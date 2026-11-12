<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Twig;

use CodeConjure\SyliusCarrierBundle\Model\CarrierShippingMethodInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Mindkét függvény statikus: egyikhez sem kell szolgáltatás, és így a
 * sablonokba került szabályok konténer nélkül tesztelhetők.
 */
final class CarrierExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('codeconjure_hook_output_is_blank', [self::class, 'isBlankHookOutput']),
            new TwigFunction('codeconjure_carrier_shipping_methods', [self::class, 'shippingMethods']),
        ];
    }

    /**
     * Egy hook kimenete akkor üres, ha a HTML-kommentek levágása után csak
     * whitespace marad. `kernel.debug` mellett a Twig Hooks egy üres hookot is
     * `<!-- BEGIN HOOK … -->` / `<!-- END HOOK … -->` párral (és hookable-
     * szinten is kommentekkel) ad vissza — enélkül a vizsgálat nélkül egy
     * „ha senki nem rajzolt, jöjjön az alap" fallback dev-ben némán halott.
     */
    public static function isBlankHookOutput(string $html): bool
    {
        return '' === trim((string) preg_replace('/<!--.*?-->/s', '', $html));
    }

    /**
     * A szállítmány `method` mezőjének választéka, módkód szerint, a
     * `carrier-shipment` Stimulus controller `methods` értékének formájában.
     * A szerződést nem ismerő mód semmit nem kér.
     *
     * @return array<string, array{deliveryKind: ?string, requiresRecipientPhone: bool, pickupPoint: bool}>
     */
    public static function shippingMethods(FormView $methodField): array
    {
        $choices = $methodField->vars['choices'] ?? [];
        \assert(\is_array($choices));

        $methods = [];
        foreach ($choices as $choice) {
            if (!$choice instanceof ChoiceView || !$choice->data instanceof ShippingMethodInterface) {
                continue;
            }
            $method = $choice->data;
            $carrier = $method instanceof CarrierShippingMethodInterface ? $method : null;

            $methods[$choice->value] = [
                'deliveryKind' => $carrier?->getDeliveryKindSlug(),
                'requiresRecipientPhone' => $carrier?->requiresRecipientPhone() ?? false,
                'pickupPoint' => $carrier?->deliversToPickupPoint() ?? false,
            ];
        }

        return $methods;
    }
}
