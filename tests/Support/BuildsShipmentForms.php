<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Support;

use CodeConjure\SyliusCarrierBundle\Form\Extension\CheckoutShipmentTypeExtension;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Bundle\ShippingBundle\Form\Type\ShippingMethodChoiceType;
use Sylius\Component\Core\Model\Shipment;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

/**
 * A Sylius pénztár-`ShipmentType` VALÓDI példánya, a csomag kiterjesztésével
 * vagy anélkül. A `ShipmentType` PRE_SET_DATA listenere `ShippingMethodChoiceType`-ként
 * veszi fel a `method` mezőt; az osztály final, a három argumentuma interfész,
 * tehát dublőrözhető (üres választékkal).
 *
 * @phpstan-require-extends \PHPUnit\Framework\TestCase
 */
trait BuildsShipmentForms
{
    private static function shipmentFormFactory(bool $withBundle = true): FormFactoryInterface
    {
        $resolver = self::createStub(ShippingMethodsResolverInterface::class);
        $resolver->method('supports')->willReturn(false);
        $repository = self::createStub(RepositoryInterface::class);
        $repository->method('findAll')->willReturn([]);

        $builder = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType(new ShipmentType(Shipment::class))
            ->addType(new ShippingMethodChoiceType($resolver, self::createStub(ServiceRegistryInterface::class), $repository));

        if ($withBundle) {
            $builder->addTypeExtension(new CheckoutShipmentTypeExtension());
        }

        return $builder->getFormFactory();
    }
}
