# Sylius Carrier Bundle

[![CI](https://github.com/connorhu/sylius-carrier-bundle/actions/workflows/ci.yaml/badge.svg)](https://github.com/connorhu/sylius-carrier-bundle/actions/workflows/ci.yaml)

Szállító-semleges alap a Sylius szállító-pluginokhoz (`codeconjure/foxpost-sylius-plugin`, a GLS és a Packeta pluginja).

## Miért

Egy szállítmánynak egy módja van, tehát az átvételi pont azonosítója és a címzett telefonja **egy-egy oszlop** a boltban, bármelyik szállító viszi a csomagot. Ha ezeket a mezőket minden szállító-plugin maga adná a pénztárhoz, két plugin ütközne:

- egy Symfony-mező csak **egyszer** renderelhető;
- két azonos nevű `add()` közül a második **felülírja** az elsőt, a constraintjeivel együtt;
- a Sylius két hook-slotja (a pénztár szállítmány-ciklusa és az admin feladás-gomb) egyetlen host-sort fogad — a második plugin némán kiütné az elsőt.

Ez a csomag ezeket **egyszer** adja, a szállítók pedig csak a saját widgetjükkel és admin-akciójukkal kapcsolódnak be.

## Mit ad

| | |
|---|---|
| **Modell** | `CarrierShipmentInterface` (pickupPointId, phoneNumber), `CarrierShippingMethodInterface` (fajta, telefon kell-e, átvételi pontra szállít-e) |
| **Pénztár** | a két mező a Sylius `ShipmentType`-on; validációs csoportok a mód jelzőiből; szállítmányonkénti `carrier-shipment` Stimulus-hatókör; mezőpanel a widgetek hookjával |
| **Admin** | a `ship` slot, amibe minden szállító a `codeconjure_carrier.admin.order.shipment.ship` hookon kapcsolódik |
| **Twig** | `codeconjure_hook_output_is_blank()` (a debug-kommenteket nem számítja tartalomnak), `codeconjure_carrier_shipping_methods()` |

## Telepítés

**[`docs/installation.md`](docs/installation.md)** — a host öt lépése, és a szállító-pluginok szerződése (widget, esemény, admin-akció).

## Fejlesztés

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse -c phpstan.dist.neon
vendor/bin/ecs check

cd assets && npm install && npm test   # a Stimulus controller, vitest + jsdom
```

## License

MIT
