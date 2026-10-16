<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\Config;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\MysqlUserRepository;
use App\Tests\TestCase;

final class RecoveryCodesPersistenceTest extends TestCase
{
    public function testFailedPasswordUpdatePreservesCodeAndSuccessfulResetConsumesAllCodesAndRevokesSessions(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $repository = new MysqlUserRepository($pdo);
        $email = 'recovery-' . bin2hex(random_bytes(8)) . '@example.test';
        $user = $repository->create('Recovery Test', $email, password_hash('current password long enough', PASSWORD_DEFAULT));
        $code = bin2hex(random_bytes(16));
        $codeHash = hash('sha256', $code);
        $otherHash = hash('sha256', bin2hex(random_bytes(16)));

        try {
            $repository->replaceRecoveryCodes($user->id, [$codeHash, $otherHash]);
            self::assertSame(0, $repository->sessionVersionFor($user->id));
            try {
                $repository->consumeRecoveryCodeAndReplacePassword($codeHash, str_repeat('x', 300), date('Y-m-d H:i:s'));
                self::fail('An oversized hash should make the database update fail.');
            } catch (\PDOException) {
                $statement = $pdo->prepare('SELECT consumed_at FROM password_recovery_codes WHERE code_hash = ?');
                $statement->execute([$codeHash]);
                self::assertNull($statement->fetchColumn());
                self::assertSame(0, $repository->sessionVersionFor($user->id));
            }

            self::assertSame($user->id, $repository->consumeRecoveryCodeAndReplacePassword(
                $codeHash,
                password_hash('new password long enough', PASSWORD_DEFAULT),
                date('Y-m-d H:i:s')
            ));
            self::assertSame(1, $repository->sessionVersionFor($user->id));
            self::assertNull($repository->consumeRecoveryCodeAndReplacePassword(
                $otherHash,
                password_hash('another new password long enough', PASSWORD_DEFAULT),
                date('Y-m-d H:i:s')
            ));
            self::assertNull($repository->consumeRecoveryCodeAndReplacePassword(
                $codeHash,
                password_hash('one more new password long enough', PASSWORD_DEFAULT),
                date('Y-m-d H:i:s')
            ));
            self::assertSame(1, $repository->sessionVersionFor($user->id));
        } finally {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user->id]);
        }
    }

    public function testReplacingRecoveryCodesInvalidatesThePreviousBatch(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $repository = new MysqlUserRepository($pdo);
        $email = 'recovery-' . bin2hex(random_bytes(8)) . '@example.test';
        $user = $repository->create('Recovery Test', $email, password_hash('current password long enough', PASSWORD_DEFAULT));
        $oldHash = hash('sha256', bin2hex(random_bytes(16)));

        try {
            $repository->replaceRecoveryCodes($user->id, [$oldHash]);
            $repository->replaceRecoveryCodes($user->id, [hash('sha256', bin2hex(random_bytes(16)))]);
            self::assertNull($repository->consumeRecoveryCodeAndReplacePassword(
                $oldHash,
                password_hash('new password long enough', PASSWORD_DEFAULT),
                date('Y-m-d H:i:s')
            ));
        } finally {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user->id]);
        }
    }

    public function testConcurrentUseOfTheSameRecoveryCodeSucceedsExactlyOnce(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open indisponível para o teste concorrente.');
        }
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $repository = new MysqlUserRepository($pdo);
        $email = 'recovery-' . bin2hex(random_bytes(8)) . '@example.test';
        $user = $repository->create('Recovery Test', $email, password_hash('current password long enough', PASSWORD_DEFAULT));
        $codeHash = hash('sha256', bin2hex(random_bytes(16)));
        $repository->replaceRecoveryCodes($user->id, [$codeHash]);
        $readyPath = tempnam(sys_get_temp_dir(), 'recovery-ready-');
        if ($readyPath === false) {
            self::fail('Não foi possível criar arquivo temporário de sincronização.');
        }
        unlink($readyPath);
        $goPath = $readyPath . '.go';
        $program = <<<'PHP'
require getcwd() . '/vendor/autoload.php';
$config = \App\Bootstrap\Config::fromEnvironment();
$repo = new \App\Infrastructure\Database\Repository\MysqlUserRepository(\App\Infrastructure\Database\ConnectionFactory::create($config));
file_put_contents($argv[1], "ready\n", FILE_APPEND | LOCK_EX);
$deadline = microtime(true) + 10;
while (!file_exists($argv[2]) && microtime(true) < $deadline) usleep(10000);
$result = $repo->consumeRecoveryCodeAndReplacePassword($argv[3], password_hash($argv[4], PASSWORD_DEFAULT), date('Y-m-d H:i:s'));
echo $result === null ? 'null' : (string) $result;
PHP;
        $processes = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $pipes = [];
                $process = proc_open([PHP_BINARY, '-r', $program, $readyPath, $goPath, $codeHash, 'new password long enough ' . $i], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                if (!is_resource($process)) {
                    self::fail('Não foi possível iniciar consumidor concorrente.');
                }
                fclose($pipes[0]);
                $processes[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 10;
            do {
                clearstatcache(true, $readyPath);
                $readyCount = is_file($readyPath) ? substr_count((string) file_get_contents($readyPath), "ready\n") : 0;
                if ($readyCount === 2) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            self::assertSame(2, $readyCount, 'Os consumidores não chegaram ao ponto de sincronização.');
            touch($goPath);
            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $results[] = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                self::assertSame(0, proc_close($process), $error);
            }
            sort($results);
            self::assertSame([(string) $user->id, 'null'], $results);
        } finally {
            if (is_file($readyPath)) {
                unlink($readyPath);
            }
            if (is_file($goPath)) {
                unlink($goPath);
            }
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user->id]);
        }
    }

    public function testAttemptStorageCountsPerKeyAndExpiresEntriesAfterTheWindow(): void
    {
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $repository = new MysqlUserRepository($pdo);
        $key = hash('sha256', 'rate-test|' . bin2hex(random_bytes(8)));
        $otherKey = hash('sha256', 'rate-test-other|' . bin2hex(random_bytes(8)));
        $now = date('Y-m-d H:i:s');
        $expired = date('Y-m-d H:i:s', time() - 901);
        try {
            self::assertSame(1, $repository->recordAttempt($key, $now));
            self::assertSame(2, $repository->recordAttempt($key, $now));
            self::assertSame(1, $repository->recordAttempt($otherKey, $now));
            $repository->clearAttempts($key);
            $pdo->prepare('INSERT INTO auth_attempts(attempt_key, attempted_at) VALUES(?, ?)')->execute([$key, $expired]);
            self::assertSame(1, $repository->recordAttempt($key, $now));
        } finally {
            $pdo->prepare('DELETE FROM auth_attempts WHERE attempt_key IN (?, ?)')->execute([$key, $otherKey]);
        }
    }
}
