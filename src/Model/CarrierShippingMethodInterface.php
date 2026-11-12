<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Model;

use Sylius\Component\Shipping\Model\ShippingMethodInterface;

/**
 * A szállítási mód szállító-semleges jelzői: ezekből dönt a pénztár, hogy a
 * címzett telefonja és az átvételi pont kötelező-e, és melyik szállító
 * widgetje jelenjen meg.
 *
 * A befogadó bolt szállítási mód-entitása implementálja — egyszer, akárhány
 * szállító-plugin van telepítve.
 */
interface CarrierShippingMethodInterface extends ShippingMethodInterface
{
    /**
     * A szállító saját fajta-azonosítója (pl. `foxpost_parcel_locker`,
     * `gls_parcel_shop`), vagy null, ha a módhoz nem tartozik szállító-plugin.
     * A szállító-pluginok widgetjei erre illeszkednek.
     */
    public function getDeliveryKindSlug(): ?string;

    public function requiresRecipientPhone(): bool;

    public function deliversToPickupPoint(): bool;
}
