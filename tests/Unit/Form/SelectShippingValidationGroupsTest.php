<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Unit\Form;

use CodeConjure\SyliusCarrierBundle\Form\Extension\SelectShippingTypeExtension;
use CodeConjure\SyliusCarrierBundle\Tests\Support\CarrierShippingMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\Shipment;
use Sylius\Component\Core\Model\ShippingMethod;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A pénztár 2. lépésének validációs csoportjai a szállítási mód
 * SZÁLLÍTÓ-SEMLEGES jelzőiből jönnek — nem egy szállító saját fajtájából.
 *
 * A két csoportot a {@see CheckoutShipmentTypeExtension} mezőinek `NotBlank`
 * szabálya használja: a csoport nélkül a kötelező telefon / átvételi pont
 * szabálya némán nem fut le.
 */
final class SelectShippingValidationGroupsTest extends TestCase
{
    /** @return iterable<string, array{CarrierShippingMethod|ShippingMethod, list<string>}> */
    public static function methods(): iterable
    {
        yield 'semmi extra' => [new CarrierShippingMethod(), ['sylius']];
        yield 'telefon kell' => [new CarrierShippingMethod(requiresRecipientPhone: true), ['sylius', 'recipient_phone_required']];
        yield 'átvételi pont kell' => [new CarrierShippingMethod(deliversToPickupPoint: true), ['sylius', 'pickup_point_required']];
        yield 'mindkettő' => [
            new CarrierShippingMethod(requiresRecipientPhone: true, deliversToPickupPoint: true),
            ['sylius', 'recipient_phone_required', 'pickup_point_required'],
        ];
        yield 'a szerződést nem ismerő mód' => [new ShippingMethod(), ['sylius']];
    }

    /** @param list<string> $expected */
    #[DataProvider('methods')]
    public function testTheGroupsFollowTheMethodFlags(CarrierShippingMethod|ShippingMethod $method, array $expected): void
    {
        self::assertSame($expected, $this->groupsFor($this->orderWith($method)));
    }

    public function testTwoShipmentsNeedingTheSameGroupListItOnce(): void
    {
        $order = $this->orderWith(
            new CarrierShippingMethod(code: 'a', requiresRecipientPhone: true),
            new CarrierShippingMethod(code: 'b', requiresRecipientPhone: true),
        );

        self::assertSame(['sylius', 'recipient_phone_required'], $this->groupsFor($order));
    }

    public function testANonOrderFormFallsBackToTheDefaultGroup(): void
    {
        self::assertSame(['Default'], $this->groupsFor(null));
    }

    public function testTheGroupNamesArePublishedAsConstants(): void
    {
        self::assertSame('recipient_phone_required', SelectShippingTypeExtension::RECIPIENT_PHONE_VALIDATION_GROUP);
        self::assertSame('pickup_point_required', SelectShippingTypeExtension::PICKUP_POINT_VALIDATION_GROUP);
    }

    private function orderWith(CarrierShippingMethod|ShippingMethod ...$methods): Order
    {
        $order = new Order();
        foreach ($methods as $method) {
            $shipment = new Shipment();
            $shipment->setMethod($method);
            $order->addShipment($shipment);
        }

        return $order;
    }

    /** @return list<string> */
    private function groupsFor(?Order $data): array
    {
        $resolver = new OptionsResolver();
        (new SelectShippingTypeExtension())->configureOptions($resolver);
        $groups = $resolver->resolve()['validation_groups'];
        self::assertIsCallable($groups);

        $form = $this->createStub(FormInterface::class);
        $form->method('getData')->willReturn($data);

        return $groups($form);
    }
}
