<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\I18n\Localization;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class LocaleMiddleware implements MiddlewareInterface
{
    public function __construct(private Localization $localization)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $locale = $this->localization->negotiator->negotiate($request->getHeaderLine('Accept-Language'));
        $response = $handler->handle($request->withAttribute('locale', $locale));
        $vary = array_filter(array_map('trim', explode(',', $response->getHeaderLine('Vary'))));
        if (!in_array('accept-language', array_map('strtolower', $vary), true)) {
            $vary[] = 'Accept-Language';
        }
        return $response->withHeader('Content-Language', $locale)->withHeader('Vary', implode(', ', $vary));
    }
}
