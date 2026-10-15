<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth;

use App\Application\Auth\{AuthException, AuthService};
use App\Domain\User\{User, UserRepository};
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testRegistrationNormalizesEmailHashesPasswordAndCreatesUserRole(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('findByEmail')->with('alice@example.com')->willReturn(null);
        $repository->expects(self::once())->method('create')->with('Alice', 'alice@example.com', self::callback(
            static fn(string $hash): bool => password_verify('correct horse battery staple', $hash)
        ))->willReturn($this->user(3, 'correct horse battery staple'));
        $repository->method('recordAttempt')->willReturn(1);
        $user = (new AuthService($repository))->register(' Alice ', 'ALICE@Example.com ', 'correct horse battery staple');
        self::assertSame('USER', $user->role);
        self::assertArrayNotHasKey('passwordHash', $user->publicData());
    }

    public function testRegeneratingCodesVerifiesPasswordAndPersistsOnlyHashes(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findById')->with(9)->willReturn($this->user(9, 'old password long enough'));
        $repository->expects(self::once())->method('replaceRecoveryCodes')->with(9, self::callback(
            static fn(array $hashes): bool => count($hashes) === 10
                && count(array_unique($hashes)) === 10
                && array_reduce($hashes, static fn(bool $valid, string $hash): bool => $valid && preg_match('/^[a-f0-9]{64}$/', $hash) === 1, true)
        ));
        $repository->method('recordAttempt')->willReturn(1);
        $codes = (new AuthService($repository))->regenerateRecoveryCodes(9, 'old password long enough', '127.0.0.1');
        self::assertCount(10, $codes);
        self::assertCount(10, array_unique($codes));
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $codes[0]);
    }

    public function testRegeneratingCodesRequiresCurrentPassword(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findById')->willReturn($this->user(9, 'old password long enough'));
        $repository->method('recordAttempt')->willReturn(1);
        $repository->expects(self::never())->method('replaceRecoveryCodes');
        try {
            (new AuthService($repository))->regenerateRecoveryCodes(9, 'wrong password', '127.0.0.1');
            self::fail('Expected invalid current password.');
        } catch (AuthException $error) {
            self::assertSame('current_password', $error->reason);
        }
    }

    public function testResetRateLimitsBeforeConsumingCodeAndHashesNewPassword(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::exactly(2))->method('recordAttempt')->willReturnOnConsecutiveCalls(1, 1);
        $repository->expects(self::once())->method('consumeRecoveryCodeAndReplacePassword')->with(
            hash('sha256', str_repeat('a', 32)),
            self::callback(static fn(string $hash): bool => password_verify('a new sufficiently long password', $hash)),
            self::isType('string')
        )->willReturn(12);
        $repository->expects(self::once())->method('clearAttempts');
        (new AuthService($repository))->reset(str_repeat('a', 32), 'a new sufficiently long password', '127.0.0.1');
    }

    public function testLoginUsesAccountAndOriginLimitsAndVerifiesPassword(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $user = $this->user(4, 'correct password value');
        $repository->expects(self::exactly(2))->method('recordAttempt')->willReturnOnConsecutiveCalls(1, 1);
        $repository->expects(self::once())->method('findByEmail')->with('alice@example.com')->willReturn($user);
        $repository->expects(self::exactly(2))->method('clearAttempts');
        self::assertSame($user, (new AuthService($repository))->login(' ALICE@example.com ', 'correct password value', '127.0.0.1'));
    }

    public function testOriginRateLimitStopsLoginBeforeLookingUpAnyAccount(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::exactly(2))->method('recordAttempt')->willReturnOnConsecutiveCalls(1, 31);
        $repository->expects(self::never())->method('findByEmail');
        try {
            (new AuthService($repository))->login('another@example.com', 'guess', '127.0.0.1');
            self::fail('Expected origin rate limit.');
        } catch (AuthException $error) {
            self::assertSame('rate_limited', $error->reason);
        }
    }

    public function testOriginLimitIsSharedAcrossDifferentAccounts(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::exactly(4))->method('recordAttempt')->willReturnOnConsecutiveCalls(1, 30, 1, 31);
        $repository->expects(self::once())->method('findByEmail')->with('first@example.test')->willReturn(null);
        $service = new AuthService($repository);
        try {
            $service->login('first@example.test', 'guess', '127.0.0.1');
            self::fail('Expected invalid credentials for the first account.');
        } catch (AuthException $error) {
            self::assertSame('invalid_credentials', $error->reason);
        }
        try {
            $service->login('second@example.test', 'guess', '127.0.0.1');
            self::fail('Expected the origin-level limit to include the second account.');
        } catch (AuthException $error) {
            self::assertSame('rate_limited', $error->reason);
        }
    }

    public function testResetRateLimitRunsBeforeCodeConsumption(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::exactly(2))->method('recordAttempt')->willReturnOnConsecutiveCalls(21, 1);
        $repository->expects(self::never())->method('consumeRecoveryCodeAndReplacePassword');
        try {
            (new AuthService($repository))->reset(str_repeat('a', 32), 'a new sufficiently long password', '127.0.0.1');
            self::fail('Expected reset rate limit.');
        } catch (AuthException $error) {
            self::assertSame('rate_limited', $error->reason);
        }
    }

    private function user(int $id, string $password): User
    {
        return new User($id, 'Alice', 'alice@example.com', password_hash($password, PASSWORD_DEFAULT), 'USER', new \DateTimeImmutable());
    }
}
