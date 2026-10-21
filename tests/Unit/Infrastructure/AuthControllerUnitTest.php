<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Application\Auth\AuthService;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Controller\AuthController;
use App\Infrastructure\I18n\Localization;
use App\Tests\TestCase;
use DateTimeImmutable;
use PDOException;
use Slim\Psr7\Response;

final class AuthControllerUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    public function testRegistrationSuccessAndDomainErrorsAreMappedToHttp(): void
    {
        $user = $this->user();
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByEmail')->willReturn(null);
        $repository->method('create')->willReturn($user);
        $controller = $this->controller($repository);

        self::assertSame(201, $controller->register($this->createJsonRequest('POST', '/api/auth/register', [
            'name' => 'Author', 'email' => 'author@example.test', 'password' => 'long-password-123',
        ]), new Response())->getStatusCode());
        self::assertSame(422, $controller->register($this->createJsonRequest('POST', '/api/auth/register', [
            'name' => '', 'email' => 'author@example.test', 'password' => 'long-password-123',
        ]), new Response())->getStatusCode());
        self::assertSame(422, $controller->register($this->createJsonRequest('POST', '/api/auth/register', [
            'name' => 'Author', 'email' => 'bad-email', 'password' => 'long-password-123',
        ]), new Response())->getStatusCode());
        self::assertSame(422, $controller->register($this->createJsonRequest('POST', '/api/auth/register', [
            'name' => 'Author', 'email' => 'author@example.test', 'password' => 'short',
        ]), new Response())->getStatusCode());
        self::assertSame(422, $controller->register($this->createJsonRequest('POST', '/api/auth/register', [
            'email' => 'author@example.test', 'password' => 'long-password-123',
        ]), new Response())->getStatusCode());
    }

    public function testRegistrationMapsDuplicateEmailAndRethrowsOtherStorageErrors(): void
    {
        $request = $this->createJsonRequest('POST', '/api/auth/register', [
            'name' => 'Author', 'email' => 'author@example.test', 'password' => 'long-password-123',
        ]);
        $duplicate = $this->createMock(UserRepository::class);
        $duplicate->method('findByEmail')->willReturn(null);
        $duplicate->method('create')->willThrowException(new PDOException('duplicate', 23000));
        self::assertSame(409, $this->controller($duplicate)->register($request, new Response())->getStatusCode());

        $other = $this->createMock(UserRepository::class);
        $other->method('findByEmail')->willReturn(null);
        $other->method('create')->willThrowException(new PDOException('storage', 42000));
        $this->expectException(PDOException::class);
        $this->controller($other)->register($request, new Response());
    }

    public function testExistingEmailAndMissingLoginFieldsHaveStableResponses(): void
    {
        $existing = $this->createMock(UserRepository::class);
        $existing->method('findByEmail')->willReturn($this->user());
        $controller = $this->controller($existing);
        self::assertSame(409, $controller->register($this->createJsonRequest('POST', '/api/auth/register', [
            'name' => 'Author', 'email' => 'author@example.test', 'password' => 'long-password-123',
        ]), new Response())->getStatusCode());

        $empty = $this->controller($this->createMock(UserRepository::class));
        self::assertSame(422, $empty->login($this->createJsonRequest('POST', '/api/auth/login', [
            'password' => 'a password',
        ]), new Response())->getStatusCode());
        self::assertSame(422, $empty->login($this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'author@example.test',
        ]), new Response())->getStatusCode());
    }

    public function testLogoutClearsSessionAndReturnsSuccess(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = ['user_id' => 5, 'csrf' => 'token'];
        $response = $this->controller($this->createMock(UserRepository::class))->logout(
            $this->createRequest('POST', '/api/auth/logout'),
            new Response()
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testLoginAndCurrentUserHandleSuccessAnonymousAndRemovedUser(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $user = $this->user();
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByEmail')->willReturnCallback(static function (string $email) use ($user): ?User {
            return $email === 'author@example.test' ? $user : null;
        });
        $repository->method('findById')->willReturnCallback(static fn (int $id): ?User => $id === 5 ? $user : null);
        $controller = $this->controller($repository);

        self::assertSame(200, $controller->login($this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'AUTHOR@example.test', 'password' => 'correct-password',
        ]), new Response())->getStatusCode());
        self::assertSame(401, $controller->login($this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'unknown@example.test', 'password' => 'wrong',
        ]), new Response())->getStatusCode());

        $_SESSION = ['csrf' => 'existing-token', 'user_id' => '5'];
        self::assertSame(200, $controller->me($this->createRequest('GET', '/api/auth/me'), new Response())->getStatusCode());
        self::assertNull(json_decode((string) $controller->me($this->createRequest('GET', '/api/auth/me'), new Response())->getBody(), true)['user']);
        $_SESSION = ['csrf' => 'existing-token', 'user_id' => 5];
        self::assertSame('Author', json_decode((string) $controller->me($this->createRequest('GET', '/api/auth/me'), new Response())->getBody(), true)['user']['name']);
        $_SESSION = [];
        self::assertSame(200, $controller->csrf($this->createRequest('GET', '/api/auth/csrf'), new Response())->getStatusCode());
        self::assertSame(200, $controller->csrf($this->createRequest('GET', '/api/auth/csrf'), new Response())->getStatusCode());
    }

    private function controller(UserRepository $repository): AuthController
    {
        return new AuthController(new AuthService($repository), $repository, Localization::load(['en', 'pt-BR'], 'en'));
    }

    private function user(): User
    {
        return new User(5, 'Author', 'author@example.test', password_hash('correct-password', PASSWORD_DEFAULT), new DateTimeImmutable());
    }
}
