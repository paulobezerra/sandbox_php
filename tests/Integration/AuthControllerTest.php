<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Auth\AuthService;
use App\Domain\User\{User, UserRepository};
use App\Infrastructure\Http\Controller\AuthController;
use App\Infrastructure\Http\ClientIpResolver;
use App\Tests\TestCase;
use Slim\Psr7\Response;

final class AuthControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['user_id']);
        parent::tearDown();
    }

    public function testRecoveryCodesRequireAuthenticationAndCurrentPasswordAndReturnCodesOnlyOnce(): void
    {
        $_SESSION = [];
        $repository = $this->createMock(UserRepository::class);
        $controller = new AuthController(new AuthService($repository), $repository, new ClientIpResolver([]));
        $unauthorized = $controller->recoveryCodes($this->createJsonRequest('POST', '/api/auth/recovery-codes', ['currentPassword' => 'secret']), new Response());
        self::assertSame(401, $unauthorized->getStatusCode());

        $_SESSION['user_id'] = 51;
        $user = new User(51, 'Alice', 'alice@example.test', password_hash('current password long enough', PASSWORD_DEFAULT), 'USER', new \DateTimeImmutable());
        $repository->method('findById')->with(51)->willReturn($user);
        $repository->method('recordAttempt')->willReturn(1);
        $persistedHashes = (object) ['values' => []];
        $repository->expects(self::once())->method('replaceRecoveryCodes')->with(51, self::callback(
            static function (array $hashes) use ($persistedHashes): bool {
                $persistedHashes->values = $hashes;
                return count($hashes) === 10 && count(array_unique($hashes)) === 10;
            }
        ));
        $controller = new AuthController(new AuthService($repository), $repository, new ClientIpResolver([]));
        $response = $controller->recoveryCodes(
            $this->createJsonRequest('POST', '/api/auth/recovery-codes', ['currentPassword' => 'current password long enough']),
            new Response()
        );
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(200, $response->getStatusCode());
        self::assertCount(10, $body['codes']);
        foreach ($body['codes'] as $code) {
            self::assertContains(hash('sha256', $code), $persistedHashes->values);
        }
        self::assertArrayNotHasKey('passwordHash', $body);
    }

    public function testLoginAndResetApplicationErrorsMapToStableHttpStatuses(): void
    {
        $_SESSION = [];
        $repository = $this->createMock(UserRepository::class);
        $repository->method('recordAttempt')->willReturn(1);
        $repository->method('findByEmail')->willReturn(null);
        $controller = new AuthController(new AuthService($repository), $repository, new ClientIpResolver([]));
        $login = $controller->login($this->createJsonRequest('POST', '/api/auth/login', ['email' => 'nobody@example.test', 'password' => 'guess']), new Response());
        self::assertSame(401, $login->getStatusCode());

        $repository->expects(self::once())->method('consumeRecoveryCodeAndReplacePassword')->willReturn(null);
        $reset = $controller->reset($this->createJsonRequest('POST', '/api/auth/reset-password', [
            'code' => str_repeat('a', 32), 'password' => 'new sufficiently long password',
        ]), new Response());
        self::assertSame(400, $reset->getStatusCode());
    }

    public function testRateLimitsMapTo429BeforeUserLookupOrCodeConsumption(): void
    {
        $_SESSION = [];
        $loginRepository = $this->createMock(UserRepository::class);
        $loginRepository->expects(self::exactly(2))->method('recordAttempt')->willReturnOnConsecutiveCalls(1, 31);
        $loginRepository->expects(self::never())->method('findByEmail');
        $login = (new AuthController(new AuthService($loginRepository), $loginRepository, new ClientIpResolver([])))->login(
            $this->createJsonRequest('POST', '/api/auth/login', ['email' => 'other@example.test', 'password' => 'guess']),
            new Response()
        );
        self::assertSame(429, $login->getStatusCode());

        $resetRepository = $this->createMock(UserRepository::class);
        $resetRepository->expects(self::exactly(2))->method('recordAttempt')->willReturnOnConsecutiveCalls(21, 1);
        $resetRepository->expects(self::never())->method('consumeRecoveryCodeAndReplacePassword');
        $reset = (new AuthController(new AuthService($resetRepository), $resetRepository, new ClientIpResolver([])))->reset(
            $this->createJsonRequest('POST', '/api/auth/reset-password', [
                'code' => str_repeat('a', 32), 'password' => 'new sufficiently long password',
            ]),
            new Response()
        );
        self::assertSame(429, $reset->getStatusCode());
    }

    public function testLoginUsesForwardedClientIpOnlyThroughConfiguredProxy(): void
    {
        $_SESSION = [];
        $repository = $this->createMock(UserRepository::class);
        $attemptKeys = [];
        $repository->method('recordAttempt')->willReturnCallback(
            static function (string $key) use (&$attemptKeys): int {
                $attemptKeys[] = $key;
                return 1;
            }
        );
        $repository->method('findByEmail')->willReturn(null);

        $request = $this->createJsonRequest(
            'POST',
            '/api/auth/login',
            ['email' => 'nobody@example.test', 'password' => 'guess'],
            ['X-Forwarded-For' => '198.51.100.20, 10.0.0.1'],
            ['REMOTE_ADDR' => '10.0.0.2']
        );
        $controller = new AuthController(
            new AuthService($repository),
            $repository,
            new ClientIpResolver(['10.0.0.0/8'])
        );

        $response = $controller->login($request, new Response());

        self::assertSame(401, $response->getStatusCode());
        self::assertSame([
            hash('sha256', 'login-account|nobody@example.test|198.51.100.20'),
            hash('sha256', 'login-origin|198.51.100.20'),
        ], $attemptKeys);
    }
}
