<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Bootstrap\Config;
use App\Infrastructure\Http\Middleware\CsrfMiddleware;
use App\Infrastructure\Http\Middleware\LocaleMiddleware;
use App\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;
use App\Infrastructure\Http\Middleware\SessionMiddleware;
use App\Infrastructure\I18n\Localization;
use App\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class MiddlewareUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['user_id'], $_SESSION['csrf']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    public function testCsrfAllowsSafeAndExplicitlyExemptAnonymousRequests(): void
    {
        $handler = $this->handler();
        $middleware = new CsrfMiddleware(['POST /api/auth/register']);
        self::assertSame(200, $middleware->process($this->createRequest('GET', '/api/x'), $handler)->getStatusCode());
        self::assertSame(200, $middleware->process($this->createRequest('POST', '/api/auth/register'), $handler)->getStatusCode());
    }

    public function testCsrfRejectsMissingOrWrongTokensAndAllowsMatchingAuthenticatedToken(): void
    {
        $middleware = new CsrfMiddleware();
        $handler = $this->handler();
        unset($_SESSION['user_id'], $_SESSION['csrf']);
        self::assertSame(419, $middleware->process($this->createRequest('POST', '/api/x'), $handler)->getStatusCode());

        $_SESSION = ['user_id' => 4, 'csrf' => 12];
        self::assertSame(419, $middleware->process($this->createRequest('POST', '/api/x')->withHeader('X-CSRF-Token', '12'), $handler)->getStatusCode());
        $_SESSION['csrf'] = 'valid-token';
        self::assertSame(419, $middleware->process($this->createRequest('POST', '/api/x')->withHeader('X-CSRF-Token', 'wrong'), $handler)->getStatusCode());
        $_SESSION['csrf'] = '';
        self::assertSame(419, $middleware->process($this->createRequest('POST', '/api/x'), $handler)->getStatusCode());
        $_SESSION['csrf'] = 'valid-token';
        self::assertSame(200, $middleware->process($this->createRequest('POST', '/api/x')->withHeader('X-CSRF-Token', 'valid-token'), $handler)->getStatusCode());
    }

    public function testLocaleMiddlewareSetsNegotiatedHeadersAndPreservesVary(): void
    {
        $localization = Localization::load(['en', 'pt-BR'], 'en');
        $handler = $this->handler((new Response())->withHeader('Vary', 'Origin, accept-language'));
        $request = $this->createRequest('GET', '/')->withHeader('Accept-Language', 'pt-BR');
        $response = (new LocaleMiddleware($localization))->process($request, $handler);

        self::assertSame('pt-BR', $response->getHeaderLine('Content-Language'));
        self::assertSame('Origin, accept-language', $response->getHeaderLine('Vary'));

        $withoutVary = (new LocaleMiddleware($localization))->process($this->createRequest('GET', '/'), $this->handler());
        self::assertSame('Accept-Language', $withoutVary->getHeaderLine('Vary'));
    }

    public function testSessionMiddlewareStartsOnceAndUsesConfiguredCookieName(): void
    {
        $config = Config::fromArray(['APP_ENV' => 'testing', 'DB_DATABASE' => 'app', 'DB_USERNAME' => 'user', 'SESSION_COOKIE' => 'test_cookie']);
        $middleware = new SessionMiddleware($config);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $response = $middleware->process($this->createRequest('GET', '/'), $this->handler());
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('test_cookie', session_name());
        self::assertSame(200, $middleware->process($this->createRequest('GET', '/'), $this->handler())->getStatusCode());

        session_destroy();
        $secure = Config::fromArray(['APP_ENV' => 'production', 'APP_URL' => 'https://example.test', 'DB_DATABASE' => 'app', 'DB_USERNAME' => 'user']);
        self::assertSame(200, (new SessionMiddleware($secure))->process($this->createRequest('GET', '/'), $this->handler())->getStatusCode());
    }

    public function testSecurityMiddlewareAddsSharedHeadersForEveryEnvironment(): void
    {
        $config = Config::fromArray(['APP_ENV' => 'testing', 'DB_DATABASE' => 'app', 'DB_USERNAME' => 'user']);
        $response = (new SecurityHeadersMiddleware($config))->process($this->createRequest('GET', '/'), $this->handler());
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        self::assertSame('0', $response->getHeaderLine('X-XSS-Protection'));
    }

    private function handler(?ResponseInterface $response = null): RequestHandlerInterface
    {
        return new class ($response ?? new Response()) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }
}
