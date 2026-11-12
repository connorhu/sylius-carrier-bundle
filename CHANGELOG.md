# Változásnapló

A csomag a [SemVer](https://semver.org/lang/hu/) szerint verziózódik. A `0.x` sor azt jelenti, hogy a nyilvános API — a host-szerződés interfészei, a hook-nevek, a widget-esemény — még mozog.

## [Nem kiadott]

### Hozzáadva

- `CarrierShipmentInterface`, `CarrierShippingMethodInterface`.
- A pénztár két mezője (`pickupPointId` rejtett, `phoneNumber`) a Sylius `ShipmentType`-on, `PRIORITY = 16`-tal; validációs csoportok (`recipient_phone_required`, `pickup_point_required`) a mód jelzőiből.
- Sablonok: a szállítmány-ciklus `carrier-shipment` hatókörrel, a mezőpanel (`carrier_fields`) a widgetek hookjával, az admin `ship` slot a `codeconjure_carrier.admin.order.shipment.ship` hookkal.
- `carrier-shipment` Stimulus controller: a szekciók és a widgetek láthatósága, a `carrier:pickup-point-selected` esemény, pont-törlés fajtaváltáskor.
- Twig: `codeconjure_hook_output_is_blank()`, `codeconjure_carrier_shipping_methods()`.
