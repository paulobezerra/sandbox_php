<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Error;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;
use App\Infrastructure\I18n\Localization;
use App\Infrastructure\Http\JsonRequestException;

readonly class HttpErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ?Localization $localization = null
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $statusCode = 500;
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
        }

        if ($logErrors) {
            $logMessage = sprintf(
                '[%s] %s %s -> %d',
                get_class($exception),
                $request->getMethod(),
                $request->getUri()->getPath(),
                $statusCode
            );
            if ($logErrorDetails) {
                $logMessage .= sprintf(
                    " in %s:%d\nStack trace:\n%s",
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getTraceAsString()
                );
            }
            error_log($logMessage);
        }

        $response = $this->responseFactory->createResponse($statusCode);

        $path = $request->getUri()->getPath();
        $accept = $request->getHeaderLine('Accept');
        $isApi = str_starts_with($path, '/api') || str_contains($accept, 'application/json');

        if ($isApi) {
            $locale = $this->locale($request);
            $message = $this->resolveErrorMessage($exception, $locale);

            $payload = [
                'error' => [
                    'status' => $statusCode,
                    'message' => $message,
                ],
            ];

            $response->getBody()->write(
                (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            return $this->localized($response->withHeader('Content-Type', 'application/json'), $locale);
        }

        $locale = $this->locale($request);
        $message = htmlspecialchars(
            $this->resolveErrorMessage($exception, $locale),
            ENT_QUOTES,
            'UTF-8'
        );

        $html = sprintf(
            '<!DOCTYPE html><html lang="%s"><head><meta charset="utf-8"><title>%s %d</title></head><body><h1>%s %d</h1><p>%s</p></body></html>',
            htmlspecialchars($locale, ENT_QUOTES, 'UTF-8'),
            $locale === 'pt-BR' ? 'Erro' : 'Error',
            $statusCode,
            $locale === 'pt-BR' ? 'Erro' : 'Error',
            $statusCode,
            $message
        );

        $response->getBody()->write($html);

        return $this->localized($response->withHeader('Content-Type', 'text/html; charset=utf-8'), $locale);
    }

    private function resolveErrorMessage(Throwable $exception, string $locale): string
    {
        if ($exception instanceof JsonRequestException) {
            $key = $exception->reason === 'malformed_json' ? 'http.malformedJson' : 'http.invalidJsonObject';
            return $this->localization?->translate($locale, $key) ?? 'The JSON request is invalid.';
        }
        $key = match ($exception instanceof HttpException ? $exception->getCode() : 500) {
            400 => 'error.badRequest',
            404 => 'error.notFound',
            405 => 'error.methodNotAllowed',
            default => 'error.generic',
        };
        if ($this->localization !== null) {
            return $this->localization->translate($locale, $key);
        }
        return $locale === 'pt-BR' ? match ($key) {
            'error.badRequest' => 'The request could not be understood.',
            'error.notFound' => 'The requested resource or page was not found.',
            'error.methodNotAllowed' => 'This request method is not allowed.',
            default => 'An internal server error occurred.',
        } : match ($key) {
            'error.badRequest' => 'The request could not be understood.',
            'error.notFound' => 'The requested resource or page was not found.',
            'error.methodNotAllowed' => 'This request method is not allowed.',
            default => 'An internal server error occurred.',
        };
    }

    private function locale(ServerRequestInterface $request): string
    {
        return $this->localization?->negotiator->negotiate($request->getHeaderLine('Accept-Language')) ?? 'en';
    }

    private function localized(ResponseInterface $response, string $locale): ResponseInterface
    {
        $vary = array_filter(array_map('trim', explode(',', $response->getHeaderLine('Vary'))));
        if (!in_array('accept-language', array_map('strtolower', $vary), true)) {
            $vary[] = 'Accept-Language';
        }
        return $response->withHeader('Content-Language', $locale)->withHeader('Vary', implode(', ', $vary));
    }
}
