<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Auth\AuthService;
use App\Domain\User\{User, UserRepository};
use App\Infrastructure\Http\Controller\AuthController;
use App\Tests\TestCase;
use DateTimeImmutable;
use Slim\Psr7\Response;

final class AuthControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['user_id']);
        parent::tearDown();
    }

    public function testRegistrationReturnsPublicUserWithoutRoleOrCredentials(): void
    {
        $_SESSION = [];
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByEmail')->with('alice@example.test')->willReturn(null);
        $repository->expects(self::once())->method('create')->willReturn(new User(
            51,
            'Alice',
            'alice@example.test',
            password_hash('current password long enough', PASSWORD_DEFAULT),
            new DateTimeImmutable()
        ));

        $response = (new AuthController(new AuthService($repository), $repository))->register(
            $this->createJsonRequest('POST', '/api/auth/register', [
                'name' => 'Alice',
                'email' => 'alice@example.test',
                'password' => 'current password long enough',
            ]),
            new Response()
        );
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['id', 'name', 'email', 'createdAt'], array_keys($body['user']));
        self::assertArrayNotHasKey('role', $body['user']);
        self::assertArrayNotHasKey('passwordHash', $body['user']);
    }

    public function testInvalidLoginMapsTo401WithoutAttemptCounter(): void
    {
        $_SESSION = [];
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::exactly(2))->method('findByEmail')->with('nobody@example.test')->willReturn(null);
        $controller = new AuthController(new AuthService($repository), $repository);

        foreach ([1, 2] as $_attempt) {
            $response = $controller->login(
                $this->createJsonRequest('POST', '/api/auth/login', [
                    'email' => 'nobody@example.test',
                    'password' => 'incorrect password',
                ]),
                new Response()
            );
            self::assertSame(401, $response->getStatusCode());
            self::assertArrayNotHasKey('user_id', $_SESSION);
        }
    }

    public function testSuccessfulLoginRegeneratesSessionAndReturnsNoRole(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_id('auth-login-' . bin2hex(random_bytes(8)));
        session_start();
        $_SESSION = ['csrf' => 'old-token'];
        $oldSessionId = session_id();

        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByEmail')->with('alice@example.test')->willReturn(new User(
            51,
            'Alice',
            'alice@example.test',
            password_hash('current password long enough', PASSWORD_DEFAULT),
            new DateTimeImmutable()
        ));

        $response = (new AuthController(new AuthService($repository), $repository))->login(
            $this->createJsonRequest('POST', '/api/auth/login', [
                'email' => 'alice@example.test',
                'password' => 'current password long enough',
            ]),
            new Response()
        );
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertNotSame($oldSessionId, session_id());
        self::assertSame(51, $_SESSION['user_id']);
        self::assertNotSame('old-token', $_SESSION['csrf']);
        self::assertArrayNotHasKey('role', $body['user']);
    }
}
