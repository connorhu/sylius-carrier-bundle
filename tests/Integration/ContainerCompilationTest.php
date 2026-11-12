<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Integration;

use CodeConjure\SyliusCarrierBundle\CodeConjureSyliusCarrierBundle;
use CodeConjure\SyliusCarrierBundle\DependencyInjection\CodeConjureSyliusCarrierExtension;
use CodeConjure\SyliusCarrierBundle\Form\Extension\CheckoutShipmentTypeExtension;
use CodeConjure\SyliusCarrierBundle\Form\Extension\SelectShippingTypeExtension;
use CodeConjure\SyliusCarrierBundle\Twig\CarrierExtension;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Bundle\ShopBundle\Form\Type\Checkout\SelectShippingType;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * A bundle a hostban: a két form-kiterjesztés a megfelelő típuson és
 * prioritással, a Twig-extension regisztrálva, és a `carrier_fields`
 * hookable host-lépés nélkül a pénztár szállítmány-hookján.
 */
final class ContainerCompilationTest extends TestCase
{
    public function testTheBundleExposesItsExtension(): void
    {
        self::assertInstanceOf(CodeConjureSyliusCarrierExtension::class, (new CodeConjureSyliusCarrierBundle())->getContainerExtension());
    }

    public function testTheCheckoutFieldsExtendTheSyliusShipmentTypeWithThePublishedPriority(): void
    {
        $tags = $this->loaded()->getDefinition(CheckoutShipmentTypeExtension::class)->getTag('form.type_extension');

        self::assertSame([['extended_type' => ShipmentType::class, 'priority' => CheckoutShipmentTypeExtension::PRIORITY]], $tags);
    }

    public function testTheValidationGroupsExtendTheSelectShippingType(): void
    {
        $tags = $this->loaded()->getDefinition(SelectShippingTypeExtension::class)->getTag('form.type_extension');

        self::assertSame([['extended_type' => SelectShippingType::class]], $tags);
    }

    public function testTheTwigExtensionIsRegistered(): void
    {
        self::assertTrue($this->loaded()->getDefinition(CarrierExtension::class)->hasTag('twig.extension'));
    }

    public function testThePrependAddsTheCarrierFieldsHookable(): void
    {
        $container = new ContainerBuilder();
        (new CodeConjureSyliusCarrierExtension())->prepend($container);

        $hooks = $container->getExtensionConfig('sylius_twig_hooks')[0]['hooks'] ?? null;
        self::assertIsArray($hooks);
        $shipmentHook = $hooks['sylius_shop.checkout.select_shipping.content.form.shipments.shipment'] ?? null;
        self::assertIsArray($shipmentHook);
        self::assertSame(
            [
                'template' => '@CodeConjureSyliusCarrier/shop/checkout/select_shipping/carrier_fields.html.twig',
                'priority' => -50,
            ],
            $shipmentHook['carrier_fields'] ?? null,
        );
    }

    public function testTheContainerCompilesWithoutAnyHostConfiguration(): void
    {
        // A bundle-nek nincs kötelező konfigja: egy üres host is fordul vele.
        $this->loaded()->compile();

        $this->addToAssertionCount(1);
    }

    private function loaded(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new CodeConjureSyliusCarrierExtension())->load([], $container);

        return $container;
    }
}
