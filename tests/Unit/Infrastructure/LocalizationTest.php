<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\I18n\Localization;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LocalizationTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/localization-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*.json') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testTranslatesRequestedLocaleAndFallsBackForMissingKey(): void
    {
        $this->catalogues(['en' => ['greeting' => 'Hello', 'fallback' => 'Fallback'], 'pt-BR' => ['greeting' => 'Olá', 'fallback' => 'Fallback']]);
        $localization = Localization::load(['en', 'pt-BR'], 'en', $this->directory);

        self::assertSame('Olá', $localization->translate('pt-BR', 'greeting'));
        self::assertSame('Fallback', $localization->translate('pt-BR', 'fallback'));
    }

    public function testRejectsMissingInvalidAndNonTextCatalogues(): void
    {
        foreach ([
            ['en' => null],
            ['en' => '{broken'],
            ['en' => '{"label": 3}'],
        ] as $catalogue) {
            $this->clearCatalogues();
            foreach ($catalogue as $locale => $contents) {
                if ($contents !== null) {
                    file_put_contents($this->directory . '/' . $locale . '.json', $contents);
                }
            }
            try {
                Localization::load(['en'], 'en', $this->directory);
                self::fail('Invalid catalogue should be rejected.');
            } catch (RuntimeException $exception) {
                self::assertStringContainsString('catalogue', $exception->getMessage());
            }
        }
    }

    public function testRejectsMissingTranslationKey(): void
    {
        $this->catalogues(['en' => ['present' => 'Here']]);
        $localization = Localization::load(['en'], 'en', $this->directory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Translation key "absent" is missing.');
        $localization->translate('en', 'absent');
    }

    /** @param array<string, array<string, string>> $catalogues */
    private function catalogues(array $catalogues): void
    {
        foreach ($catalogues as $locale => $catalogue) {
            file_put_contents($this->directory . '/' . $locale . '.json', json_encode($catalogue, JSON_THROW_ON_ERROR));
        }
    }

    private function clearCatalogues(): void
    {
        foreach (glob($this->directory . '/*.json') ?: [] as $file) {
            unlink($file);
        }
    }
}
