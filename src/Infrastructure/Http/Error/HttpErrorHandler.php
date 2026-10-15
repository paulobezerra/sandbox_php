<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Error;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

readonly class HttpErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory
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
            $message = $this->resolveErrorMessage($exception);

            $payload = [
                'error' => [
                    'status' => $statusCode,
                    'message' => $message,
                ],
            ];

            $response->getBody()->write(
                (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            return $response->withHeader('Content-Type', 'application/json');
        }

        $message = htmlspecialchars(
            $this->resolveErrorMessage($exception),
            ENT_QUOTES,
            'UTF-8'
        );

        $html = sprintf(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Erro %d</title></head><body><h1>Erro %d</h1><p>%s</p></body></html>',
            $statusCode,
            $statusCode,
            $message
        );

        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function resolveErrorMessage(Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getMessage();
        }

        return 'Ocorreu um erro interno no servidor.';
    }
}
