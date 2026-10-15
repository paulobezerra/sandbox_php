<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;

class SpaRoutesTest extends TestCase
{
    public function testRootPathReturnsHtmlWithShell(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('GET', '/', ['HTTP_ACCEPT' => 'text/html']);
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $this->assertStringContainsString('<div id="app">', $body);
        $this->assertStringContainsString('app.js', $body);
    }

    public function testSpaRoutesFallbackReturnsHtmlShell(): void
    {
        $app = $this->getApp();

        $routes = ['/plans', '/about', '/qualquer-outra-pagina-spa'];
        foreach ($routes as $route) {
            $request = $this->createRequest('GET', $route, ['HTTP_ACCEPT' => 'text/html']);
            $response = $app->handle($request);

            $this->assertSame(200, $response->getStatusCode(), "Falha na rota: {$route}");
            $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));

            $body = (string) $response->getBody();
            $this->assertStringContainsString('<div id="app">', $body);
        }
    }

    public function testSecurityHeadersArePresentOnResponse(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        $this->assertTrue($response->hasHeader('Content-Security-Policy'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
    }
}
