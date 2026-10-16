<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Post\PostService;
use App\Bootstrap\Config;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\{MysqlPostRepository, MysqlUserRepository};
use App\Tests\TestCase;

final class CmsPersistenceTest extends TestCase
{
    public function testInitialSchemaContainsOnlyUsersAndPostsWithTheirConstraints(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        sort($tables);
        self::assertSame(['phinxlog', 'posts', 'users'], $tables);

        $userColumns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(\PDO::FETCH_COLUMN);
        self::assertSame(['id', 'name', 'email', 'password_hash', 'created_at', 'updated_at'], $userColumns);

        $foreignKeys = $pdo->query(
            "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND REFERENCED_TABLE_NAME IS NOT NULL"
        )->fetchAll(\PDO::FETCH_ASSOC);
        self::assertSame([[
            'COLUMN_NAME' => 'author_id',
            'REFERENCED_TABLE_NAME' => 'users',
            'REFERENCED_COLUMN_NAME' => 'id',
        ]], $foreignKeys);

        self::assertNotNull($this->getApp(), 'Bootstrap must initialize while legacy modules remain registered.');
    }

    public function testPostsMoveBetweenDraftAndPublishedPublicQueries(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $users = new MysqlUserRepository($pdo);
        $posts = new MysqlPostRepository($pdo);
        $suffix = bin2hex(random_bytes(6));
        $user = $users->create('CMS Integration', "cms-{$suffix}@example.test", password_hash('irrelevant-test-password', PASSWORD_DEFAULT));
        $postId = null;

        try {
            $postService = new PostService($posts);
            $post = $postService->create(['title' => 'Post ' . $suffix, 'slug' => 'post-' . $suffix, 'content' => '# Markdown', 'excerpt' => null], $user->id);
            $postId = $post->id;
            self::assertNull($posts->findPublishedBySlug($post->slug));
            self::assertSame('PUBLISHED', $postService->publishMine($post->id, $user->id)->status);
            self::assertSame($post->id, $posts->findPublishedBySlug($post->slug)?->id);
            self::assertContains($post->id, array_map(static fn ($item): int => $item->id, $postService->listPublished(1, 100)['items']));
            self::assertSame('DRAFT', $postService->unpublishMine($post->id, $user->id)->status);
            self::assertNull($posts->findPublishedBySlug($post->slug));

        } finally {
            if ($postId !== null) {
                $posts->delete($postId);
            }
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user->id]);
        }
    }
}
