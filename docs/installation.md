# Telepítés egy befogadó Sylius boltba

A csomag **nem önálló szállító**: a szállító-pluginok (FoxPost, GLS, Packeta) közös alapja. A befogadó bolt egyszer telepíti, és egyszer köti be — akárhány szállító-plugin jön rá.

Flex recipe nincs, a lépések kézzel mennek.

## 1. Csomag és bundle

```bash
composer require codeconjure/sylius-carrier-bundle
```

```php
// config/bundles.php — a szállító-pluginok ELÉ
CodeConjure\SyliusCarrierBundle\CodeConjureSyliusCarrierBundle::class => ['all' => true],
```

## 2. A két entitás szerződése

A szállítmány két mezője **a host oszlopa** — a csomag nem ad migrációt, mert egy szállítmánynak egy módja van, és ugyanaz az oszlop szolgál minden szállítót.

```php
use CodeConjure\SyliusCarrierBundle\Model\CarrierShipmentInterface;

class Shipment extends BaseShipment implements CarrierShipmentInterface
{
    #[ORM\Column(name: 'pickup_point_id', type: Types::STRING, nullable: true)]
    private ?string $pickupPointId = null;

    #[ORM\Column(name: 'phone_number', type: Types::STRING, length: 30, nullable: true)]
    private ?string $phoneNumber = null;

    // getter + SETTER mindkettőhöz: a pénztár űrlapja ír, nem csak olvas
}
```

```php
use CodeConjure\SyliusCarrierBundle\Model\CarrierShippingMethodInterface;

class ShippingMethod extends BaseShippingMethod implements CarrierShippingMethodInterface
{
    public function getDeliveryKindSlug(): ?string { /* a szállító fajtája, pl. 'gls_parcel_locker', vagy null */ }
    public function requiresRecipientPhone(): bool { /* … */ }
    public function deliversToPickupPoint(): bool { /* … */ }
}
```

## 3. A pénztár szállítmány-ciklusa (host-sor)

A `shipment` kulcs a SyliusShopBundle-é, a bundle `prepend()`-je nem tudja átírni — a hostnak kell:

```yaml
# config/packages/_sylius.yaml
sylius_twig_hooks:
    hooks:
        'sylius_shop.checkout.select_shipping.content.form.shipments':
            shipment:
                template: '@CodeConjureSyliusCarrier/shop/checkout/select_shipping/shipments.html.twig'
```

Enélkül nincs szállítmányonkénti `carrier-shipment` hatókör, és a két mező szekciója **mindig rejtve marad** — render-hiba nélkül.

A két mező panelje (`…shipments.shipment` → `carrier_fields`, prioritás `-50`) **host-lépés nélkül** megjelenik: az a név új, a bundle `prepend()`-je bevezeti. A sablonja felülírható (`carrier_fields: { template: … }`); a felülíró sablonnak a `data-carrier-shipment-target` attribútumokat meg kell tartania.

## 4. Az admin feladás-panel (host-sor)

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.order.show.content.sections.shipments.item.actions':
            ship:
                props:
                    template: '@CodeConjureSyliusCarrier/admin/shipment/ship.html.twig'
```

A `props` kulcs szerint fésül össze: a Sylius `resource` / `path` / `pathParameters` propjai megmaradnak.

## 5. Stimulus controller

A `composer require` után a Flex felveszi a `carrier-shipment` controllert az `assets/controllers.json`-ba (a csomag `symfony-ux` kulcsszava miatt), és a `package.json`-ba a `"@codeconjure/sylius-carrier-bundle": "file:vendor/codeconjure/sylius-carrier-bundle/assets"` függőséget. Utána:

```bash
npm install && npm run build
```

## Szerződés a szállító-pluginoknak

Egy szállító-plugin **nem ad** `pickupPointId` vagy `phoneNumber` mezőt, és **nem állítja** a fenti két host-sort. Helyette:

### Widget a pénztárban

Hookable a `sylius_shop.checkout.select_shipping.content.form.shipments.shipment.carrier_fields` hookon (új név, a plugin `prepend()`-je beveheti). Kontextus: `form` (a szállítmány űrlapnézete), `index`, `order`.

```twig
<div data-carrier-delivery-kinds="gls_parcel_locker gls_parcel_shop">
    {# a szállító saját választója #}
</div>
```

- A gyökérelem `data-carrier-delivery-kinds` listája adja meg, melyik `getDeliveryKindSlug()` értékeknél látszik; a többinél a controller rejti.
- A választott pontot eseménnyel adja át — a mezőbe a controller ír:

  ```js
  element.dispatchEvent(new CustomEvent('carrier:pickup-point-selected', {
      bubbles: true,
      detail: { id: 'HU1012-PLOCKER001', label: 'GLS Automata SHELL Budapest I. Ker.' },
  }));
  ```

- Fajtaváltáskor a controller a pontot törli (egy szállító azonosítója másiknál érvénytelen).

### Akció az admin feladás-panelen

Hookable a `codeconjure_carrier.admin.order.shipment.ship` hookon. Kontextus: `resource` (a szállítmány), `form`, `attributes`. **Csak a saját módjainál rajzoljon**; ha egyetlen hookable sem rajzol (a Twig Hooks debug-kommentjei nem számítanak), a Sylius alapgombja jelenik meg.

### Validáció

A két mező a `recipient_phone_required` / `pickup_point_required` csoportban kötelező, a mód `requiresRecipientPhone()` / `deliversToPickupPoint()` jelzője szerint. Szállító-specifikus szabály (pl. telefonszám-formátum, pontazonosító-formátum) a plugin saját constraintje a saját csoportjában.

## Ellenőrzés

```bash
# a két form-kiterjesztés regisztrált
bin/console debug:form 'Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType' | grep -i carrier
# a carrier_fields hookable a pénztár hookján
bin/console debug:config sylius_twig_hooks | grep -A2 carrier_fields
# a Stimulus controller a host manifestjében
grep -A4 'sylius-carrier-bundle' assets/controllers.json
```
