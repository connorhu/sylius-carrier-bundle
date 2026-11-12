import { Controller } from '@hotwired/stimulus';

/**
 * Egy szállítmány hatóköre a pénztár 2. lépésén (a csomag
 * `shipments.html.twig` wrapperén).
 *
 * - A `methods` érték a szállítmány módjainak szállító-semleges leírása
 *   (`codeconjure_carrier_shipping_methods`): módkód → { deliveryKind,
 *   requiresRecipientPhone, pickupPoint }.
 * - A választott mód szerint mutatja a telefon- és az átvételi pont
 *   szekciót, és az átvételi pont szekción belül csak azt a widgetet, amelyik
 *   `data-carrier-delivery-kinds` listája tartalmazza a mód fajtáját.
 * - A widgetek a `carrier:pickup-point-selected` eseménnyel
 *   (`detail: { id, label? }`) adják át a választott pontot; a rejtett mezőbe
 *   ez a controller ír, és `change` eseményt küld rajta.
 * - Másik fajtára váltva a pontot törli: egy szállító pontazonosítója egy
 *   másik szállítónál (vagy másik fajtánál) érvénytelen.
 *
 * A KÖTELEZŐSÉGET a szerveroldal dönti el; ez a controller csak mutat/rejt.
 */
export default class extends Controller {
    static targets = ['phoneSection', 'pickupPointSection', 'pickupPointInput', 'pickupPointLabel'];

    static values = { methods: Object };

    connect() {
        this.currentKind = this.selectedMethod()?.deliveryKind ?? null;
        this.onChange = (event) => {
            if (event.target instanceof HTMLInputElement && event.target.type === 'radio') {
                this.update();
            }
        };
        this.onPickupPointSelected = (event) => this.selectPickupPoint(event.detail ?? {});
        this.element.addEventListener('change', this.onChange);
        this.element.addEventListener('carrier:pickup-point-selected', this.onPickupPointSelected);
        this.render(this.selectedMethod());
    }

    disconnect() {
        this.element.removeEventListener('change', this.onChange);
        this.element.removeEventListener('carrier:pickup-point-selected', this.onPickupPointSelected);
    }

    update() {
        const method = this.selectedMethod();
        const kind = method?.deliveryKind ?? null;
        if (kind !== this.currentKind) {
            this.clearPickupPoint();
            this.currentKind = kind;
        }
        this.render(method);
    }

    selectedMethod() {
        const checked = [...this.element.querySelectorAll('input[type="radio"]:checked')]
            .find((radio) => Object.hasOwn(this.methodsValue, radio.value));

        return checked ? this.methodsValue[checked.value] : null;
    }

    render(method) {
        if (this.hasPhoneSectionTarget) {
            this.phoneSectionTarget.hidden = !(method?.requiresRecipientPhone ?? false);
        }
        if (this.hasPickupPointSectionTarget) {
            this.pickupPointSectionTarget.hidden = !(method?.pickupPoint ?? false);
            for (const widget of this.pickupPointSectionTarget.querySelectorAll('[data-carrier-delivery-kinds]')) {
                const kinds = widget.dataset.carrierDeliveryKinds.split(/\s+/).filter(Boolean);
                widget.hidden = !(method?.pickupPoint && kinds.includes(method.deliveryKind));
            }
        }
    }

    selectPickupPoint({ id, label }) {
        if (!this.hasPickupPointInputTarget || typeof id !== 'string') {
            return;
        }
        this.pickupPointInputTarget.value = id;
        if (this.hasPickupPointLabelTarget) {
            this.pickupPointLabelTarget.textContent = label || id;
        }
        this.pickupPointInputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    clearPickupPoint() {
        if (this.hasPickupPointInputTarget) {
            this.pickupPointInputTarget.value = '';
        }
        if (this.hasPickupPointLabelTarget) {
            this.pickupPointLabelTarget.textContent = this.pickupPointLabelTarget.dataset.placeholder ?? '';
        }
    }
}
