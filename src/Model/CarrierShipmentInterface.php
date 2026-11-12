<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Model;

use Sylius\Component\Core\Model\ShipmentInterface;

/**
 * A szállítmány két szállító-független mezője. Egy szállítmánynak egy módja
 * van, tehát az átvételi pont azonosítója és a címzett telefonja egy-egy
 * oszlop a befogadó boltban, bármelyik szállító viszi a csomagot.
 *
 * A pénztár űrlapja ({@see \CodeConjure\SyliusCarrierBundle\Form\Extension\CheckoutShipmentTypeExtension})
 * ezeket írja — ezért kell a setter is.
 */
interface CarrierShipmentInterface extends ShipmentInterface
{
    public function getPickupPointId(): ?string;

    public function setPickupPointId(?string $pickupPointId): void;

    public function getPhoneNumber(): ?string;

    public function setPhoneNumber(?string $phoneNumber): void;
}
