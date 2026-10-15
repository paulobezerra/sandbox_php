<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\{App, Config};
use App\Tests\TestCase;
use PDO;

final class ContentAuthorizationTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION = []; session_destroy(); }
        parent::tearDown();
    }

    public function testAnonymousVisitorCannotReadAdminPostsOrPages(): void
    {
        $pdo = new class extends PDO { public function __construct() {} };
        $config = Config::fromArray(['DB_DATABASE' => 'test', 'DB_USERNAME' => 'test', 'APP_ENV' => 'testing']);
        foreach (['/api/admin/posts', '/api/admin/pages'] as $path) {
            $response = App::create($pdo, $config)->handle($this->createRequest('GET', $path));
            self::assertSame(401, $response->getStatusCode(), (string) $response->getBody());
            self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        }
    }

    public function testContentMutationRequiresCsrfBeforeAuthorization(): void
    {
        $pdo = new class extends PDO { public function __construct() {} };
        $config = Config::fromArray(['DB_DATABASE' => 'test', 'DB_USERNAME' => 'test', 'APP_ENV' => 'testing']);
        $response = App::create($pdo, $config)->handle($this->createJsonRequest('POST', '/api/admin/posts', ['title' => 'Post', 'content' => 'Conteúdo']));
        self::assertSame(419, $response->getStatusCode());
    }
}
