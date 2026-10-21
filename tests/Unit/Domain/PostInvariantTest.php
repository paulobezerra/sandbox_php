<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Post\Post;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PostInvariantTest extends TestCase
{
    public function testAcceptsValidDraftAndPublishedPosts(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01T10:00:00+00:00');

        $draft = $this->post(excerpt: 'An excerpt', createdAt: $createdAt, updatedAt: $createdAt);
        $published = $this->post(
            status: 'PUBLISHED',
            publishedAt: $createdAt,
            createdAt: $createdAt,
            updatedAt: $createdAt
        );

        self::assertSame('DRAFT', $draft->status);
        self::assertSame('PUBLISHED', $published->status);
        self::assertSame($createdAt->format(DATE_ATOM), $draft->toArray()['createdAt']);
        self::assertArrayNotHasKey('authorId', $draft->toArray(public: true));
        self::assertArrayNotHasKey('updatedAt', $published->toArray(public: true));
    }

    public function testAcceptsBoundaryLengthValuesAndZeroDraftIdentifier(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01T10:00:00+00:00');
        $post = new Post(
            0,
            str_repeat('t', 200),
            str_repeat('a', 200),
            str_repeat('c', 60000),
            str_repeat('e', 1000),
            'DRAFT',
            1,
            null,
            $createdAt,
            $createdAt
        );

        self::assertSame(0, $post->id);
    }

    #[DataProvider('invalidStates')]
    public function testRejectsInvalidStates(array $changes): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->post(...$changes);
    }

    public static function invalidStates(): iterable
    {
        yield 'negative id' => [['id' => -1]];
        yield 'missing author' => [['authorId' => 0]];
        yield 'blank title' => [['title' => '  ']];
        yield 'long title' => [['title' => str_repeat('a', 201)]];
        yield 'empty content' => [['content' => '']];
        yield 'long content' => [['content' => str_repeat('a', 60001)]];
        yield 'invalid slug' => [['slug' => 'not a slug']];
        yield 'long slug' => [['slug' => str_repeat('a', 201)]];
        yield 'invalid status' => [['status' => 'ARCHIVED']];
        yield 'long excerpt' => [['excerpt' => str_repeat('a', 1001)]];
        yield 'published without timestamp' => [['status' => 'PUBLISHED']];
        yield 'draft with timestamp' => [['publishedAt' => new DateTimeImmutable('2026-01-01T10:00:00+00:00')]];
        yield 'updated before created' => [[
            'createdAt' => new DateTimeImmutable('2026-01-02T10:00:00+00:00'),
            'updatedAt' => new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        ]];
    }

    private function post(
        int $id = 1,
        string $title = 'A title',
        string $slug = 'a-title',
        string $content = 'Body',
        ?string $excerpt = null,
        string $status = 'DRAFT',
        int $authorId = 1,
        ?DateTimeImmutable $publishedAt = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ): Post {
        $createdAt ??= new DateTimeImmutable('2026-01-01T10:00:00+00:00');

        return new Post(
            $id,
            $title,
            $slug,
            $content,
            $excerpt,
            $status,
            $authorId,
            $publishedAt,
            $createdAt,
            $updatedAt ?? $createdAt
        );
    }
}
