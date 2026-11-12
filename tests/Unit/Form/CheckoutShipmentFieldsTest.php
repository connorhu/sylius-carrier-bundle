<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Unit\Form;

use CodeConjure\SyliusCarrierBundle\Form\Extension\CheckoutShipmentTypeExtension;
use CodeConjure\SyliusCarrierBundle\Form\Extension\SelectShippingTypeExtension;
use CodeConjure\SyliusCarrierBundle\Model\CarrierShipmentInterface;
use CodeConjure\SyliusCarrierBundle\Tests\Support\BuildsShipmentForms;
use CodeConjure\SyliusCarrierBundle\Tests\Support\CarrierShipment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A pénztár két szállító-független mezője EGYSZER, ebből a csomagból kerül a
 * Sylius `ShipmentType`-ra — a szállító-pluginok nem adják hozzá újra (két
 * azonos nevű `add()` közül a második felülírná az elsőt a constraintjeivel
 * együtt, egy mező pedig csak egyszer renderelhető).
 */
final class CheckoutShipmentFieldsTest extends TestCase
{
    use BuildsShipmentForms;

    /** @return iterable<string, array{string, class-string, string}> */
    public static function fields(): iterable
    {
        yield 'phoneNumber' => ['phoneNumber', TelType::class, SelectShippingTypeExtension::RECIPIENT_PHONE_VALIDATION_GROUP];
        yield 'pickupPointId' => ['pickupPointId', HiddenType::class, SelectShippingTypeExtension::PICKUP_POINT_VALIDATION_GROUP];
    }

    /** @param class-string $type */
    #[DataProvider('fields')]
    public function testTheBundleAddsTheField(string $field, string $type, string $group): void
    {
        $form = $this->shipmentForm();

        self::assertTrue($form->has($field));
        self::assertInstanceOf($type, $form->get($field)->getConfig()->getType()->getInnerType());
    }

    /** @param class-string $type */
    #[DataProvider('fields')]
    public function testTheFieldIsOnlyRequiredInItsOwnValidationGroup(string $field, string $type, string $group): void
    {
        $constraints = $this->shipmentForm()->get($field)->getConfig()->getOption('constraints');
        self::assertIsArray($constraints);
        $notBlanks = array_values(array_filter($constraints, static fn (mixed $c): bool => $c instanceof NotBlank));

        self::assertCount(1, $notBlanks);
        self::assertSame([$group], $notBlanks[0]->groups);
    }

    /** @param class-string $type */
    #[DataProvider('fields')]
    public function testTheFieldIsNotRequiredAtTheHtmlLevel(string $field, string $type, string $group): void
    {
        // A controller elrejti, ha a mód nem kéri: egy `required` rejtett mező
        // a böngészőben blokkolná a beküldést.
        self::assertFalse($this->shipmentForm()->get($field)->getConfig()->getOption('required'));
    }

    public function testThePickupPointInputIsTheTargetTheWidgetsWriteInto(): void
    {
        $attr = $this->shipmentForm()->get('pickupPointId')->getConfig()->getOption('attr');

        self::assertSame(['data-carrier-shipment-target' => 'pickupPointInput'], $attr);
    }

    /**
     * A rejtett mező hibája a MEZŐN marad. A `HiddenType` alapból
     * felbuborékoltatja a hibáit a szülőre — itt a szállítmány-űrlapra, amit
     * se ennek a csomagnak a panelje, se egy host panelje nem ír ki (mindkettő
     * a mező `form_errors()`-át rendereli). A „válassz átvételi pontot" és a
     * szállítók pont-ellenőrzésének hibája így NÉMÁN elveszett: a vevő a 2.
     * lépésen ragadt, üzenet nélkül (mérve a befogadó boltban, 2026-11-12).
     */
    public function testThePickupPointErrorStaysOnTheField(): void
    {
        $form = self::shipmentFormFactory()->create(ShipmentType::class, new CarrierShipment());
        $form->submit(['pickupPointId' => 'X'], false);
        $form->get('pickupPointId')->addError(new FormError('rossz pont'));

        self::assertCount(1, $form->get('pickupPointId')->getErrors());
        self::assertCount(0, $form->getErrors());
    }

    public function testSubmittingTheFormWritesBothFieldsOntoTheShipment(): void
    {
        $shipment = new CarrierShipment();
        $form = self::shipmentFormFactory()->create(ShipmentType::class, $shipment);

        $form->submit(['phoneNumber' => '+36301234567', 'pickupPointId' => 'HU1012-PLOCKER001'], false);

        self::assertTrue($form->isSynchronized());
        self::assertSame('+36301234567', $shipment->getPhoneNumber());
        self::assertSame('HU1012-PLOCKER001', $shipment->getPickupPointId());
    }

    public function testEveryAddedFieldIsWritableThroughThePublishedContract(): void
    {
        $contract = new \ReflectionClass(CarrierShipmentInterface::class);
        $with = array_keys($this->shipmentForm()->all());
        $without = array_keys(self::shipmentFormFactory(withBundle: false)->create(ShipmentType::class, new CarrierShipment())->all());
        $added = array_values(array_diff($with, $without));

        self::assertSame(['pickupPointId', 'phoneNumber'], $added);
        foreach ($added as $field) {
            self::assertTrue($contract->hasMethod('get' . ucfirst($field)));
            self::assertTrue($contract->hasMethod('set' . ucfirst($field)));
        }
    }

    public function testAHostExtensionWithAHigherPriorityCanStillOverrideAField(): void
    {
        // A Symfony a kiterjesztéseket prioritás szerint, csökkenő sorrendben
        // futtatja: a csomagé publikált prioritással fut, hogy egy host a
        // saját (alapértelmezett, 0-s) kiterjesztésével felülírhassa.
        self::assertGreaterThan(0, CheckoutShipmentTypeExtension::PRIORITY);
    }

    private function shipmentForm(): FormInterface
    {
        return self::shipmentFormFactory()->create(ShipmentType::class, new CarrierShipment());
    }
}
