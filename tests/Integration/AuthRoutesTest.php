<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\{App,Config};
use App\Tests\TestCase;
use PDO;

final class AuthRoutesTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        parent::tearDown();
    }
    public function testCsrfEndpointCreatesTokenAndAuthControllerResolvesFromContainer(): void
    {
        $pdo = new class () extends PDO {
            public function __construct()
            {
            }
        };
        $config = Config::fromArray(['DB_DATABASE' => 'test','DB_USERNAME' => 'test','APP_ENV' => 'testing']);
        $response = App::create($pdo, $config)->handle($this->createRequest('GET', '/api/auth/csrf'));
        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        self::assertIsString($body['csrfToken'] ?? null);
        self::assertSame(64, strlen($body['csrfToken']));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }
    public function testAuthMutationWithoutCsrfTokenIsRejected(): void
    {
        $pdo = new class () extends PDO {
            public function __construct()
            {
            }
        };
        $config = Config::fromArray(['DB_DATABASE' => 'test','DB_USERNAME' => 'test','APP_ENV' => 'testing']);
        $response = App::create($pdo, $config)->handle($this->createJsonRequest('POST', '/api/auth/register', ['name' => 'User','email' => 'user@example.test','password' => 'long-enough-password']));
        self::assertSame(419, $response->getStatusCode());
    }
}
