<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Unit\Twig;

use CodeConjure\SyliusCarrierBundle\Tests\Support\CarrierShippingMethod;
use CodeConjure\SyliusCarrierBundle\Twig\CarrierExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ShippingMethod;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;

final class CarrierExtensionTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function hookOutputs(): iterable
    {
        yield 'üres' => ['', true];
        yield 'whitespace' => ["  \n\t ", true];
        yield 'csak a Twig Hooks debug-kommentjei' => [
            "<!-- BEGIN HOOK | name: \"a.b\" -->\n<!--  END HOOK  | name: \"a.b\" -->",
            true,
        ];
        yield 'hookable-szintű kommentek is, tartalom nélkül' => [
            "<!-- BEGIN HOOK -->\n<!-- BEGIN HOOKABLE | hook: \"a.b\" -->\n\n<!--  END HOOKABLE -->\n<!--  END HOOK -->",
            true,
        ];
        yield 'tartalom kommentek között' => ["<!-- BEGIN HOOK -->\n<button>GLS</button>\n<!-- END HOOK -->", false];
        yield 'tartalom komment nélkül' => ['<span></span>', false];
    }

    #[DataProvider('hookOutputs')]
    public function testHookOutputIsBlankIgnoresDebugComments(string $html, bool $blank): void
    {
        self::assertSame($blank, CarrierExtension::isBlankHookOutput($html));
    }

    public function testTheMethodsMapDescribesEveryChoiceByItsCode(): void
    {
        $methods = CarrierExtension::shippingMethods($this->methodField(
            new CarrierShippingMethod(code: 'gls_locker', deliveryKindSlug: 'gls_parcel_locker', requiresRecipientPhone: true, deliversToPickupPoint: true),
            new CarrierShippingMethod(code: 'personal_pickup'),
        ));

        self::assertSame([
            'gls_locker' => ['deliveryKind' => 'gls_parcel_locker', 'requiresRecipientPhone' => true, 'pickupPoint' => true],
            'personal_pickup' => ['deliveryKind' => null, 'requiresRecipientPhone' => false, 'pickupPoint' => false],
        ], $methods);
    }

    public function testAMethodOutsideTheContractNeedsNothing(): void
    {
        $plain = new ShippingMethod();
        $plain->setCode('plain');

        self::assertSame(
            ['plain' => ['deliveryKind' => null, 'requiresRecipientPhone' => false, 'pickupPoint' => false]],
            CarrierExtension::shippingMethods($this->methodField($plain)),
        );
    }

    public function testBothFunctionsAreRegistered(): void
    {
        $names = array_map(static fn ($f): string => $f->getName(), (new CarrierExtension())->getFunctions());

        self::assertSame(['codeconjure_hook_output_is_blank', 'codeconjure_carrier_shipping_methods'], $names);
    }

    /**
     * A Sylius `ShippingMethodChoiceType` nézete: a választék értéke a mód
     * kódja, az adata maga a mód.
     */
    private function methodField(ShippingMethod ...$methods): FormView
    {
        $view = new FormView();
        $view->vars['choices'] = array_map(
            static fn (ShippingMethod $m): ChoiceView => new ChoiceView($m, (string) $m->getCode(), (string) $m->getCode()),
            $methods,
        );

        return $view;
    }
}
