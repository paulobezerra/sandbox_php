<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    public function testNonExistentApiRouteReturns404Json(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('GET', '/api/rota-inexistente');
        $response = $app->handle($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        $this->assertSame(404, $body['error']['status']);
    }

    public function testNonExistentApiMutationStillReturns404BeforeCsrfValidation(): void
    {
        $response = $this->getApp()->handle($this->createRequest('POST', '/api/rota-inexistente'));

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testApiRootDoesNotFallThroughToSpa(): void
    {
        $response = $this->getApp()->handle($this->createRequest('GET', '/api'));

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testPathBeginningWithApiButOutsideApiNamespaceCanUseSpa(): void
    {
        $response = $this->getApp()->handle($this->createRequest(
            'GET',
            '/apiary',
            ['HTTP_ACCEPT' => 'text/html']
        ));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testMethodNotAllowedOnApiRouteReturns405Json(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('POST', '/api/posts');
        $response = $app->handle($request);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        $this->assertSame(405, $body['error']['status']);
    }
}
