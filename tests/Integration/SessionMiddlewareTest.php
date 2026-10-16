<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\Config;
use App\Infrastructure\Http\Middleware\SessionMiddleware;
use App\Tests\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class SessionMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        parent::tearDown();
    }

    public function testSecureCookieUsesConfiguredPublicHttpsUrlAndIgnoresForwardedHeaders(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $middleware = new SessionMiddleware($this->config('https://example.test'));
        $observed = (object) ['secure' => null];
        $handler = new class ($observed) implements RequestHandlerInterface {
            public function __construct(private object $observed)
            {
            }
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->observed->secure = session_get_cookie_params()['secure'];
                return new Response();
            }
        };
        $request = $this->createRequest('GET', 'http://internal.test/health', ['X-Forwarded-Proto' => 'http']);
        $middleware->process($request, $handler);
        self::assertTrue($observed->secure);
    }

    public function testUntrustedForwardedHttpsDoesNotEnableSecureCookieForLocalHttp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $middleware = new SessionMiddleware($this->config('http://localhost:8080'));
        $observed = (object) ['secure' => null];
        $handler = new class ($observed) implements RequestHandlerInterface {
            public function __construct(private object $observed)
            {
            }
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->observed->secure = session_get_cookie_params()['secure'];
                return new Response();
            }
        };
        $request = $this->createRequest('GET', 'http://localhost:8080/', ['X-Forwarded-Proto' => 'https']);
        $middleware->process($request, $handler);
        self::assertFalse($observed->secure);
    }

    public function testSessionIdentityDoesNotDependOnCredentialVersionStorage(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_id('version-check-' . bin2hex(random_bytes(8)));
        session_start();
        $_SESSION = ['user_id' => 81, 'csrf' => 'old-token'];
        $observed = (object) ['session' => null];
        $handler = new class ($observed) implements RequestHandlerInterface {
            public function __construct(private object $observed)
            {
            }
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->observed->session = $_SESSION;
                return new Response();
            }
        };
        (new SessionMiddleware($this->config('http://localhost:8080')))->process($this->createRequest('GET', '/'), $handler);
        self::assertSame(['user_id' => 81, 'csrf' => 'old-token'], $observed->session);
    }

    private function config(string $appUrl): Config
    {
        return Config::fromArray(['DB_DATABASE' => 'test', 'DB_USERNAME' => 'test', 'APP_ENV' => 'testing', 'APP_URL' => $appUrl]);
    }
}
