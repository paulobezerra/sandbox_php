<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use App\Bootstrap\App;

class ApiRoutesTest extends TestCase
{
    public function testSkeletonBootsWithoutLegacyPlanRoutes(): void
    {
        $app = App::create();
        foreach ([
            '/api/plans', '/api/pages', '/api/pages/home', '/api/health',
            '/api/admin/posts', '/api/admin/pages',
        ] as $path) {
            $response = $app->handle($this->createRequest('GET', $path));
            self::assertSame(404, $response->getStatusCode(), $path);
            self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'), $path);
        }
    }

    public function testBootstrapRegistersPostsWithoutDynamicModules(): void
    {
        $app = App::create();
        $removed = $app->handle($this->createRequest('GET', '/api/health'));
        $posts = $app->handle($this->createRequest('GET', '/api/posts'));

        self::assertSame(404, $removed->getStatusCode());
        self::assertStringContainsString('application/json', $removed->getHeaderLine('Content-Type'));
        self::assertSame(200, $posts->getStatusCode());
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

    public function testNumericRouteParameterRejectsNonNumericValue(): void
    {
        $response = $this->getApp()->handle($this->createRequest('GET', '/api/posts/not-a-number'));

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
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
