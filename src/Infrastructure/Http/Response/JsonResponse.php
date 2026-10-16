<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Response;

use Psr\Http\Message\ResponseInterface;

class JsonResponse
{
    /**
     * Returns a successful JSON response.
     *
     * @param array<array-key, mixed>|object $data
     */
    public static function success(
        ResponseInterface $response,
        mixed $data = [],
        int $statusCode = 200
    ): ResponseInterface {
        return self::writeJson($response, $data, $statusCode);
    }

    /**
     * Returns a standardized JSON error response.
     *
     * @param array<string, mixed> $details
     */
    public static function error(
        ResponseInterface $response,
        string $message,
        int $statusCode = 400,
        array $details = []
    ): ResponseInterface {
        $payload = [
            'error' => [
                'status' => $statusCode,
                'message' => $message,
            ],
        ];

        if (!empty($details)) {
            $payload['error']['details'] = $details;
        }

        return self::writeJson($response, $payload, $statusCode);
    }

    /**
     * Returns a validation error JSON response (HTTP 422).
     *
     * @param array<string, mixed> $errors
     */
    public static function validationError(
        ResponseInterface $response,
        array $errors,
        string $message = 'Validation error'
    ): ResponseInterface {
        return self::error($response, $message, 422, $errors);
    }

    private static function writeJson(
        ResponseInterface $response,
        mixed $data,
        int $statusCode
    ): ResponseInterface {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response->getBody()->write((string) $json);

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
}
