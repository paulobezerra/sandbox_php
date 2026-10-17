<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bootstrap;

use App\Bootstrap\Config;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testReadsAndValidatesApplicationAndDatabaseSettings(): void
    {
        $config = Config::fromArray([
            'APP_ENV' => 'development',
            'APP_DEBUG' => 'true',
            'DB_HOST' => 'mysql',
            'DB_PORT' => '3307',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
            'DB_PASSWORD' => 'secret',
        ]);

        self::assertSame('development', $config->appEnv);
        self::assertTrue($config->appDebug);
        self::assertSame('mysql', $config->databaseHost);
        self::assertSame(3307, $config->databasePort);
        self::assertSame('app', $config->databaseName);
        self::assertSame('app-user', $config->databaseUser);
        self::assertSame('secret', $config->databasePassword);
        self::assertSame('utf8mb4', $config->databaseCharset);
    }

    public function testRequiresDatabaseNameAndUser(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DB_DATABASE is required.');

        Config::fromArray([]);
    }

    public function testRejectsInvalidDebugValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('APP_DEBUG must be a valid boolean value.');

        Config::fromArray([
            'APP_DEBUG' => 'maybe',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
        ]);
    }

    public function testRejectsInvalidPort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DB_PORT must be a number between 1 and 65535.');

        Config::fromArray([
            'DB_PORT' => '70000',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
        ]);
    }

    public function testDocumentationIsEnabledInDevelopmentAndDisabledInProductionByDefault(): void
    {
        $database = [
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
        ];

        self::assertTrue(Config::fromArray($database + ['APP_ENV' => 'development'])->openApiEnabled);
        self::assertFalse(Config::fromArray($database + ['APP_ENV' => 'production', 'APP_URL' => 'https://example.test'])->openApiEnabled);
    }

    public function testProductionRequiresPublicHttpsUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('APP_URL must use HTTPS in production.');
        Config::fromArray(['DB_DATABASE' => 'app', 'DB_USERNAME' => 'app-user', 'APP_ENV' => 'production']);
    }

    public function testConfiguresSupportedLocalesAndFallback(): void
    {
        $config = Config::fromArray([
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
            'APP_ENV' => 'testing',
            'SUPPORTED_LOCALES' => 'en, pt-BR',
            'FALLBACK_LOCALE' => 'pt-BR',
        ]);

        self::assertSame(['en', 'pt-BR'], $config->supportedLocales);
        self::assertSame('pt-BR', $config->fallbackLocale);
    }

    public function testRejectsFallbackOutsideSupportedLocales(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FALLBACK_LOCALE must be included in SUPPORTED_LOCALES.');
        Config::fromArray([
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
            'APP_ENV' => 'testing',
            'SUPPORTED_LOCALES' => 'en,pt-BR',
            'FALLBACK_LOCALE' => 'fr',
        ]);
    }

    public function testRejectsCataloguesWithDifferentKeys(): void
    {
        $directory = sys_get_temp_dir() . '/locale_' . bin2hex(random_bytes(6));
        mkdir($directory);
        file_put_contents($directory . '/en.json', '{"home":"Home"}');
        file_put_contents($directory . '/pt-BR.json', '{"home":"Início","extra":"x"}');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('has incomplete keys');
            \App\Infrastructure\I18n\Localization::load(['en', 'pt-BR'], 'en', $directory);
        } finally {
            unlink($directory . '/en.json');
            unlink($directory . '/pt-BR.json');
            rmdir($directory);
        }
    }

}
