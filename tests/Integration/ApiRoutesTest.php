<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use App\Bootstrap\App;

class ApiRoutesTest extends TestCase
{
    public function testSkeletonBootsWithoutLegacyPlanRoutes(): void
    {
        $response = App::create()->handle($this->createRequest('GET', '/api/plans'));

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testBootstrapCanRunWithoutCmsOrPlanModules(): void
    {
        $app = App::create(modules: []);
        $health = $app->handle($this->createRequest('GET', '/api/health'));
        $posts = $app->handle($this->createRequest('GET', '/api/posts'));

        self::assertSame(200, $health->getStatusCode());
        self::assertSame(404, $posts->getStatusCode());
    }

    public function testHealthCheckReturnsSuccessJsonResponse(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('GET', '/api/health');
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertSame('ok', $body['status']);
        $this->assertSame('connected', $body['database']);
    }

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

    public function testNumericRouteParameterRejectsNonNumericValue(): void
    {
        $response = $this->getApp()->handle($this->createRequest('GET', '/api/plans/not-a-number'));

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testMethodNotAllowedOnApiRouteReturns405Json(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('POST', '/api/health');
        $response = $app->handle($request);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        $this->assertSame(405, $body['error']['status']);
    }
}
