<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Bootstrap\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $csp = "default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self'; form-action 'self'";
        if ($this->config->appEnv === 'development') {
            $csp = str_replace(
                "script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
                "script-src 'self' 'unsafe-eval' http://localhost:5173; style-src 'self' 'unsafe-inline' http://localhost:5173;",
                $csp
            );
            $csp = str_replace(
                "connect-src 'self';",
                "connect-src 'self' http://localhost:5173 ws://localhost:5173;",
                $csp
            );
        }

        return $response
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->withHeader('X-XSS-Protection', '0');
    }
}
