<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\User\User;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserInvariantTest extends TestCase
{
    #[DataProvider('invalidUsers')]
    public function testRejectsInvalidUserValues(array $changes, string $message): void
    {
        $values = array_replace([
            'id' => 1,
            'name' => 'Example User',
            'email' => 'user@example.test',
            'passwordHash' => 'hash',
            'createdAt' => new DateTimeImmutable('2026-01-01T00:00:00Z'),
        ], $changes);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        new User(...$values);
    }

    public static function invalidUsers(): iterable
    {
        yield 'negative id' => [['id' => -1], 'Invalid user data.'];
        yield 'blank name' => [['name' => '  '], 'Invalid user data.'];
        yield 'long name' => [['name' => str_repeat('x', 121)], 'Invalid user data.'];
        yield 'invalid email' => [['email' => 'invalid'], 'Invalid user email.'];
        yield 'long email' => [['email' => str_repeat('a', 243) . '@example.test'], 'Invalid user email.'];
        yield 'missing password hash' => [['passwordHash' => ''], 'Invalid user credentials.'];
    }
}
