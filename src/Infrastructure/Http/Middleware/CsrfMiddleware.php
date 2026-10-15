<?php
declare(strict_types=1);
namespace App\Infrastructure\Http\Middleware;
use Psr\Http\Message\{ResponseInterface,ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface,RequestHandlerInterface};
use Slim\Psr7\Response;
final class CsrfMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request,RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        // Anonymous extension routes are allowed only when they do not act on session identity.
        $hasAuthenticatedSession = is_int($_SESSION['user_id'] ?? null);
        $usesCookieAuthentication = $hasAuthenticatedSession
            || str_starts_with($path, '/api/auth/')
            || str_starts_with($path, '/api/admin/');
        $isMutation = !in_array(strtoupper($request->getMethod()), ['GET', 'HEAD', 'OPTIONS'], true);
        if ($usesCookieAuthentication && $isMutation) {
            $expected=$_SESSION['csrf']??''; $provided=$request->getHeaderLine('X-CSRF-Token');
            if (!is_string($expected)||$expected===''||!hash_equals($expected,$provided)) {
                $response=new Response(419); $response->getBody()->write('{"error":{"status":419,"message":"Token CSRF inválido ou ausente."}}');
                return $response->withHeader('Content-Type','application/json');
            }
        }
        return $handler->handle($request);
    }
}
