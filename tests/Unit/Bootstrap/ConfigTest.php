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
        $this->expectExceptionMessage('DB_DATABASE é obrigatório.');

        Config::fromArray([]);
    }

    public function testRejectsInvalidDebugValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('APP_DEBUG deve ser um valor booleano válido.');

        Config::fromArray([
            'APP_DEBUG' => 'maybe',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
        ]);
    }

    public function testRejectsInvalidPort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DB_PORT deve ser um número entre 1 e 65535.');

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
        $this->expectExceptionMessage('APP_URL deve usar HTTPS em produção.');
        Config::fromArray(['DB_DATABASE' => 'app', 'DB_USERNAME' => 'app-user', 'APP_ENV' => 'production']);
    }

    public function testTrustedProxyNetworksAreParsedAndValidated(): void
    {
        $config = Config::fromArray([
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
            'APP_ENV' => 'testing',
            'TRUSTED_PROXIES' => '10.0.0.0/8, 2001:db8::1',
        ]);

        self::assertSame(['10.0.0.0/8', '2001:db8::1'], $config->trustedProxies);
    }

    public function testRejectsInvalidTrustedProxyNetwork(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TRUSTED_PROXIES deve conter IPs ou redes CIDR válidos separados por vírgula.');

        Config::fromArray([
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app-user',
            'APP_ENV' => 'testing',
            'TRUSTED_PROXIES' => '10.0.0.0/99',
        ]);
    }
}
