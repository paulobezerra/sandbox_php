<?php

declare(strict_types=1);

namespace App\Tests\Integration;

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
        $response = $this->createApp()->handle($this->createRequest('GET', '/docs/openapi.json'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $document = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('/api/health', $document['paths']);
        self::assertArrayNotHasKey('/api/plans', $document['paths']);
        self::assertArrayNotHasKey('/api/pages', $document['paths']);
        self::assertArrayNotHasKey('/api/auth/recovery-codes', $document['paths']);
        self::assertArrayNotHasKey('/api/auth/reset-password', $document['paths']);
        self::assertArrayNotHasKey('429', $document['paths']['/api/auth/login']['post']['responses']);
        self::assertSame('sandbox_session', $document['components']['securitySchemes']['cookieAuth']['name']);
    }

    public function testOpenApiCookieNameFollowsConfiguredSessionCookie(): void
    {
        $config = Config::fromArray([
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'OPENAPI_ENABLED' => 'true',
            'APP_URL' => 'http://localhost:8080',
            'SESSION_COOKIE' => 'project_auth_cookie',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'test',
        ]);
        $response = $this->createApp($config)->handle($this->createRequest('GET', '/docs/openapi.json'));
        $document = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('project_auth_cookie', $document['components']['securitySchemes']['cookieAuth']['name']);
    }

    public function testDocumentationIsHiddenInProductionByDefault(): void
    {
        $productionConfig = Config::fromArray([
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://example.test',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'test',
        ]);
        self::assertFalse($productionConfig->openApiEnabled);

        $spaConfig = Config::fromArray([
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'OPENAPI_ENABLED' => 'false',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'test',
        ]);
        $response = $this->createApp($spaConfig)->handle($this->createRequest(
            'GET',
            '/docs/openapi.json',
            ['Accept' => 'text/html']
        ));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringNotContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }
}
