<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Support;

use CodeConjure\SyliusCarrierBundle\Model\CarrierShipmentInterface;
use Sylius\Component\Core\Model\Shipment;

/** Egy befogadó bolt szállítmánya, pontosan a csomag szerződésével. */
final class CarrierShipment extends Shipment implements CarrierShipmentInterface
{
    private ?string $pickupPointId = null;

    private ?string $phoneNumber = null;

    public function getPickupPointId(): ?string
    {
        return $this->pickupPointId;
    }

    public function setPickupPointId(?string $pickupPointId): void
    {
        $this->pickupPointId = $pickupPointId;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }
}
