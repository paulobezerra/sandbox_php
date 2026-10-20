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

    private function post(int $id, int $authorId): Post
    {
        $now = new DateTimeImmutable();
        return new Post($id, 'Original', 'original', 'Body', null, 'DRAFT', $authorId, null, $now, $now);
    }
}
