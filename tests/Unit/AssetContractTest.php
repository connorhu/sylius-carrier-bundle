<?php

declare(strict_types=1);

namespace CodeConjure\SyliusCarrierBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * A sablonok és a Stimulus controller közti szerződés, és a Flex-metaadat,
 * amin a host-oldali regisztráció múlik. Mind néma hibamód: render-hiba
 * nincs, a felület egyszerűen nem reagál.
 *
 * - A Flex csak annak a csomagnak a controllereit veszi fel a host
 *   `controllers.json`-jába, amelyiknek a composer-metaadatában ott a
 *   `symfony-ux` kulcsszó — enélkül minden `composer update` törli a
 *   bejegyzést (a FoxPost plugin #197-e).
 * - A bridge a controllert `name` kulcs nélkül a csomagnévből képzett hosszú
 *   azonosítón regisztrálja (`codeconjure--sylius-carrier-bundle--…`), és a
 *   sablonok rövid `carrier-shipment` azonosítója semmire nem illeszkedne
 *   (a FoxPost plugin #203-a).
 */
final class AssetContractTest extends TestCase
{
    private const string IDENTIFIER = 'carrier-shipment';

    public function testTheComposerMetadataCarriesTheSymfonyUxKeyword(): void
    {
        $keywords = $this->json('composer.json')['keywords'] ?? null;
        self::assertIsArray($keywords);
        self::assertContains('symfony-ux', $keywords);
    }

    public function testTheNpmPackageNameIsTheComposerNameWithALeadingAt(): void
    {
        $name = $this->json('composer.json')['name'] ?? null;
        self::assertIsString($name);
        self::assertSame('@' . $name, $this->json('assets/package.json')['name'] ?? null);
    }

    public function testTheControllerIsRegisteredUnderTheShortIdentifierTheTemplatesUse(): void
    {
        $entry = $this->controllers()[self::IDENTIFIER] ?? null;

        self::assertIsArray($entry);
        self::assertSame(self::IDENTIFIER, $entry['name'] ?? null);
        self::assertSame('eager', $entry['fetch'] ?? null);
        self::assertTrue($entry['enabled'] ?? false);
        $main = $entry['main'] ?? null;
        self::assertIsString($main);
        self::assertFileExists($this->root() . '/assets/' . $main);
    }

    public function testEveryIdentifierInTheTemplatesIsRegistered(): void
    {
        preg_match_all('/data-controller="([^"]+)"/', $this->templates(), $m);

        self::assertNotSame([], $m[1]);
        foreach ($m[1] as $identifiers) {
            foreach (preg_split('/\s+/', $identifiers) ?: [] as $identifier) {
                self::assertArrayHasKey($identifier, $this->controllers());
            }
        }
    }

    public function testEveryTargetTheTemplatesAndTheFormUseIsDeclaredByTheController(): void
    {
        $sources = $this->templates() . file_get_contents($this->root() . '/src/Form/Extension/CheckoutShipmentTypeExtension.php');
        preg_match_all("/data-carrier-shipment-target['\"]?\\s*(?:=>|=)\\s*['\"](\\w+)['\"]/", $sources, $used);
        preg_match('/static targets = \[([^\]]*)\]/', $this->controllerSource(), $declared);
        $declaredTargets = array_map(static fn (string $t): string => trim($t, " '\""), explode(',', $declared[1] ?? ''));

        self::assertSame(
            ['phoneSection', 'pickupPointInput', 'pickupPointLabel', 'pickupPointSection'],
            array_values(array_unique(self::sorted($used[1]))),
        );
        foreach ($used[1] as $target) {
            self::assertContains($target, $declaredTargets);
        }
    }

    public function testTheValueTheScopeTemplatePassesIsDeclaredByTheController(): void
    {
        self::assertStringContainsString('data-carrier-shipment-methods-value=', $this->templates());
        self::assertMatchesRegularExpression('/static values = \{\s*methods: Object\s*\}/', $this->controllerSource());
    }

    public function testTheWidgetEventNameIsTheOneTheDocumentationPublishes(): void
    {
        self::assertStringContainsString("'carrier:pickup-point-selected'", $this->controllerSource());
        self::assertStringContainsString('carrier:pickup-point-selected', (string) file_get_contents($this->root() . '/docs/installation.md'));
    }

    /** @return array<mixed> */
    private function controllers(): array
    {
        $symfony = $this->json('assets/package.json')['symfony'] ?? null;
        self::assertIsArray($symfony);
        $controllers = $symfony['controllers'] ?? null;
        self::assertIsArray($controllers);

        return $controllers;
    }

    /** @return array<mixed> */
    private function json(string $file): array
    {
        $data = json_decode((string) file_get_contents($this->root() . '/' . $file), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }

    private function templates(): string
    {
        $html = '';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root() . '/templates', \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            \assert($file instanceof \SplFileInfo);
            $html .= file_get_contents($file->getPathname());
        }

        return $html;
    }

    private function controllerSource(): string
    {
        return (string) file_get_contents($this->root() . '/assets/controllers/carrier_shipment_controller.js');
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private static function sorted(array $values): array
    {
        sort($values);

        return $values;
    }

    private function root(): string
    {
        return \dirname(__DIR__, 2);
    }
}
