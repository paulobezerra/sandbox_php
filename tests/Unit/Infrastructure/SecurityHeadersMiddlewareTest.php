<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Bootstrap\Config;
use App\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testDevelopmentCspAllowsOnlyTheConfiguredLocalViteEndpoints(): void
    {
        $config = Config::fromArray([
            'APP_ENV' => 'development',
            'APP_URL' => 'http://localhost:8080',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app_user',
        ]);

        $csp = $this->process($config)->getHeaderLine('Content-Security-Policy');

        self::assertStringContainsString("script-src 'self' 'unsafe-eval' http://localhost:5173", $csp);
        self::assertStringContainsString('ws://localhost:5173', $csp);
        self::assertStringNotContainsString('https://', $csp);
    }

    public function testProductionCspDoesNotAllowViteDevelopmentEndpoints(): void
    {
        $config = Config::fromArray([
            'APP_ENV' => 'production',
            'APP_URL' => 'https://example.test',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'app_user',
        ]);

        $csp = $this->process($config)->getHeaderLine('Content-Security-Policy');

        self::assertStringNotContainsString('5173', $csp);
        self::assertStringContainsString("connect-src 'self';", $csp);
    }

    private function process(Config $config): ResponseInterface
    {
        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        return (new SecurityHeadersMiddleware($config))->process(
            (new ServerRequestFactory())->createServerRequest('GET', '/'),
            $handler
        );
    }
}
