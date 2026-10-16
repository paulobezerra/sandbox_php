<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use JsonException;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

final class JsonObject
{
    /** @return array<string, mixed> */
    public static function fromRequest(ServerRequestInterface $request): array
    {
        $contentType = strtolower(trim(explode(';', $request->getHeaderLine('Content-Type'))[0]));
        if ($contentType !== 'application/json') {
            throw new HttpBadRequestException($request, 'O corpo deve ser um objeto JSON.');
        }
        try {
            $decoded = json_decode((string) $request->getBody(), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpBadRequestException($request, 'JSON malformado.');
        }
        if (!is_object($decoded)) {
            throw new HttpBadRequestException($request, 'O corpo deve ser um objeto JSON.');
        }
        return get_object_vars($decoded);
    }
}
