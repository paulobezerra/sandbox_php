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
        $this->assertStringContainsString('<div id="app"', $body);
        $this->assertStringContainsString('http://localhost:5173/@vite/client', $body);
        $this->assertStringContainsString('http://localhost:5173/src/spa/main.js', $body);
    }

    public function testSpaRoutesFallbackReturnsHtmlShell(): void
    {
        $app = $this->getApp();

        $routes = ['/plans', '/about', '/qualquer-outra-pagina-spa'];
        foreach ($routes as $route) {
            $request = $this->createRequest('GET', $route, ['HTTP_ACCEPT' => 'text/html']);
            $response = $app->handle($request);

            $this->assertSame(200, $response->getStatusCode(), "Route failed: {$route}");
            $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));

            $body = (string) $response->getBody();
            $this->assertStringContainsString('<div id="app"', $body);
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

    public function testShellUsesNegotiatedCanonicalLocaleAndCacheHeaders(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('GET', '/', [
            'Accept' => 'text/html',
            'Accept-Language' => 'pt-PT, en;q=0.5',
        ]);
        $response = $app->handle($request);
        $body = (string) $response->getBody();

        self::assertSame('pt-BR', $response->getHeaderLine('Content-Language'));
        self::assertSame('Accept-Language', $response->getHeaderLine('Vary'));
        self::assertStringContainsString('<html lang="pt-BR">', $body);
        self::assertStringContainsString('Sandbox PHP — documentação e blog', $body);
        self::assertStringContainsString('Carregando a aplicação', $body);
    }

    public function testShellDefaultsToEnglishForUnsupportedPreferences(): void
    {
        $app = $this->getApp();
        $request = $this->createRequest('GET', '/', ['Accept-Language' => 'fr-FR, *;q=0.8']);
        $response = $app->handle($request);

        self::assertSame('en', $response->getHeaderLine('Content-Language'));
        self::assertStringContainsString('<html lang="en">', (string) $response->getBody());
        self::assertStringContainsString('Sandbox PHP — documentation and blog', (string) $response->getBody());
    }
}
