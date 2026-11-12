<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Unit\Template;

use CodeConjure\SyliusCarrierBundle\Tests\Support\BuildsShipmentForms;
use CodeConjure\SyliusCarrierBundle\Tests\Support\BundleTwig;
use CodeConjure\SyliusCarrierBundle\Tests\Support\CarrierShipment;
use CodeConjure\SyliusCarrierBundle\Tests\Support\Hookable;
use CodeConjure\SyliusCarrierBundle\Tests\Support\RecordingHookRenderer;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Component\Core\Model\Order;
use Symfony\Component\Form\FormView;

/**
 * A szállítmány két szállító-független mezőjének panelje a pénztárban
 * (`…form.shipments.shipment` → `carrier_fields`).
 *
 * A két szekció alapból REJTETT: a `carrier-shipment` controller a választott
 * mód jelzői szerint mutatja. Az átvételi pont szekciójában a szállítók
 * widgetjei a {@see self::WIDGET_HOOK} hookba kapcsolódnak, és a rejtett
 * `pickupPointId` mezőbe írnak.
 */
final class CheckoutCarrierFieldsTest extends TestCase
{
    use BuildsShipmentForms;

    private const string HOOK = 'sylius_shop.checkout.select_shipping.content.form.shipments.shipment';

    private const string WIDGET_HOOK = self::HOOK . '.carrier_fields';

    private const string TEMPLATE = '@CodeConjureSyliusCarrier/shop/checkout/select_shipping/carrier_fields.html.twig';

    public function testThePhoneSectionHoldsThePhoneFieldAndStartsHidden(): void
    {
        $section = $this->target($this->render(new RecordingHookRenderer()), 'phoneSection');

        self::assertTrue($section->hasAttribute('hidden'));
        $input = $this->xpath($section, './/input[@type="tel"]')[0];
        self::assertStringEndsWith('[phoneNumber]', $input->getAttribute('name'));
    }

    public function testThePickupPointSectionHoldsTheHiddenInputTheWidgetsWriteInto(): void
    {
        $section = $this->target($this->render(new RecordingHookRenderer()), 'pickupPointSection');

        self::assertTrue($section->hasAttribute('hidden'));
        $input = $this->xpath($section, './/input[@data-carrier-shipment-target="pickupPointInput"]')[0];
        self::assertSame('hidden', $input->getAttribute('type'));
        self::assertStringEndsWith('[pickupPointId]', $input->getAttribute('name'));
    }

    public function testTheSelectedPointLabelShowsAPlaceholderUntilAPointIsChosen(): void
    {
        $label = $this->target($this->render(new RecordingHookRenderer()), 'pickupPointLabel');

        self::assertSame('Még nincs kiválasztott átvételi pont.', trim($label->textContent));
    }

    public function testTheLabelCarriesThePlaceholderTheControllerRestoresOnAKindChange(): void
    {
        $shipment = new CarrierShipment();
        $shipment->setPickupPointId('HU1012-PLOCKER001');

        $label = $this->target($this->render(new RecordingHookRenderer(), $shipment), 'pickupPointLabel');

        self::assertSame('Még nincs kiválasztott átvételi pont.', $label->getAttribute('data-placeholder'));
    }

    public function testTheSelectedPointLabelShowsTheStoredPointOnReturn(): void
    {
        // Visszalépéskor (vagy egy elbukott beküldés után) a mező már tölt;
        // a pont NEVÉT csak a szállító widgetje ismeri, az azonosítót viszont
        // a csomag is meg tudja mutatni.
        $shipment = new CarrierShipment();
        $shipment->setPickupPointId('HU1012-PLOCKER001');

        $label = $this->target($this->render(new RecordingHookRenderer(), $shipment), 'pickupPointLabel');

        self::assertSame('HU1012-PLOCKER001', trim($label->textContent));
    }

    public function testTheCarrierWidgetsRenderInsideThePickupPointSection(): void
    {
        $hooks = new RecordingHookRenderer([self::WIDGET_HOOK => '<div id="gls-widget"></div>']);

        $section = $this->target($this->render($hooks), 'pickupPointSection');

        self::assertCount(1, $this->xpath($section, './/div[@id="gls-widget"]'));
    }

    public function testTheWidgetHookGetsTheShipmentFormAndTheOrder(): void
    {
        $hooks = new RecordingHookRenderer();
        $order = new Order();

        $this->render($hooks, order: $order);

        self::assertSame([self::WIDGET_HOOK], $hooks->calls[0]['names']);
        self::assertInstanceOf(FormView::class, $hooks->calls[0]['context']['form']);
        self::assertSame($order, $hooks->calls[0]['context']['order']);
    }

    public function testAHostThatRemovedTheFieldsStillGetsACheckout(): void
    {
        // Egy host felülírhatja a mezőket (a kiterjesztés prioritása ezt
        // engedi); ha közben el is hagyja őket, a pénztár ne álljon le.
        $form = self::shipmentFormFactory(withBundle: false)->create(ShipmentType::class, new CarrierShipment())->createView();

        $html = $this->renderView($form, new RecordingHookRenderer(), new Order());

        self::assertStringNotContainsString('data-carrier-shipment-target', $html);
    }

    private function render(RecordingHookRenderer $hooks, ?CarrierShipment $shipment = null, ?Order $order = null): string
    {
        $form = self::shipmentFormFactory()->create(ShipmentType::class, $shipment ?? new CarrierShipment())->createView();

        return $this->renderView($form, $hooks, $order ?? new Order());
    }

    private function renderView(FormView $form, RecordingHookRenderer $hooks, Order $order): string
    {
        return BundleTwig::environment($hooks)->render(self::TEMPLATE, [
            'hookable_metadata' => Hookable::metadata(self::HOOK, ['form' => $form, 'index' => 1, 'order' => $order]),
        ]);
    }

    private function target(string $html, string $name): \DOMElement
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
        $nodes = $this->xpath($dom, \sprintf('//*[@data-carrier-shipment-target="%s"]', $name));
        self::assertCount(1, $nodes, \sprintf('Nem pontosan egy `%s` target van.', $name));

        return $nodes[0];
    }

    /** @return list<\DOMElement> */
    private function xpath(\DOMNode $context, string $query): array
    {
        $document = $context instanceof \DOMDocument ? $context : $context->ownerDocument;
        \assert($document instanceof \DOMDocument);
        $found = [];
        foreach ((new \DOMXPath($document))->query($query, $context) ?: [] as $node) {
            \assert($node instanceof \DOMElement);
            $found[] = $node;
        }

        return $found;
    }
}
