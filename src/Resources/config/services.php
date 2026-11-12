<?php

declare(strict_types=1);

use CodeConjure\SyliusCarrierBundle\Form\Extension\CheckoutShipmentTypeExtension;
use CodeConjure\SyliusCarrierBundle\Form\Extension\SelectShippingTypeExtension;
use CodeConjure\SyliusCarrierBundle\Twig\CarrierExtension;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Bundle\ShopBundle\Form\Type\Checkout\SelectShippingType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Explicit tag, autoconfigure NÉLKÜL: az autoconfigure egy második,
    // prioritás nélküli `form.type_extension` taget tenne mellé, és a
    // kiterjesztés kétszer futna (a bolt symfony-form-extension-sorrend
    // tanulsága).
    $services->set(CheckoutShipmentTypeExtension::class)
        ->tag('form.type_extension', ['extended_type' => ShipmentType::class, 'priority' => CheckoutShipmentTypeExtension::PRIORITY]);

    $services->set(SelectShippingTypeExtension::class)
        ->tag('form.type_extension', ['extended_type' => SelectShippingType::class]);

    $services->set(CarrierExtension::class)
        ->tag('twig.extension');
};
