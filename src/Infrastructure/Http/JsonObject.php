<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use JsonException;
use Psr\Http\Message\ServerRequestInterface;

final class JsonObject
{
    /** @return array<string, mixed> */
    public static function fromRequest(ServerRequestInterface $request): array
    {
        $contentType = strtolower(trim(explode(';', $request->getHeaderLine('Content-Type'))[0]));
        if ($contentType !== 'application/json') {
            throw new JsonRequestException($request, 'invalid_json_object');
        }
        try {
            $decoded = json_decode((string) $request->getBody(), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new JsonRequestException($request, 'malformed_json');
        }
        if (!is_object($decoded)) {
            throw new JsonRequestException($request, 'invalid_json_object');
        }
        return get_object_vars($decoded);
    }
}
