<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bootstrap;

use App\Bootstrap\Config;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testLoadsApplicationSettingsFromDotenvFile(): void
    {
        $path = $this->temporaryEnvironmentFile(<<<'ENV'
            APP_ENV=production
            APP_DEBUG=false
            OPENAPI_ENABLED=false
            DB_HOST=db.example.test
            DB_DATABASE=shared_app
            DB_USERNAME=shared_user
            DB_PASSWORD='p#ss=$word=secret' # quoted special characters stay intact
            APP_URL=https://www.example.test
            ENV);

        try {
            $config = Config::fromEnvironment([], $path);

            self::assertSame('production', $config->appEnv);
            self::assertSame('db.example.test', $config->databaseHost);
            self::assertSame('p#ss=$word=secret', $config->databasePassword);
            self::assertFalse($config->openApiEnabled);
        } finally {
            unlink($path);
        }
    }

    public function testReadsQuotedEmptyDotenvValues(): void
    {
        $path = $this->temporaryEnvironmentFile("APP_ENV=testing\nDB_DATABASE=app\nDB_USERNAME=app_user\nDB_PASSWORD=\"\"\n");

        try {
            self::assertSame('', Config::fromEnvironment([], $path)->databasePassword);
        } finally {
            unlink($path);
        }
    }

    public function testEnvironmentOnlyConfigurationWorksWhenDotenvFileIsAbsent(): void
    {
        $config = Config::fromEnvironment([
            'DB_DATABASE' => 'process_database',
            'DB_USERNAME' => 'process_user',
            'APP_ENV' => 'testing',
        ], sys_get_temp_dir() . '/missing-sandbox-' . bin2hex(random_bytes(6)) . '.env');

        self::assertSame('process_database', $config->databaseName);
        self::assertSame('process_user', $config->databaseUser);
    }

    public function testEmptyProcessDatabaseNameOverridesFileAndUsesExistingValidation(): void
    {
        $path = $this->temporaryEnvironmentFile("DB_DATABASE=file_database\nDB_USERNAME=file_user\n");

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('DB_DATABASE is required.');
            Config::fromEnvironment(['DB_DATABASE' => ''], $path);
        } finally {
            unlink($path);
        }
    }

    public function testRejectsPresentNonFileConfigurationPath(): void
    {
        $directory = sys_get_temp_dir() . '/sandbox-env-dir-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($directory));

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Unable to read configuration file');
            Config::fromEnvironment([], $directory);
        } finally {
            rmdir($directory);
        }
    }

    public function testProcessValuesOverrideFileValuesPerKeyIncludingEmptyValues(): void
    {
        $path = $this->temporaryEnvironmentFile(<<<'ENV'
            DB_DATABASE=file_database
            DB_USERNAME=file_user
            DB_PASSWORD=file_secret
            APP_ENV=production
            APP_URL=https://www.example.test
            ENV);

        try {
            $config = Config::fromEnvironment([
                'DB_DATABASE' => 'process_database',
                'DB_PASSWORD' => '',
            ], $path);

            self::assertSame('process_database', $config->databaseName);
            self::assertSame('file_user', $config->databaseUser);
            self::assertSame('', $config->databasePassword);
        } finally {
            unlink($path);
        }
    }

    public function testRejectsMalformedDotenvWithoutExposingItsContents(): void
    {
        $secret = 'raw-parser-line-secret';
        $path = $this->temporaryEnvironmentFile("DB_DATABASE=app\n{$secret}\n");

        try {
            Config::fromEnvironment([], $path);
            self::fail('Malformed dotenv input should fail.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString(basename($path), $exception->getMessage());
            self::assertStringNotContainsString($secret, $exception->getMessage());
            self::assertStringNotContainsString('DB_DATABASE=app', $exception->getMessage());
        } finally {
            unlink($path);
        }
    }

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

    private function temporaryEnvironmentFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sandbox-env-');
        self::assertNotFalse($path);
        self::assertNotFalse(file_put_contents($path, $contents));

        return $path;
    }

}
