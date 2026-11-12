<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Unit\Template;

use CodeConjure\SyliusCarrierBundle\Tests\Support\BundleTwig;
use CodeConjure\SyliusCarrierBundle\Tests\Support\CarrierShippingMethod;
use CodeConjure\SyliusCarrierBundle\Tests\Support\Hookable;
use CodeConjure\SyliusCarrierBundle\Tests\Support\RecordingHookRenderer;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\Shipment;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;

/**
 * A pénztár 2. lépésének szállítmány-ciklusa.
 *
 * A Sylius alapsablonja a szállítmányokat KÖZÖS elem nélkül rendereli, tehát
 * egy Stimulus controllernek nincs hová ülnie, ahonnan a mód rádiógombjait
 * ÉS a mezőpanelt is látja. Ez a sablon a `…form.shipments` → `shipment`
 * hookable-t váltja (a host egy sora, mert a név a Syliusé), és minden
 * szállítmányt saját `carrier-shipment` hatókörbe tesz, a módjai
 * szállító-semleges leírásával.
 */
final class CheckoutShipmentScopeTest extends TestCase
{
    private const string HOOK = 'sylius_shop.checkout.select_shipping.content.form.shipments';

    private const string TEMPLATE = '@CodeConjureSyliusCarrier/shop/checkout/select_shipping/shipments.html.twig';

    public function testEveryShipmentGetsItsOwnScope(): void
    {
        $html = $this->render(new RecordingHookRenderer(), 2);

        self::assertCount(2, $this->scopes($html));
    }

    public function testTheScopeDescribesTheMethodsOfItsOwnShipment(): void
    {
        $scope = $this->scopes($this->render(new RecordingHookRenderer(), 1))[0];
        $methods = json_decode($scope->getAttribute('data-carrier-shipment-methods-value'), true);

        self::assertSame([
            'gls_locker' => ['deliveryKind' => 'gls_parcel_locker', 'requiresRecipientPhone' => true, 'pickupPoint' => true],
            'personal_pickup' => ['deliveryKind' => null, 'requiresRecipientPhone' => false, 'pickupPoint' => false],
        ], $methods);
    }

    public function testTheShipmentContentRendersInsideTheScope(): void
    {
        $hooks = new RecordingHookRenderer([self::HOOK . '.shipment' => '<p id="shipment-content">tartalom</p>']);

        $scope = $this->scopes($this->render($hooks, 1))[0];

        self::assertNotNull($scope->getElementsByTagName('p')->item(0));
    }

    public function testTheNestedShipmentHookGetsTheShipmentFormItsIndexAndTheOrder(): void
    {
        $hooks = new RecordingHookRenderer();
        $order = $this->order(2);

        $this->render($hooks, 2, $order);

        self::assertCount(2, $hooks->calls);
        self::assertSame([self::HOOK . '.shipment'], $hooks->calls[1]['names']);
        self::assertSame(2, $hooks->calls[1]['context']['index']);
        self::assertSame($order, $hooks->calls[1]['context']['order']);
        $form = $hooks->calls[1]['context']['form'];
        self::assertInstanceOf(FormView::class, $form);
        self::assertSame('1', $form->vars['name']);
    }

    public function testAnOrderWithoutShipmentsRendersTheUnavailableHook(): void
    {
        $hooks = new RecordingHookRenderer();

        $html = $this->render($hooks, 0);

        self::assertSame([], $this->scopes($html));
        self::assertSame([self::HOOK . '.shipment#unavailable'], $hooks->calls[0]['names']);
    }

    private function render(RecordingHookRenderer $hooks, int $shipments, ?Order $order = null): string
    {
        $order ??= $this->order($shipments);
        $context = ['form' => $this->selectShippingForm($shipments), 'order' => $order];

        return BundleTwig::environment($hooks)->render(self::TEMPLATE, [
            'hookable_metadata' => Hookable::metadata(self::HOOK, $context),
        ]);
    }

    private function order(int $shipments): Order
    {
        $order = new Order();
        for ($i = 0; $i < $shipments; ++$i) {
            $order->addShipment(new Shipment());
        }

        return $order;
    }

    /** A `SelectShippingType` nézete: `shipments[n].method` a módválasztékkal. */
    private function selectShippingForm(int $shipments): FormView
    {
        $root = new FormView();
        $collection = new FormView($root);
        $collection->vars['name'] = 'shipments';
        $root->children['shipments'] = $collection;

        for ($i = 0; $i < $shipments; ++$i) {
            $shipment = new FormView($collection);
            $shipment->vars['name'] = (string) $i;
            $method = new FormView($shipment);
            $method->vars['choices'] = [
                new ChoiceView(new CarrierShippingMethod('gls_locker', 'gls_parcel_locker', true, true), 'gls_locker', 'GLS automata'),
                new ChoiceView(new CarrierShippingMethod('personal_pickup'), 'personal_pickup', 'Személyes átvétel'),
            ];
            $shipment->children['method'] = $method;
            $collection->children[(string) $i] = $shipment;
        }

        return $root;
    }

    /** @return list<\DOMElement> */
    private function scopes(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
        $scopes = [];
        foreach ((new \DOMXPath($dom))->query('//*[@data-controller="carrier-shipment"]') ?: [] as $node) {
            \assert($node instanceof \DOMElement);
            $scopes[] = $node;
        }

        return $scopes;
    }
}
