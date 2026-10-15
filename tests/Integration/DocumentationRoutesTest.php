<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\App;
use App\Bootstrap\Config;
use App\Tests\TestCase;

class DocumentationRoutesTest extends TestCase
{
    public function testDevelopmentDocumentationUiLoadsLocalAssets(): void
    {
        $response = $this->getApp()->handle($this->createRequest('GET', '/docs'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString(
            '/assets/vendor/swagger-ui/swagger-ui-bundle.js',
            (string) $response->getBody()
        );
    }

    public function testOpenApiDocumentIsServedAsJson(): void
    {
        $response = App::create()->handle($this->createRequest('GET', '/docs/openapi.json'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $document = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('/api/health', $document['paths']);
        self::assertArrayNotHasKey('/api/plans', $document['paths']);
    }

    public function testDocumentationIsHiddenInProductionByDefault(): void
    {
        $environment = Config::fromEnvironment();
        $config = Config::fromArray([
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://example.test',
            'DB_HOST' => $environment->databaseHost,
            'DB_PORT' => (string) $environment->databasePort,
            'DB_DATABASE' => $environment->databaseName,
            'DB_USERNAME' => $environment->databaseUser,
            'DB_PASSWORD' => $environment->databasePassword,
            'DB_CHARSET' => $environment->databaseCharset,
        ]);
        $response = App::create(config: $config)->handle($this->createRequest('GET', '/docs/openapi.json'));

        self::assertFalse($config->openApiEnabled);
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringNotContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }
}
