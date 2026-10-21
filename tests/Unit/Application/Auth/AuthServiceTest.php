<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth;

use App\Application\Auth\{AuthException, AuthService};
use App\Domain\User\{User, UserRepository};
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AuthServiceTest extends TestCase
{
    public function testRegistrationNormalizesEmailHashesPasswordAndReturnsNoProfile(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $user = $this->user(3, 'correct horse battery staple');
        $repository->expects(self::once())->method('findByEmail')->with('alice@example.com')->willReturn(null);
        $repository->expects(self::once())->method('create')->with('Alice', 'alice@example.com', self::callback(
            static fn (string $hash): bool => password_verify('correct horse battery staple', $hash)
        ))->willReturn($user);

        $registered = (new AuthService($repository))->register(' Alice ', 'ALICE@Example.com ', 'correct horse battery staple');

        self::assertSame($user, $registered);
        self::assertSame(['id', 'name', 'email', 'createdAt'], array_keys($registered->publicData()));
        self::assertArrayNotHasKey('passwordHash', $registered->publicData());
    }

    public function testInvalidLoginCanBeRepeatedWithoutRateLimiting(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::exactly(2))->method('findByEmail')->with('nobody@example.com')->willReturn(null);
        $service = new AuthService($repository);

        foreach ([1, 2] as $_attempt) {
            try {
                $service->login('NOBODY@example.com', 'wrong password');
                self::fail('Expected invalid credentials.');
            } catch (AuthException $error) {
                self::assertSame('invalid_credentials', $error->reason);
            }
        }
    }

    public function testLoginVerifiesPasswordWithoutAttemptStorage(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $user = $this->user(4, 'correct password value');
        $repository->expects(self::once())->method('findByEmail')->with('alice@example.com')->willReturn($user);

        self::assertSame($user, (new AuthService($repository))->login(' ALICE@example.com ', 'correct password value'));
    }

    public function testWrongPasswordDoesNotCreateASessionOrChangeCredentials(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByEmail')->willReturn($this->user(4, 'correct password value'));
        $repository->expects(self::never())->method('create');

        try {
            (new AuthService($repository))->login('alice@example.com', 'wrong password');
            self::fail('Expected invalid credentials.');
        } catch (AuthException $error) {
            self::assertSame('invalid_credentials', $error->reason);
        }
    }

    public function testRegistrationRejectsWeakPasswordBeforeRepositoryAccess(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('findByEmail');
        $repository->expects(self::never())->method('create');

        try {
            (new AuthService($repository))->register('Alice', 'alice@example.com', 'short');
            self::fail('Expected invalid password.');
        } catch (AuthException $error) {
            self::assertSame('invalid_password', $error->reason);
        }
    }

    public function testRegistrationRejectsDuplicateEmail(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByEmail')->with('alice@example.com')->willReturn($this->user(3, 'existing password'));
        $repository->expects(self::never())->method('create');

        try {
            (new AuthService($repository))->register('Alice', 'alice@example.com', 'a sufficiently long password');
            self::fail('Expected duplicate email.');
        } catch (AuthException $error) {
            self::assertSame('email_exists', $error->reason);
        }
    }

    #[DataProvider('invalidRegistrationValues')]
    public function testRegistrationRejectsEveryInvalidBoundaryBeforePersistence(array $values, string $reason): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('findByEmail');
        $repository->expects(self::never())->method('create');

        try {
            (new AuthService($repository))->register(
                $values['name'] ?? 'Alice',
                $values['email'] ?? 'alice@example.com',
                $values['password'] ?? 'a sufficiently long password'
            );
            self::fail('Invalid registration data should be rejected.');
        } catch (AuthException $exception) {
            self::assertSame($reason, $exception->reason);
        }
    }

    public static function invalidRegistrationValues(): iterable
    {
        yield 'blank name' => [['name' => '  '], 'invalid_name'];
        yield 'long name' => [['name' => str_repeat('a', 121)], 'invalid_name'];
        yield 'invalid email' => [['email' => 'not-an-email'], 'invalid_email'];
        yield 'long email' => [['email' => str_repeat('a', 245) . '@example.com'], 'invalid_email'];
        yield 'long password' => [['password' => str_repeat('x', 4097)], 'invalid_password'];
    }

    private function user(int $id, string $password): User
    {
        return new User($id, 'Alice', 'alice@example.com', password_hash($password, PASSWORD_DEFAULT), new DateTimeImmutable());
    }
}
