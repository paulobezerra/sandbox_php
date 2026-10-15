<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Page\PageService;
use App\Application\Post\PostService;
use App\Bootstrap\Config;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\{MysqlPageRepository, MysqlPostRepository, MysqlUserRepository};
use App\Tests\TestCase;

final class CmsPersistenceTest extends TestCase
{
    public function testPostsAndPagesMoveBetweenDraftAndPublishedPublicQueries(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $users = new MysqlUserRepository($pdo);
        $posts = new MysqlPostRepository($pdo);
        $pages = new MysqlPageRepository($pdo);
        $suffix = bin2hex(random_bytes(6));
        $user = $users->createAdmin('CMS Integration', "cms-{$suffix}@example.test", password_hash('irrelevant-test-password', PASSWORD_DEFAULT));
        $postId = null;
        $pageId = null;

        try {
            $postService = new PostService($posts);
            $post = $postService->create(['title' => 'Post ' . $suffix, 'slug' => 'post-' . $suffix, 'content' => '# Markdown', 'excerpt' => null], $user->id);
            $postId = $post->id;
            self::assertNull($posts->findPublishedBySlug($post->slug));
            self::assertSame('PUBLISHED', $postService->publish($post->id)->status);
            self::assertSame($post->id, $posts->findPublishedBySlug($post->slug)?->id);
            self::assertContains($post->id, array_map(static fn($item): int => $item->id, $postService->listPublished(1, 100)['items']));
            self::assertSame('DRAFT', $postService->unpublish($post->id)->status);
            self::assertNull($posts->findPublishedBySlug($post->slug));

            $pageService = new PageService($pages);
            $page = $pageService->create(['title' => 'Page ' . $suffix, 'slug' => 'page-' . $suffix, 'content' => 'Conteúdo da página']);
            $pageId = $page->id;
            self::assertNull($pages->findPublishedBySlug($page->slug));
            self::assertSame('PUBLISHED', $pageService->publish($page->id)->status);
            self::assertSame($page->id, $pages->findPublishedBySlug($page->slug)?->id);
            self::assertContains($page->id, array_map(static fn($item): int => $item->id, $pageService->listPublished(1, 100)['items']));
            self::assertSame('DRAFT', $pageService->unpublish($page->id)->status);
            self::assertNull($pages->findPublishedBySlug($page->slug));
        } finally {
            if ($postId !== null) { $posts->delete($postId); }
            if ($pageId !== null) { $pages->delete($pageId); }
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user->id]);
        }
    }
}
