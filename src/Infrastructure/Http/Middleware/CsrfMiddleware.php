<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\{ResponseInterface,ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface,RequestHandlerInterface};
use Slim\Psr7\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    /** @param list<string> $anonymousMutationExemptions Entries use "METHOD /exact/path". */
    public function __construct(private array $anonymousMutationExemptions = [])
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());
        $isMutation = !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
        $hasAuthenticatedSession = is_int($_SESSION['user_id'] ?? null);
        $isExplicitlyExempt = in_array($method . ' ' . $path, $this->anonymousMutationExemptions, true);

        if ($isMutation && ($hasAuthenticatedSession || !$isExplicitlyExempt)) {
            $expected = $_SESSION['csrf'] ?? '';
            $provided = $request->getHeaderLine('X-CSRF-Token');
            if (!is_string($expected) || $expected === '' || !hash_equals($expected, $provided)) {
                $response = new Response(419);
                $response->getBody()->write('{"error":{"status":419,"message":"Token CSRF inválido ou ausente."}}');
                return $response->withHeader('Content-Type', 'application/json');
            }
        }
        return $handler->handle($request);
    }
}
