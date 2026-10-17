<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\App;
use App\Bootstrap\Config;
use App\Infrastructure\Database\ConnectionFactory;
use App\Tests\TestCase;

class EnvironmentConfigurationTest extends TestCase
{
    public function testDotenvSettingsOpenDatabaseAndServeAnExistingRoute(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sandbox-env-integration-');
        self::assertNotFalse($path);
        $values = [
            'APP_ENV' => 'testing',
            'DB_HOST' => getenv('DB_HOST') ?: 'mysql',
            'DB_PORT' => getenv('DB_PORT') ?: '3306',
            'DB_DATABASE' => getenv('DB_DATABASE') ?: '',
            'DB_USERNAME' => getenv('DB_USERNAME') ?: '',
            'DB_PASSWORD' => getenv('DB_PASSWORD') ?: '',
        ];
        $contents = implode("\n", array_map(
            static fn (string $name, string $value): string => $name . '=' . self::quoteDotenv($value),
            array_keys($values),
            array_values($values)
        )) . "\n";
        self::assertNotFalse(file_put_contents($path, $contents));

        try {
            $config = Config::fromEnvironment([], $path);
            $pdo = ConnectionFactory::create($config);
            $response = App::create($pdo, $config)->handle($this->createRequest('GET', '/', [
                'Accept' => 'text/html',
            ]));

            self::assertSame(200, $response->getStatusCode());
            self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        } finally {
            unlink($path);
        }
    }

    private static function quoteDotenv(string $value): string
    {
        return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
    }
}
