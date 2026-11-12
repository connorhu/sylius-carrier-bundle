<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Support;

use CodeConjure\SyliusCarrierBundle\Model\CarrierShippingMethodInterface;
use Sylius\Component\Core\Model\ShippingMethod;

/** Egy befogadó bolt szállítási módja, ahogy a csomag szerződése látja. */
final class CarrierShippingMethod extends ShippingMethod implements CarrierShippingMethodInterface
{
    public function __construct(
        string $code = 'method',
        private readonly ?string $deliveryKindSlug = null,
        private readonly bool $requiresRecipientPhone = false,
        private readonly bool $deliversToPickupPoint = false,
    ) {
        parent::__construct();
        $this->setCode($code);
    }

    public function getDeliveryKindSlug(): ?string
    {
        return $this->deliveryKindSlug;
    }

    public function requiresRecipientPhone(): bool
    {
        return $this->requiresRecipientPhone;
    }

    public function deliversToPickupPoint(): bool
    {
        return $this->deliversToPickupPoint;
    }
}
