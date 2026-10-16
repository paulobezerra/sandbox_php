<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Page\Page;
use App\Domain\Post\Post;
use App\Domain\User\User;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CmsEntityInvariantTest extends TestCase
{
    public function testUserRejectsUnknownRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new User(1, 'Alice', 'alice@example.test', 'hash', 'OWNER', new DateTimeImmutable());
    }

    public function testPostRejectsPublishedStateWithoutPublicationTime(): void
    {
        $now = new DateTimeImmutable();
        $this->expectException(InvalidArgumentException::class);
        new Post(1, 'Title', 'title', 'Content', null, 'PUBLISHED', 1, null, $now, $now);
    }

    public function testPageRejectsInvalidPersistedSlug(): void
    {
        $now = new DateTimeImmutable();
        $this->expectException(InvalidArgumentException::class);
        new Page(1, 'Title', 'Invalid Slug', 'Content', 'DRAFT', null, $now, $now);
    }
}
