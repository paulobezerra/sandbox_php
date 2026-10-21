<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Post\PostService;
use App\Application\Post\PostException;
use App\Domain\Post\PostRepository;
use App\Domain\Post\Post;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PostServiceTest extends TestCase
{
    public function testValidationErrorsExposeStableReasonsInsteadOfPresentationText(): void
    {
        $service = new PostService($this->createMock(PostRepository::class));

        try {
            $service->create(['title' => '', 'content' => 'Body'], 1);
            self::fail('Invalid Post data must be rejected.');
        } catch (PostException $error) {
            self::assertSame('post.titleRequired', $error->reason);
            self::assertSame('', $error->getMessage());
        }
    }

    public function testInvalidExplicitSlugAndWrongFieldTypesAreRejected(): void
    {
        $repository = $this->createMock(PostRepository::class);
        $repository->expects(self::never())->method('create');
        $service = new PostService($repository);
        foreach ([
            ['title' => 'Title', 'content' => 'Text', 'slug' => 'Bad slug!'],
            ['title' => ['Title'], 'content' => 'Text'],
            ['title' => 'Title', 'content' => 123],
        ] as $payload) {
            try {
                $service->create($payload, 1);
                self::fail('Expected invalid input types or slug to fail.');
            } catch (DomainException) {
                self::assertTrue(true);
            }
        }
    }

    public function testOtherAuthorCannotReadUpdateOrDeletePost(): void
    {
        $repository = $this->createMock(PostRepository::class);
        $repository->expects(self::exactly(3))->method('findByAuthor')->with(17, 99)->willReturn(null);
        $repository->expects(self::never())->method('update');
        $repository->expects(self::never())->method('delete');
        $service = new PostService($repository);

        foreach ([
            static fn () => $service->findMine(17, 99),
            static fn () => $service->updateMine(17, 99, ['title' => 'Changed', 'content' => 'Changed']),
            static fn () => $service->deleteMine(17, 99),
        ] as $operation) {
            try {
                $operation();
                self::fail('A different author must not access the Post.');
            } catch (PostException $error) {
                self::assertSame('post.notFound', $error->reason);
            }
        }
    }

    public function testUpdateKeepsTheExistingAuthorEvenIfInputContainsAnotherAuthor(): void
    {
        $post = $this->post(17, 41);
        $repository = $this->createMock(PostRepository::class);
        $repository->expects(self::once())->method('findByAuthor')->with(17, 41)->willReturn($post);
        $repository->expects(self::once())->method('update')->with(self::callback(
            static fn (Post $updated): bool => $updated->authorId === 41 && $updated->title === 'Updated'
        ))->willReturnCallback(static fn (Post $updated): Post => $updated);

        $updated = (new PostService($repository))->updateMine(17, 41, [
            'title' => 'Updated', 'content' => 'Revised', 'authorId' => 99,
        ]);

        self::assertSame(41, $updated->authorId);
    }

    #[DataProvider('invalidPostData')]
    public function testCreateRejectsEachInvalidFieldBeforePersistence(array $data, string $reason): void
    {
        $repository = $this->createMock(PostRepository::class);
        $repository->expects(self::never())->method('create');

        try {
            (new PostService($repository))->create($data, 1);
            self::fail('Invalid Post data should be rejected.');
        } catch (PostException $exception) {
            self::assertSame($reason, $exception->reason);
        }
    }

    public static function invalidPostData(): iterable
    {
        yield 'missing title' => [['content' => 'Body'], 'post.titleRequired'];
        yield 'missing content' => [['title' => 'Title'], 'post.contentRequired'];
        yield 'non-string title' => [['title' => 4, 'content' => 'Body'], 'post.titleText'];
        yield 'non-string content' => [['title' => 'Title', 'content' => 4], 'post.contentText'];
        yield 'non-string slug' => [['title' => 'Title', 'content' => 'Body', 'slug' => 5], 'post.invalidSlug'];
        yield 'non-string excerpt' => [['title' => 'Title', 'content' => 'Body', 'excerpt' => 5], 'post.invalidExcerpt'];
        yield 'blank title' => [['title' => '  ', 'content' => 'Body'], 'post.titleRequired'];
        yield 'long title' => [['title' => str_repeat('x', 201), 'content' => 'Body'], 'post.titleRequired'];
        yield 'blank content' => [['title' => 'Title', 'content' => ''], 'post.contentRequired'];
        yield 'long content' => [['title' => 'Title', 'content' => str_repeat('x', 60001)], 'post.contentRequired'];
        yield 'invalid slug' => [['title' => 'Title', 'content' => 'Body', 'slug' => 'not a slug'], 'post.invalidSlug'];
        yield 'long slug' => [['title' => 'Title', 'content' => 'Body', 'slug' => str_repeat('x', 201)], 'post.invalidSlug'];
        yield 'long excerpt' => [['title' => 'Title', 'content' => 'Body', 'excerpt' => str_repeat('x', 1001)], 'post.excerptTooLong'];
    }

    public function testCreateSlugifiesTitleAndTrimsOptionalExcerpt(): void
    {
        $repository = $this->createMock(PostRepository::class);
        $repository->expects(self::once())->method('create')->with(self::callback(
            static fn (Post $post): bool => $post->slug === 'cafe-au-lait' && $post->excerpt === 'A note'
        ))->willReturnCallback(static fn (Post $post): Post => $post);

        $post = (new PostService($repository))->create([
            'title' => ' Café au lait ', 'content' => 'Body', 'slug' => ' ', 'excerpt' => ' A note ',
        ], 4);

        self::assertSame('DRAFT', $post->status);
    }

    public function testListServicesNormalizePaginationAndRejectInvalidBounds(): void
    {
        $repository = $this->createMock(PostRepository::class);
        $repository->expects(self::once())->method('listByAuthor')->with(4, 50, 50)->willReturn([]);
        $repository->expects(self::once())->method('countByAuthor')->with(4)->willReturn(0);
        $repository->expects(self::once())->method('listPublished')->with(10, 0)->willReturn([]);
        $repository->expects(self::once())->method('countPublished')->willReturn(0);
        $service = new PostService($repository);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 2, 'perPage' => 50], $service->listMine(4, 2, 50));
        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'perPage' => 10], $service->listPublished(1, 10));

        foreach ([[0, 10], [1, 0], [1, 101]] as [$page, $size]) {
            try {
                $service->listPublished($page, $size);
                self::fail('Out-of-range pagination must be rejected.');
            } catch (PostException $exception) {
                self::assertSame('post.invalidPagination', $exception->reason);
            }
        }
    }

    public function testPublishingAndUnpublishingPreservePostState(): void
    {
        $draft = $this->post(17, 41);
        $publishedAt = new DateTimeImmutable('2026-01-01T10:00:00+00:00');
        $published = new Post(18, 'Published', 'published', 'Body', null, 'PUBLISHED', 41, $publishedAt, $publishedAt, $publishedAt);
        $repository = $this->createMock(PostRepository::class);
        $repository->expects(self::exactly(3))->method('findByAuthor')->willReturnOnConsecutiveCalls($draft, $published, $published);
        $repository->expects(self::exactly(3))->method('update')->willReturnCallback(static fn (Post $post): Post => $post);
        $service = new PostService($repository);

        self::assertSame('PUBLISHED', $service->publishMine(17, 41)->status);
        self::assertSame($publishedAt, $service->publishMine(18, 41)->publishedAt);
        $unpublished = $service->unpublishMine(18, 41);
        self::assertSame('DRAFT', $unpublished->status);
        self::assertNull($unpublished->publishedAt);
    }

    private function post(int $id, int $authorId): Post
    {
        $now = new DateTimeImmutable();
        return new Post($id, 'Original', 'original', 'Body', null, 'DRAFT', $authorId, null, $now, $now);
    }
}
