<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Support;

use Sylius\TwigHooks\Bag\DataBag;
use Sylius\TwigHooks\Bag\ScalarDataBag;
use Sylius\TwigHooks\Hook\Metadata\HookMetadata;
use Sylius\TwigHooks\Hookable\Metadata\HookableMetadata;

/**
 * Egy hookable sablon renderelési környezete, ahogy a Twig Hooks adja: a
 * kontextus a `hookable_metadata.context`-ben, az előtag pedig a hook NEVE,
 * ami a hookable-t renderelte — a beágyazott `{% hook 'x' %}` ebből képez
 * `<hook>.x` nevet.
 */
final class Hookable
{
    /** @param array<string, mixed> $context */
    public static function metadata(string $renderedByHook, array $context): HookableMetadata
    {
        return new HookableMetadata(
            new HookMetadata($renderedByHook, new DataBag($context)),
            new DataBag($context),
            new ScalarDataBag([]),
            [$renderedByHook],
        );
    }
}
