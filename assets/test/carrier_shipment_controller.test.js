import { Application } from '@hotwired/stimulus';
import { afterEach, beforeEach, describe, expect, test } from 'vitest';
import CarrierShipmentController from '../controllers/carrier_shipment_controller.js';

// A DOM pontosan az, amit a csomag két sablonja (shipments.html.twig,
// carrier_fields.html.twig) és a Sylius módválasztója renderel; a widget két
// szállító (GLS automata, FoxPost automata) gyökéreleme.
const METHODS = {
    gls_locker: { deliveryKind: 'gls_parcel_locker', requiresRecipientPhone: true, pickupPoint: true },
    gls_shop: { deliveryKind: 'gls_parcel_shop', requiresRecipientPhone: true, pickupPoint: true },
    gls_courier: { deliveryKind: 'gls_home_delivery', requiresRecipientPhone: true, pickupPoint: false },
    personal_pickup: { deliveryKind: null, requiresRecipientPhone: false, pickupPoint: false },
};

function render({ checked = null, pickupPointId = '' } = {}) {
    const radios = Object.keys(METHODS)
        .map((code) => `<label><input type="radio" name="sylius_checkout_select_shipping[shipments][0][method]" value="${code}" ${code === checked ? 'checked' : ''}> ${code}</label>`)
        .join('');

    document.body.innerHTML = `
        <form>
            <div data-controller="carrier-shipment" data-carrier-shipment-methods-value='${JSON.stringify(METHODS)}'>
                ${radios}
                <div data-carrier-shipment-target="phoneSection" hidden><input type="tel"></div>
                <div data-carrier-shipment-target="pickupPointSection" hidden>
                    <div id="gls-locker-widget" data-carrier-delivery-kinds="gls_parcel_locker"></div>
                    <div id="gls-shop-widget" data-carrier-delivery-kinds="gls_parcel_shop gls_parcel_locker_alt"></div>
                    <div id="foxpost-widget" data-carrier-delivery-kinds="foxpost_parcel_locker"></div>
                    <p data-carrier-shipment-target="pickupPointLabel" data-placeholder="Még nincs kiválasztott átvételi pont.">${pickupPointId || 'Még nincs kiválasztott átvételi pont.'}</p>
                    <input type="hidden" data-carrier-shipment-target="pickupPointInput" value="${pickupPointId}">
                </div>
            </div>
        </form>`;
}

let application;

async function start(options) {
    render(options);
    application = Application.start();
    application.register('carrier-shipment', CarrierShipmentController);
    await Promise.resolve();
}

async function choose(code) {
    const radio = document.querySelector(`input[value="${code}"]`);
    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));
    await Promise.resolve();
}

const el = (selector) => document.querySelector(selector);
const phoneSection = () => el('[data-carrier-shipment-target="phoneSection"]');
const pickupSection = () => el('[data-carrier-shipment-target="pickupPointSection"]');
const input = () => el('[data-carrier-shipment-target="pickupPointInput"]');
const label = () => el('[data-carrier-shipment-target="pickupPointLabel"]');

afterEach(() => {
    application?.stop();
    document.body.innerHTML = '';
});

describe('a szekciók láthatósága', () => {
    test('választott mód nélkül mindkét szekció rejtett', async () => {
        await start();

        expect(phoneSection().hidden).toBe(true);
        expect(pickupSection().hidden).toBe(true);
    });

    test('a telefont kérő mód a telefon-szekciót mutatja', async () => {
        await start();
        await choose('gls_courier');

        expect(phoneSection().hidden).toBe(false);
        expect(pickupSection().hidden).toBe(true);
    });

    test('az átvételi pontra szállító mód az átvételi pont szekcióját mutatja', async () => {
        await start();
        await choose('gls_locker');

        expect(pickupSection().hidden).toBe(false);
    });

    test('a szerződést nem ismerő mód semmit nem mutat', async () => {
        await start();
        await choose('gls_locker');
        await choose('personal_pickup');

        expect(phoneSection().hidden).toBe(true);
        expect(pickupSection().hidden).toBe(true);
    });

    test('egy már választott mód a betöltéskor is érvényesül (visszalépés, elbukott beküldés)', async () => {
        await start({ checked: 'gls_locker' });

        expect(pickupSection().hidden).toBe(false);
        expect(phoneSection().hidden).toBe(false);
    });
});

describe('a szállítók widgetjei', () => {
    test('csak a választott fajta widgetje látszik', async () => {
        await start();
        await choose('gls_locker');

        expect(el('#gls-locker-widget').hidden).toBe(false);
        expect(el('#gls-shop-widget').hidden).toBe(true);
        expect(el('#foxpost-widget').hidden).toBe(true);
    });

    test('egy widget több fajtát is vállalhat', async () => {
        await start();
        await choose('gls_shop');

        expect(el('#gls-shop-widget').hidden).toBe(false);
        expect(el('#gls-locker-widget').hidden).toBe(true);
    });
});

describe('a választott átvételi pont', () => {
    test('a widget eseménye beírja a pontot a mezőbe és a feliratba', async () => {
        await start({ checked: 'gls_locker' });
        let changed = false;
        input().addEventListener('change', () => { changed = true; });

        el('#gls-locker-widget').dispatchEvent(new CustomEvent('carrier:pickup-point-selected', {
            bubbles: true,
            detail: { id: 'HU1012-PLOCKER001', label: 'GLS Automata SHELL Budapest I. Ker.' },
        }));

        expect(input().value).toBe('HU1012-PLOCKER001');
        expect(label().textContent).toBe('GLS Automata SHELL Budapest I. Ker.');
        expect(changed).toBe(true);
    });

    test('felirat nélkül az azonosító jelenik meg', async () => {
        await start({ checked: 'gls_locker' });

        el('#gls-locker-widget').dispatchEvent(new CustomEvent('carrier:pickup-point-selected', {
            bubbles: true,
            detail: { id: 'HU1012-PLOCKER001' },
        }));

        expect(label().textContent).toBe('HU1012-PLOCKER001');
    });

    test('másik fajtára váltva a pont törlődik — egy GLS-automata azonosító nem érvényes csomagpontként', async () => {
        await start({ checked: 'gls_locker', pickupPointId: 'HU1012-PLOCKER001' });
        await choose('gls_shop');

        expect(input().value).toBe('');
        expect(label().textContent).toBe('Még nincs kiválasztott átvételi pont.');
    });

    test('a betöltéskor tárolt pont megmarad', async () => {
        await start({ checked: 'gls_locker', pickupPointId: 'HU1012-PLOCKER001' });

        expect(input().value).toBe('HU1012-PLOCKER001');
    });

    test('egy másik szállítmány eseménye nem ír ebbe a szállítmányba', async () => {
        await start({ checked: 'gls_locker' });
        const outside = document.createElement('div');
        document.querySelector('form').appendChild(outside);

        outside.dispatchEvent(new CustomEvent('carrier:pickup-point-selected', { bubbles: true, detail: { id: 'MASIK' } }));

        expect(input().value).toBe('');
    });
});
