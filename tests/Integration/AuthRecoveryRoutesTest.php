<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\{App as BootstrapApp, Config};
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\MysqlUserRepository;
use App\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\App as SlimApp;

final class AuthRecoveryRoutesTest extends TestCase
{
    private ?Config $config = null;

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        if ($this->config !== null) {
            unset($_COOKIE[$this->config->sessionCookie]);
        }
        parent::tearDown();
    }

    public function testAdminCanRegenerateCodesResetPasswordAndInvalidateTwoSessions(): void
    {
        $this->config = Config::fromEnvironment();
        $pdo = ConnectionFactory::create($this->config);
        $users = new MysqlUserRepository($pdo);
        $app = BootstrapApp::create($pdo, $this->config);
        $email = 'recovery-http-' . bin2hex(random_bytes(8)) . '@example.test';
        $user = $users->createAdmin('Recovery Admin', $email, password_hash('current password long enough', PASSWORD_DEFAULT));
        $csrfA = bin2hex(random_bytes(32));
        $csrfB = bin2hex(random_bytes(32));
        $sessionA = $this->createSession($user->id, $user->sessionVersion, $csrfA);
        $sessionB = $this->createSession($user->id, $user->sessionVersion, $csrfB);
        $attemptKeys = [
            hash('sha256', 'recovery-generation|' . $user->id . '|unknown'),
            hash('sha256', 'reset-origin|unknown'),
        ];

        try {
            $first = $this->send($app, $sessionA, 'POST', '/api/auth/recovery-codes', ['currentPassword' => 'current password long enough'], $csrfA);
            self::assertSame(200, $first->getStatusCode());
            $firstCodes = $this->json($first)['codes'];
            self::assertCount(10, $firstCodes);

            $second = $this->send($app, $sessionA, 'POST', '/api/auth/recovery-codes', ['currentPassword' => 'current password long enough'], $csrfA);
            self::assertSame(200, $second->getStatusCode());
            $codes = $this->json($second)['codes'];
            self::assertCount(10, $codes);
            $storedHashes = $pdo->query('SELECT code_hash FROM password_recovery_codes WHERE user_id = ' . $user->id)->fetchAll(\PDO::FETCH_COLUMN);
            self::assertCount(10, $storedHashes);
            foreach ($codes as $code) {
                self::assertContains(hash('sha256', $code), $storedHashes);
            }
            foreach ($firstCodes as $code) {
                self::assertNotContains(hash('sha256', $code), $storedHashes);
            }

            $invalidAuth = $this->send($app, $sessionA, 'POST', '/api/auth/register', [
                'name' => ['Alice'], 'email' => 'invalid@example.test', 'password' => 'a password long enough',
            ], $csrfA);
            $invalidPost = $this->send($app, $sessionA, 'POST', '/api/admin/posts', [
                'title' => 7, 'content' => 'Text',
            ], $csrfA);
            $invalidPage = $this->send($app, $sessionA, 'POST', '/api/admin/pages', [
                'title' => 'Title', 'content' => 'Text', 'slug' => 'Invalid slug!',
            ], $csrfA);
            $arrayPayload = $this->sendRaw($app, $sessionA, 'POST', '/api/admin/posts', '[]', $csrfA);
            self::assertSame(422, $invalidAuth->getStatusCode());
            self::assertSame(422, $invalidPost->getStatusCode());
            self::assertSame(422, $invalidPage->getStatusCode());
            self::assertSame(400, $arrayPayload->getStatusCode());

            $usedCode = $codes[0];
            $attemptKeys[] = hash('sha256', 'reset-code|' . $usedCode . '|unknown');
            $reset = $this->send($app, $sessionA, 'POST', '/api/auth/reset-password', [
                'code' => $usedCode, 'password' => 'new password long enough',
            ], $csrfA);
            self::assertSame(200, $reset->getStatusCode());
            self::assertSame(1, $users->sessionVersionFor($user->id));

            $currentA = $this->send($app, $sessionA, 'GET', '/api/auth/me');
            $freshSessionA = session_id();
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            $currentB = $this->send($app, $sessionB, 'GET', '/api/auth/me');
            self::assertNull($this->json($currentA)['user']);
            self::assertNull($this->json($currentB)['user']);

            $retryCsrf = $this->json($currentA)['csrfToken'];
            $reuse = $this->send($app, $freshSessionA, 'POST', '/api/auth/reset-password', [
                'code' => $usedCode, 'password' => 'third password long enough',
            ], $retryCsrf);
            self::assertSame(400, $reuse->getStatusCode());
        } finally {
            $placeholders = implode(',', array_fill(0, count($attemptKeys), '?'));
            $pdo->prepare("DELETE FROM auth_attempts WHERE attempt_key IN ($placeholders)")->execute($attemptKeys);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user->id]);
        }
    }

    private function createSession(int $userId, int $version, string $csrf): string
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_name($this->config->sessionCookie);
        session_id(bin2hex(random_bytes(16)));
        session_start();
        $_SESSION = ['user_id' => $userId, 'auth_version' => $version, 'csrf' => $csrf];
        $id = session_id();
        session_write_close();
        return $id;
    }

    /** @param array<string, mixed>|null $data */
    private function send(SlimApp $app, string $sessionId, string $method, string $path, ?array $data = null, ?string $csrf = null): ResponseInterface
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_COOKIE[$this->config->sessionCookie] = $sessionId;
        session_name($this->config->sessionCookie);
        session_id($sessionId);
        $request = $data === null
            ? $this->createRequest($method, $path)
            : $this->createJsonRequest($method, $path, $data);
        if ($csrf !== null) {
            $request = $request->withHeader('X-CSRF-Token', $csrf);
        }
        return $app->handle($request);
    }

    private function sendRaw(SlimApp $app, string $sessionId, string $method, string $path, string $body, string $csrf): ResponseInterface
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_COOKIE[$this->config->sessionCookie] = $sessionId;
        session_name($this->config->sessionCookie);
        session_id($sessionId);
        $request = $this->createRequest($method, $path, ['Content-Type' => 'application/json', 'Accept' => 'application/json']);
        $request = $request->withBody((new StreamFactory())->createStream($body))->withHeader('X-CSRF-Token', $csrf);
        return $app->handle($request);
    }

    /** @return array<string, mixed> */
    private function json(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}
