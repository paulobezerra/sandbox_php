<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Post\PostService;
use App\Application\Post\PostException;
use App\Domain\Post\PostRepository;
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
}
