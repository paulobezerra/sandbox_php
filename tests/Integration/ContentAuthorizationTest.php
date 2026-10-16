<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\{App, Config};
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\MysqlUserRepository;
use App\Tests\TestCase;
use PDO;

final class ContentAuthorizationTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        parent::tearDown();
    }

    public function testAnonymousVisitorCannotReadAdminPostsOrPages(): void
    {
        $pdo = new class () extends PDO {
            public function __construct()
            {
            }
        };
        $config = Config::fromArray(['DB_DATABASE' => 'test', 'DB_USERNAME' => 'test', 'APP_ENV' => 'testing']);
        foreach (['/api/admin/posts', '/api/admin/pages'] as $path) {
            $response = App::create($pdo, $config)->handle($this->createRequest('GET', $path));
            self::assertSame(401, $response->getStatusCode(), (string) $response->getBody());
            self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        }
    }

    public function testContentMutationRequiresCsrfBeforeAuthorization(): void
    {
        $pdo = new class () extends PDO {
            public function __construct()
            {
            }
        };
        $config = Config::fromArray(['DB_DATABASE' => 'test', 'DB_USERNAME' => 'test', 'APP_ENV' => 'testing']);
        $response = App::create($pdo, $config)->handle($this->createJsonRequest('POST', '/api/admin/posts', ['title' => 'Post', 'content' => 'Conteúdo']));
        self::assertSame(419, $response->getStatusCode());
    }

    public function testAdminCanCreatePostAndPageOverHttp(): void
    {
        $config = Config::fromEnvironment();
        $pdo = ConnectionFactory::create($config);
        $users = new MysqlUserRepository($pdo);
        $suffix = bin2hex(random_bytes(8));
        $user = $users->createAdmin('CMS HTTP Admin', "cms-http-{$suffix}@example.test", password_hash('test-password-long-enough', PASSWORD_DEFAULT));
        $csrf = bin2hex(random_bytes(32));
        $sessionId = bin2hex(random_bytes(16));
        $postSlug = 'http-post-' . $suffix;
        $pageSlug = 'http-page-' . $suffix;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_name($config->sessionCookie);
        session_id($sessionId);
        session_start();
        $_SESSION = ['user_id' => $user->id, 'auth_version' => $user->sessionVersion, 'csrf' => $csrf];
        session_write_close();
        $_COOKIE[$config->sessionCookie] = $sessionId;

        try {
            $app = App::create($pdo, $config);
            $post = $app->handle($this->createJsonRequest('POST', '/api/admin/posts', [
                'title' => 'HTTP Post', 'slug' => $postSlug, 'content' => '# Test post',
            ])->withHeader('X-CSRF-Token', $csrf));
            $preview = $app->handle($this->createJsonRequest('POST', '/api/admin/markdown-preview', [
                'content' => '# Safe preview',
            ])->withHeader('X-CSRF-Token', $csrf));
            $page = $app->handle($this->createJsonRequest('POST', '/api/admin/pages', [
                'title' => 'HTTP Page', 'slug' => $pageSlug, 'content' => '# Test page',
            ])->withHeader('X-CSRF-Token', $csrf));

            self::assertSame(201, $post->getStatusCode(), (string) $post->getBody());
            self::assertSame('DRAFT', json_decode((string) $post->getBody(), true)['post']['status']);
            self::assertSame(200, $preview->getStatusCode());
            self::assertSame('<h1>Safe preview</h1>' . "\n", json_decode((string) $preview->getBody(), true)['html']);
            self::assertSame(201, $page->getStatusCode(), (string) $page->getBody());
            self::assertSame('DRAFT', json_decode((string) $page->getBody(), true)['page']['status']);

            $postId = json_decode((string) $post->getBody(), true)['post']['id'];
            $pageId = json_decode((string) $page->getBody(), true)['page']['id'];
            $adminPosts = $app->handle($this->createRequest('GET', '/api/admin/posts'));
            $adminPage = $app->handle($this->createRequest('GET', '/api/admin/pages/' . $pageId));
            self::assertSame(200, $adminPosts->getStatusCode());
            self::assertSame(200, $adminPage->getStatusCode());

            $updatePost = $app->handle($this->createJsonRequest('PUT', '/api/admin/posts/' . $postId, [
                'title' => 'Updated HTTP Post', 'slug' => $postSlug, 'content' => '# Updated post body',
            ])->withHeader('X-CSRF-Token', $csrf));
            $updatePage = $app->handle($this->createJsonRequest('PUT', '/api/admin/pages/' . $pageId, [
                'title' => 'Updated HTTP Page', 'slug' => $pageSlug, 'content' => '# Updated page body',
            ])->withHeader('X-CSRF-Token', $csrf));
            self::assertSame(200, $updatePost->getStatusCode());
            self::assertSame(200, $updatePage->getStatusCode());

            foreach ([['posts', $postId], ['pages', $pageId]] as [$resource, $id]) {
                $publish = $app->handle($this->createJsonRequest('POST', "/api/admin/$resource/$id/publish", [
                    'unused' => true,
                ])->withHeader('X-CSRF-Token', $csrf));
                self::assertSame(200, $publish->getStatusCode());

                $slug = $resource === 'posts' ? $postSlug : $pageSlug;
                $publicList = $app->handle($this->createRequest('GET', "/api/$resource"));
                $publicShow = $app->handle($this->createRequest('GET', "/api/$resource/$slug"));
                self::assertSame(200, $publicList->getStatusCode());
                self::assertSame(200, $publicShow->getStatusCode());
                self::assertStringContainsString('<h1>', json_decode((string) $publicShow->getBody(), true)[str_starts_with($resource, 'post') ? 'post' : 'page']['html']);

                $unpublish = $app->handle($this->createJsonRequest('POST', "/api/admin/$resource/$id/unpublish", [
                    'unused' => true,
                ])->withHeader('X-CSRF-Token', $csrf));
                self::assertSame(200, $unpublish->getStatusCode());
            }

            $deletePost = $app->handle($this->createRequest('DELETE', '/api/admin/posts/' . $postId)->withHeader('X-CSRF-Token', $csrf));
            $deletePage = $app->handle($this->createRequest('DELETE', '/api/admin/pages/' . $pageId)->withHeader('X-CSRF-Token', $csrf));
            self::assertSame(204, $deletePost->getStatusCode());
            self::assertSame(204, $deletePage->getStatusCode());
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                session_destroy();
            }
            unset($_COOKIE[$config->sessionCookie]);
            $pdo->prepare('DELETE FROM posts WHERE slug = ?')->execute([$postSlug]);
            $pdo->prepare('DELETE FROM pages WHERE slug = ?')->execute([$pageSlug]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user->id]);
        }
    }
}
