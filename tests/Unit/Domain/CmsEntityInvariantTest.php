<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Post\Post;
use App\Domain\User\User;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CmsEntityInvariantTest extends TestCase
{
    public function testUserPublicDataDoesNotExposeInternalCredentials(): void
    {
        $user = new User(1, 'Alice', 'alice@example.test', 'hash', new DateTimeImmutable());

        self::assertSame(['id', 'name', 'email', 'createdAt'], array_keys($user->publicData()));
        self::assertArrayNotHasKey('role', $user->publicData());
        self::assertArrayNotHasKey('passwordHash', $user->publicData());
    }

    public function testPostRejectsPublishedStateWithoutPublicationTime(): void
    {
        $now = new DateTimeImmutable();
        $this->expectException(InvalidArgumentException::class);
        new Post(1, 'Title', 'title', 'Content', null, 'PUBLISHED', 1, null, $now, $now);
    }

}
