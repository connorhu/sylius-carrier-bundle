<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Support;

use CodeConjure\SyliusCarrierBundle\Twig\CarrierExtension;
use Sylius\TwigHooks\Hook\Normalizer\Name\CompositeNameNormalizer;
use Sylius\TwigHooks\Hook\Normalizer\Prefix\CompositePrefixNormalizer;
use Sylius\TwigHooks\Hook\Renderer\HookRendererInterface;
use Sylius\TwigHooks\Twig\HooksExtension;
use Sylius\TwigHooks\Twig\Runtime\HooksRuntime;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFunction;

/**
 * A csomag sablonjainak futtatható környezete.
 *
 * - `@CodeConjureSyliusCarrier` a csomag `templates/` könyvtára (ugyanaz, amit
 *   a bundle a hostban kap);
 * - a `{% hook %}` a VALÓDI `HooksRuntime`-on fut (névnormalizálás,
 *   `_prefixes`, kontextus-összefésülés) — csak a hookable-ek kikeresése
 *   helyén áll a paraméterként kapott renderelő;
 * - a mezők a Symfony `form_div_layout`-jával, valódi `FormRenderer`-rel
 *   renderelnek;
 * - a `trans` a csomag saját fordítási katalógusán fut, `hu` nyelven;
 * - `strict_variables`: egy elírt változónév kivételt ad, nem üres kimenetet.
 */
final class BundleTwig
{
    /** @param array<string, string> $extraNamespaces névtér => könyvtár */
    public static function environment(HookRendererInterface $hooks, array $extraNamespaces = []): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(self::root() . '/templates', 'CodeConjureSyliusCarrier');
        $loader->addPath(self::root() . '/vendor/symfony/twig-bridge/Resources/views/Form');
        foreach ($extraNamespaces as $namespace => $path) {
            $loader->addPath($path, $namespace);
        }

        $twig = new Environment($loader, ['strict_variables' => true]);
        $twig->addExtension(new HooksExtension());
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension(self::translator()));
        $twig->addExtension(new CarrierExtension());
        // A Sylius és a Symfony UX adja őket a hostban; a sablonok fordításához kellenek.
        $twig->addFunction(new TwigFunction('sylius_test_html_attribute', static fn (): string => '', ['is_safe' => ['html']]));

        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            HooksRuntime::class => static fn (): HooksRuntime => new HooksRuntime(
                $hooks,
                new CompositeNameNormalizer([]),
                new CompositePrefixNormalizer([]),
                true,
            ),
            FormRenderer::class => static fn (): FormRenderer => new FormRenderer(
                new TwigRendererEngine(['form_div_layout.html.twig'], $twig),
            ),
        ]));

        return $twig;
    }

    public static function translator(): Translator
    {
        $translator = new Translator('hu');
        $translator->addLoader('yaml', new YamlFileLoader());
        foreach (glob(self::root() . '/translations/*.hu.yaml') ?: [] as $catalogue) {
            $translator->addResource('yaml', $catalogue, 'hu', basename($catalogue, '.hu.yaml'));
        }

        return $translator;
    }

    private static function root(): string
    {
        return \dirname(__DIR__, 2);
    }
}
