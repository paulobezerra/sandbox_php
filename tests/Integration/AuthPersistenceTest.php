<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Auth\{AuthException, AuthService};
use App\Bootstrap\Config;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\MysqlUserRepository;
use App\Tests\TestCase;

final class AuthPersistenceTest extends TestCase
{
    public function testRegistrationPersistsCredentialsAndRepeatedInvalidLoginsRemainInvalid(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $users = new MysqlUserRepository($pdo);
        $auth = new AuthService($users);
        $email = 'auth-' . bin2hex(random_bytes(8)) . '@example.test';
        $userId = null;

        try {
            $registered = $auth->register(' Auth User ', ' ' . strtoupper($email) . ' ', 'a sufficiently long password');
            $userId = $registered->id;

            self::assertSame('Auth User', $registered->name);
            self::assertSame($email, $registered->email);
            self::assertArrayNotHasKey('role', $registered->publicData());
            self::assertSame($registered->id, $auth->login($email, 'a sufficiently long password')->id);

            foreach ([1, 2, 3] as $_attempt) {
                try {
                    $auth->login($email, 'incorrect password');
                    self::fail('Expected invalid credentials.');
                } catch (AuthException $error) {
                    self::assertSame('invalid_credentials', $error->reason);
                }
            }
        } finally {
            if ($userId !== null) {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            }
        }
    }
}
