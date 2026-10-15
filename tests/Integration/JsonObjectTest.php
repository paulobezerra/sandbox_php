<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Infrastructure\Http\JsonObject;
use App\Tests\TestCase;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Factory\StreamFactory;

final class JsonObjectTest extends TestCase
{
    public function testAcceptsJsonObjectAndRejectsArrayAndMalformedJsonAsBadRequest(): void
    {
        self::assertSame(['name' => 'Alice'], JsonObject::fromRequest(
            $this->createJsonRequest('POST', '/api/auth/register', ['name' => 'Alice'])
        ));

        foreach (['["Alice"]', '{bad'] as $raw) {
            $request = $this->createRequest('POST', '/api/content', ['Content-Type' => 'application/json']);
            $request = $request->withBody((new StreamFactory())->createStream($raw));
            try {
                JsonObject::fromRequest($request);
                self::fail('Expected a malformed or non-object JSON payload to fail.');
            } catch (HttpBadRequestException $error) {
                self::assertSame(400, $error->getCode());
            }
        }
    }
}
