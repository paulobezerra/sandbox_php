<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Auth\AuthService;
use App\Domain\User\{User, UserRepository};
use App\Infrastructure\Http\Controller\AuthController;
use App\Infrastructure\I18n\Localization;
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

        $response = $this->controller($repository)->register(
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
        $controller = $this->controller($repository);

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

        $response = $this->controller($repository)->login(
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

    public function testAuthErrorsUseNegotiatedCatalogueAndStableReason(): void
    {
        $_SESSION = [];
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByEmail')->willReturn(null);
        $controller = $this->controller($repository);

        foreach ([
            ['en', 'The email or password is incorrect.'],
            ['pt-BR', 'E-mail ou senha incorretos.'],
        ] as [$locale, $message]) {
            $request = $this->createJsonRequest('POST', '/api/auth/login', [
                'email' => 'nobody@example.test',
                'password' => 'incorrect password',
            ], ['Accept-Language' => $locale]);
            $response = $controller->login($request, new Response());
            $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame(401, $response->getStatusCode());
            self::assertSame($message, $body['error']['message']);
            self::assertSame(401, $body['error']['status']);
        }
    }

    private function controller(UserRepository $repository): AuthController
    {
        return new AuthController(new AuthService($repository), $repository, Localization::load(['en', 'pt-BR'], 'en'));
    }
}
