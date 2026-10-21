<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\Http\JsonObject;
use App\Infrastructure\Http\JsonRequestException;
use App\Tests\TestCase;

final class JsonObjectTest extends TestCase
{
    public function testParsesJsonObjectAndAcceptsMediaTypeParameters(): void
    {
        $request = $this->createJsonRequest('POST', '/api', ['title' => 'Hello'], ['Content-Type' => 'Application/JSON; charset=utf-8']);
        self::assertSame(['title' => 'Hello'], JsonObject::fromRequest($request));
    }

    public function testRejectsWrongMediaTypeMalformedJsonAndNonObjectValues(): void
    {
        $requests = [
            [$this->createRequest('POST', '/api', ['Content-Type' => 'text/plain']), 'invalid_json_object'],
            [$this->createRequest('POST', '/api', ['Content-Type' => 'application/json'])->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream('{')), 'malformed_json'],
            [$this->createRequest('POST', '/api', ['Content-Type' => 'application/json'])->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream('[]')), 'invalid_json_object'],
        ];

        foreach ($requests as [$request, $reason]) {
            try {
                JsonObject::fromRequest($request);
                self::fail('Invalid JSON request should be rejected.');
            } catch (JsonRequestException $exception) {
                self::assertSame($reason, $exception->reason);
            }
        }
    }
}
