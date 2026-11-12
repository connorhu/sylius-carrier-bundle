<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Unit\Template;

use CodeConjure\SyliusCarrierBundle\Tests\Support\BundleTwig;
use CodeConjure\SyliusCarrierBundle\Tests\Support\RecordingHookRenderer;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Shipment;
use Sylius\TwigHooks\Hook\Renderer\Debug\HookDebugCommentRenderer;
use Sylius\TwigHooks\Hook\Renderer\HookRendererInterface;
use Symfony\Component\Form\FormView;

/**
 * Az admin szállítmány-panel `ship` slotja.
 *
 * A host EGY `props.template` sort ad a
 * `sylius_admin.order.show.content.sections.shipments.item.actions` → `ship`
 * hookable-re — ez a csomag sablonja. Minden szállító-plugin (FoxPost, GLS,
 * Packeta) a {@see self::CARRIER_HOOK} hookba kapcsolódik, a saját
 * „én kezelem-e ezt a módot?" feltételével. Ha senki nem rajzolt, a Sylius
 * alapgombja jön.
 */
final class AdminShipSlotTest extends TestCase
{
    public const string CARRIER_HOOK = 'codeconjure_carrier.admin.order.shipment.ship';

    private const string TEMPLATE = '@CodeConjureSyliusCarrier/admin/shipment/ship.html.twig';

    public function testACarrierActionReplacesTheStockButton(): void
    {
        $html = $this->render(new RecordingHookRenderer([self::CARRIER_HOOK => '<button>GLS feladás</button>']));

        self::assertStringContainsString('<button>GLS feladás</button>', $html);
        self::assertStringNotContainsString('STOCK-SYLIUS-SHIP-BUTTON', $html);
    }

    public function testTheStockButtonStaysWhenNoCarrierClaimsTheShipment(): void
    {
        self::assertStringContainsString('STOCK-SYLIUS-SHIP-BUTTON', $this->render(new RecordingHookRenderer()));
    }

    public function testDebugCommentsAloneDoNotCountAsACarrierAction(): void
    {
        $html = $this->render(new HookDebugCommentRenderer(new RecordingHookRenderer()));

        self::assertStringContainsString('STOCK-SYLIUS-SHIP-BUTTON', $html);
    }

    public function testACarrierActionStillWinsUnderDebugComments(): void
    {
        $html = $this->render(new HookDebugCommentRenderer(
            new RecordingHookRenderer([self::CARRIER_HOOK => '<button>GLS feladás</button>']),
        ));

        self::assertStringNotContainsString('STOCK-SYLIUS-SHIP-BUTTON', $html);
    }

    public function testTheHookIsAskedByItsAbsoluteNameWithTheShipmentAndItsForm(): void
    {
        $hooks = new RecordingHookRenderer();
        $shipment = new Shipment();

        $this->render($hooks, $shipment);

        self::assertCount(1, $hooks->calls);
        self::assertSame([self::CARRIER_HOOK], $hooks->calls[0]['names']);
        self::assertSame($shipment, $hooks->calls[0]['context']['resource']);
        self::assertInstanceOf(FormView::class, $hooks->calls[0]['context']['form']);
        self::assertArrayHasKey('attributes', $hooks->calls[0]['context']);
    }

    private function render(HookRendererInterface $hooks, ?Shipment $shipment = null): string
    {
        $form = new FormView();
        $form->vars['cache_key'] = '_sylius_shipment_ship';

        return BundleTwig::environment($hooks, [
            'SyliusAdmin' => \dirname(__DIR__, 2) . '/Fixtures/templates/SyliusAdmin',
        ])->render(self::TEMPLATE, [
            'resource' => $shipment ?? new Shipment(),
            'form' => $form,
            'attributes' => 'data-live-id="ship-1"',
        ]);
    }
}
