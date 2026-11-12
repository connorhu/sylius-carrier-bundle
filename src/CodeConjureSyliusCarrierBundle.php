<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * A Twig-névtér a bundle nevéből: `@CodeConjureSyliusCarrier` → `templates/`.
 * A `translations/` könyvtárat a Symfony a `getPath()` alapján találja meg.
 */
final class CodeConjureSyliusCarrierBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
