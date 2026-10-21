<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\Http\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

final class JsonResponseTest extends TestCase
{
    public function testSuccessAndErrorSerializePayloadAndOptionalDetails(): void
    {
        $success = JsonResponse::success(new Response(), (object) ['value' => 'ok'], 201);
        self::assertSame(201, $success->getStatusCode());
        self::assertSame('application/json', $success->getHeaderLine('Content-Type'));
        self::assertSame(['value' => 'ok'], json_decode((string) $success->getBody(), true, flags: JSON_THROW_ON_ERROR));

        $withoutDetails = JsonResponse::error(new Response(), 'Invalid', 400);
        self::assertArrayNotHasKey('details', json_decode((string) $withoutDetails->getBody(), true, flags: JSON_THROW_ON_ERROR)['error']);
        $withDetails = JsonResponse::validationError(new Response(), ['title' => 'required']);
        $payload = json_decode((string) $withDetails->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(422, $payload['error']['status']);
        self::assertSame(['title' => 'required'], $payload['error']['details']);
    }
}
