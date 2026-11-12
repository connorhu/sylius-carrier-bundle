<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Form\Extension;

use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A szállítmány két szállító-független mezője a pénztárban.
 *
 * - `pickupPointId`: REJTETT mező. Az átvételi pontot a szállítók widgetjei
 *   választják ki (FoxPost térkép, GLS `<gls-dpm>`), és a
 *   `carrier:pickup-point-selected` eseménnyel írják ide — a
 *   `carrier-shipment` Stimulus controller a `pickupPointInput` targeten
 *   keresztül tölti.
 * - `phoneNumber`: a címzett telefonja, ha a mód kéri.
 *
 * Mindkettő csak a saját validációs csoportjában kötelező
 * ({@see SelectShippingTypeExtension}), HTML-szinten soha: a controller
 * elrejti, ha a mód nem kéri.
 *
 * A formátum-ellenőrzés (pl. országos telefonszám-szabály) a befogadó bolt
 * dolga: a {@see self::PRIORITY} miatt egy host-kiterjesztés a mezőt
 * felülírhatja.
 */
final class CheckoutShipmentTypeExtension extends AbstractTypeExtension
{
    public const int PRIORITY = 16;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pickupPointId', HiddenType::class, [
            'required' => false,
            // A `HiddenType` alapból a szülőre buborékoltatja a hibáit — itt a
            // szállítmány-űrlapra, amit egyetlen panel sem ír ki. A „válassz
            // pontot" és a szállítók pont-ellenőrzése így némán elveszne.
            'error_bubbling' => false,
            'attr' => ['data-carrier-shipment-target' => 'pickupPointInput'],
            'constraints' => [
                new NotBlank(
                    message: 'codeconjure.carrier.checkout.pickup_point_id.not_blank',
                    groups: [SelectShippingTypeExtension::PICKUP_POINT_VALIDATION_GROUP],
                ),
            ],
        ]);

        $builder->add('phoneNumber', TelType::class, [
            'label' => 'codeconjure.carrier.checkout.phone_number.label',
            'help' => 'codeconjure.carrier.checkout.phone_number.help',
            'required' => false,
            'constraints' => [
                new NotBlank(
                    message: 'codeconjure.carrier.checkout.phone_number.not_blank',
                    groups: [SelectShippingTypeExtension::RECIPIENT_PHONE_VALIDATION_GROUP],
                ),
            ],
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShipmentType::class];
    }
}
