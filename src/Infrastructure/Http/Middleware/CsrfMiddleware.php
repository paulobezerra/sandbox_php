<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\Http\Response\JsonResponse;
use App\Infrastructure\I18n\Localization;
use Psr\Http\Message\{ResponseInterface,ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface,RequestHandlerInterface};
use Slim\Psr7\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    /** @param list<string> $anonymousMutationExemptions Entries use "METHOD /exact/path". */
    public function __construct(private array $anonymousMutationExemptions = [], private ?Localization $localization = null)
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
                $locale = $request->getAttribute('locale');
                $locale = is_string($locale) ? $locale : $this->localization?->negotiator->negotiate($request->getHeaderLine('Accept-Language')) ?? 'en';
                $message = $this->localization?->translate($locale, 'csrf.invalid') ?? 'The CSRF token is missing or invalid.';
                return JsonResponse::error(new Response(), $message, 419);
            }
        }
        return $handler->handle($request);
    }
}
