<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Post\PostService;
use App\Bootstrap\App;
use App\Bootstrap\Config;
use App\Domain\ContentNotFound;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\{MysqlPostRepository, MysqlUserRepository};
use App\Tests\TestCase;

final class PostOwnershipTest extends TestCase
{
    public function testAuthorHttpRoutesKeepTheFullPostLifecycleAndOwnershipBoundary(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $users = new MysqlUserRepository($pdo);
        $posts = new MysqlPostRepository($pdo);
        $suffix = bin2hex(random_bytes(6));
        $authorA = $users->create('HTTP Author A', "http-a-{$suffix}@example.test", password_hash('test-password-a', PASSWORD_DEFAULT));
        $authorB = $users->create('HTTP Author B', "http-b-{$suffix}@example.test", password_hash('test-password-b', PASSWORD_DEFAULT));
        $slug = 'http-post-' . $suffix;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        session_id('post-http-' . bin2hex(random_bytes(8)));
        session_start();
        $_SESSION = ['user_id' => $authorA->id, 'csrf' => 'post-http-csrf'];
        $app = App::create($pdo);
        $withCsrf = static fn ($request) => $request->withHeader('X-CSRF-Token', 'post-http-csrf');

        try {
            $list = $app->handle($this->createRequest('GET', '/api/me/posts'));
            self::assertSame(200, $list->getStatusCode());
            self::assertSame([], json_decode((string) $list->getBody(), true, flags: JSON_THROW_ON_ERROR)['items']);

            $missingCsrf = $app->handle($this->createJsonRequest('POST', '/api/me/posts', [
                'title' => 'Protected post', 'content' => 'Body',
            ]));
            self::assertSame(419, $missingCsrf->getStatusCode());

            $created = $app->handle($withCsrf($this->createJsonRequest('POST', '/api/me/posts', [
                'title' => 'HTTP Post', 'slug' => $slug, 'content' => '# Original body', 'authorId' => $authorB->id,
            ])));
            self::assertSame(201, $created->getStatusCode(), (string) $created->getBody());
            $post = json_decode((string) $created->getBody(), true, flags: JSON_THROW_ON_ERROR)['post'];
            $postId = $post['id'];
            self::assertSame($authorA->id, $post['authorId']);

            $show = $app->handle($this->createRequest('GET', '/api/me/posts/' . $postId));
            self::assertSame(200, $show->getStatusCode());

            $_SESSION['user_id'] = $authorB->id;
            $otherShow = $app->handle($this->createRequest('GET', '/api/me/posts/' . $postId));
            $otherUpdate = $app->handle($withCsrf($this->createJsonRequest('PUT', '/api/me/posts/' . $postId, [
                'title' => 'Stolen', 'content' => 'Changed',
            ])));
            self::assertSame(404, $otherShow->getStatusCode());
            self::assertSame(404, $otherUpdate->getStatusCode());

            $_SESSION['user_id'] = $authorA->id;
            $updated = $app->handle($withCsrf($this->createJsonRequest('PUT', '/api/me/posts/' . $postId, [
                'title' => 'HTTP Post Updated', 'slug' => $slug, 'content' => '# Updated body',
            ])));
            self::assertSame(200, $updated->getStatusCode());

            $published = $app->handle($withCsrf($this->createRequest('POST', '/api/me/posts/' . $postId . '/publish')));
            self::assertSame(200, $published->getStatusCode());
            $public = $app->handle($this->createRequest('GET', '/api/posts/' . $slug));
            self::assertSame(200, $public->getStatusCode());
            self::assertSame('<h1>Updated body</h1>' . "\n", json_decode((string) $public->getBody(), true, flags: JSON_THROW_ON_ERROR)['post']['html']);

            $unpublished = $app->handle($withCsrf($this->createRequest('POST', '/api/me/posts/' . $postId . '/unpublish')));
            self::assertSame(200, $unpublished->getStatusCode());
            $hidden = $app->handle($this->createRequest('GET', '/api/posts/' . $slug));
            self::assertSame(404, $hidden->getStatusCode());

            $deleted = $app->handle($withCsrf($this->createRequest('DELETE', '/api/me/posts/' . $postId)));
            self::assertSame(204, $deleted->getStatusCode());
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                session_destroy();
            }
            $pdo->prepare('DELETE FROM posts WHERE slug = ?')->execute([$slug]);
            $pdo->prepare('DELETE FROM users WHERE id IN (?, ?)')->execute([$authorA->id, $authorB->id]);
        }
    }

    public function testMarkdownPreviewRequiresAnAuthorAndEscapesRawHtml(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $users = new MysqlUserRepository($pdo);
        $user = $users->create('Preview Author', 'preview-' . bin2hex(random_bytes(6)) . '@example.test', password_hash('test-password', PASSWORD_DEFAULT));
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        session_id('preview-http-' . bin2hex(random_bytes(8)));
        session_start();
        $_SESSION = ['csrf' => 'preview-csrf'];
        $app = App::create($pdo);
        $request = $this->createJsonRequest('POST', '/api/me/markdown-preview', ['content' => '<script>alert(1)</script>'])
            ->withHeader('X-CSRF-Token', 'preview-csrf');

        try {
            self::assertSame(401, $app->handle($request)->getStatusCode());
            $_SESSION['user_id'] = $user->id;
            $invalid = $app->handle($this->createJsonRequest('POST', '/api/me/markdown-preview', [])
                ->withHeader('X-CSRF-Token', 'preview-csrf'));
            self::assertSame(422, $invalid->getStatusCode());
            $response = $app->handle($request->withHeader('X-CSRF-Token', 'preview-csrf'));
            self::assertSame(200, $response->getStatusCode());
            $html = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR)['html'];
            self::assertStringContainsString('&lt;script&gt;', $html);
            self::assertStringNotContainsString('<script>', $html);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                session_destroy();
            }
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user->id]);
        }
    }

    public function testAuthorsCanOnlyListAndManageTheirOwnPosts(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $users = new MysqlUserRepository($pdo);
        $posts = new MysqlPostRepository($pdo);
        $suffix = bin2hex(random_bytes(6));
        $authorA = $users->create('Author A', "author-a-{$suffix}@example.test", password_hash('test-password-a', PASSWORD_DEFAULT));
        $authorB = $users->create('Author B', "author-b-{$suffix}@example.test", password_hash('test-password-b', PASSWORD_DEFAULT));
        $service = new PostService($posts);
        $postA = $service->create(['title' => 'Private A ' . $suffix, 'slug' => 'private-a-' . $suffix, 'content' => 'Draft A'], $authorA->id);
        $postB = $service->create(['title' => 'Private B ' . $suffix, 'slug' => 'private-b-' . $suffix, 'content' => 'Draft B'], $authorB->id);

        try {
            self::assertSame([$postA->id], array_map(static fn ($post): int => $post->id, $service->listMine($authorA->id, 1, 100)['items']));
            try {
                $service->findMine($postA->id, $authorB->id);
                self::fail('A different author must not read the post.');
            } catch (ContentNotFound) {
                self::assertSame('Private A ' . $suffix, $service->findMine($postA->id, $authorA->id)->title);
            }
            try {
                $service->updateMine($postA->id, $authorB->id, ['title' => 'Stolen', 'content' => 'Changed']);
                self::fail('A different author must not update the post.');
            } catch (ContentNotFound) {
                self::assertSame('Private A ' . $suffix, $service->findMine($postA->id, $authorA->id)->title);
            }
            $updated = $service->updateMine($postA->id, $authorA->id, ['title' => 'Updated A', 'content' => 'Own update', 'authorId' => $authorB->id]);
            self::assertSame($authorA->id, $updated->authorId, 'The request body must not change the author.');
            $published = $service->publishMine($postA->id, $authorA->id);
            self::assertSame('PUBLISHED', $published->status);
            $publicResponse = App::create($pdo)->handle($this->createRequest('GET', '/api/posts/' . $published->slug));
            self::assertSame(200, $publicResponse->getStatusCode(), 'Published Posts stay readable after Page routes are removed.');
            self::assertStringContainsString('application/json', $publicResponse->getHeaderLine('Content-Type'));
            try {
                $service->deleteMine($postB->id, $authorA->id);
                self::fail('A different author must not delete the post.');
            } catch (ContentNotFound) {
                self::assertNotNull($posts->find($postB->id));
            }
        } finally {
            $posts->delete($postA->id);
            $posts->delete($postB->id);
            $pdo->prepare('DELETE FROM users WHERE id IN (?, ?)')->execute([$authorA->id, $authorB->id]);
        }
    }
}
