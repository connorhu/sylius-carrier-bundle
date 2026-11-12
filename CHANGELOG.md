# Változásnapló

A csomag a [SemVer](https://semver.org/lang/hu/) szerint verziózódik. A `0.x` sor azt jelenti, hogy a nyilvános API — a host-szerződés interfészei, a hook-nevek, a widget-esemény — még mozog.

## [Nem kiadott]

## [0.1.1] – 2026-11-12

### Javítva

- A Stimulus controller tesztjének `vitest` fejlesztői függősége `^4.1`-re áll. A `^3.2` mellett a befogadó bolt `npm install`-ja egy második vitest-3 fát (esbuilddal) telepített a `file:`-linkelt csomag alá, mert a Symfony UX csomagok már vitest 4-et hoznak — a bolt `package-lock.json`-ja ettől több ezer sorral változott.
- `.gitattributes` `export-ignore`: a tesztek, a vitest-konfig és az assets-lock nem kerül a Composer dist-csomagba.

## [0.1.0] – 2026-11-12

### Hozzáadva

- `CarrierShipmentInterface`, `CarrierShippingMethodInterface`.
- A pénztár két mezője (`pickupPointId` rejtett, `phoneNumber`) a Sylius `ShipmentType`-on, `PRIORITY = 16`-tal; validációs csoportok (`recipient_phone_required`, `pickup_point_required`) a mód jelzőiből.
- Sablonok: a szállítmány-ciklus `carrier-shipment` hatókörrel, a mezőpanel (`carrier_fields`) a widgetek hookjával, az admin `ship` slot a `codeconjure_carrier.admin.order.shipment.ship` hookkal.
- `carrier-shipment` Stimulus controller: a szekciók és a widgetek láthatósága, a `carrier:pickup-point-selected` esemény, pont-törlés fajtaváltáskor.
- Twig: `codeconjure_hook_output_is_blank()`, `codeconjure_carrier_shipping_methods()`.
