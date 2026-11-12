<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * A service-konfig PHP-fájlban él, nem XML-ben: a Symfony 8 kivezette az
 * `XmlFileLoader`-t (a FoxPost plugin #205-ös tanulsága).
 */
final class CodeConjureSyliusCarrierExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.php');
    }

    /**
     * Csak ÚJ hookable-neveket vezet be — egy már foglalt (Sylius saját)
     * kulcsot a `prepend()` nem tud átírni, azok a host telepítési lépései.
     */
    public function prepend(ContainerBuilder $container): void
    {
        $parsed = Yaml::parseFile(\dirname(__DIR__) . '/Resources/config/app/hooks.yaml');
        \assert(\is_array($parsed) && \is_array($parsed['sylius_twig_hooks'] ?? null));

        /** @var array<string, mixed> $config */
        $config = $parsed['sylius_twig_hooks'];
        $container->prependExtensionConfig('sylius_twig_hooks', $config);
    }
}
