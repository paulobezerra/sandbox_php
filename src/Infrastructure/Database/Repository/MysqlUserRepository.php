<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Repository;

use App\Domain\User\{User, UserRepository};
use DateTimeImmutable;
use PDO;

final readonly class MysqlUserRepository implements UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        return $this->map($statement->fetch(PDO::FETCH_ASSOC) ?: null);
    }

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $this->map($statement->fetch(PDO::FETCH_ASSOC) ?: null);
    }

    public function create(string $name, string $email, string $passwordHash): User
    {
        $statement = $this->pdo->prepare("INSERT INTO users(name, email, password_hash, role) VALUES(:name, :email, :hash, 'USER')");
        $statement->execute(['name' => $name, 'email' => $email, 'hash' => $passwordHash]);
        return $this->findById((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('Usuário não persistido.');
    }

    public function createAdmin(string $name, string $email, string $passwordHash): User
    {
        $statement = $this->pdo->prepare("INSERT INTO users(name, email, password_hash, role) VALUES(:name, :email, :hash, 'ADMIN')");
        $statement->execute(['name' => $name, 'email' => $email, 'hash' => $passwordHash]);
        return $this->findById((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('Administrador não persistido.');
    }

    public function replaceRecoveryCodes(int $userId, array $codeHashes): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM password_recovery_codes WHERE user_id = ?')->execute([$userId]);
            $insert = $this->pdo->prepare('INSERT INTO password_recovery_codes(user_id, code_hash) VALUES(?, ?)');
            foreach ($codeHashes as $hash) {
                $insert->execute([$userId, $hash]);
            }
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function consumeRecoveryCodeAndReplacePassword(string $codeHash, string $passwordHash, string $now): ?int
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('SELECT id, user_id FROM password_recovery_codes WHERE code_hash = ? AND consumed_at IS NULL FOR UPDATE');
            $statement->execute([$codeHash]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->pdo->commit();
                return null;
            }
            $userId = (int) $row['user_id'];
            $this->pdo->prepare('UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?')->execute([$passwordHash, $userId]);
            $this->pdo->prepare('UPDATE password_recovery_codes SET consumed_at = ? WHERE user_id = ? AND consumed_at IS NULL')->execute([$now, $userId]);
            $this->pdo->commit();
            return $userId;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function sessionVersionFor(int $id): ?int
    {
        $statement = $this->pdo->prepare('SELECT session_version FROM users WHERE id = ?');
        $statement->execute([$id]);
        $version = $statement->fetchColumn();
        return $version === false ? null : (int) $version;
    }

    public function recordAttempt(string $key, string $now): int
    {
        $this->pdo->prepare('DELETE FROM auth_attempts WHERE attempted_at < DATE_SUB(?, INTERVAL 15 MINUTE)')->execute([$now]);
        $this->pdo->prepare('INSERT INTO auth_attempts(attempt_key, attempted_at) VALUES(?, ?)')->execute([$key, $now]);
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM auth_attempts WHERE attempt_key = ? AND attempted_at >= DATE_SUB(?, INTERVAL 15 MINUTE)');
        $statement->execute([$key, $now]);
        return (int) $statement->fetchColumn();
    }

    public function clearAttempts(string $key): void
    {
        $this->pdo->prepare('DELETE FROM auth_attempts WHERE attempt_key = ?')->execute([$key]);
    }

    /** @param array<string, mixed>|null $row */
    private function map(?array $row): ?User
    {
        return $row ? new User(
            (int) $row['id'],
            $row['name'],
            $row['email'],
            $row['password_hash'],
            $row['role'],
            new DateTimeImmutable($row['created_at']),
            (int) ($row['session_version'] ?? 0)
        ) : null;
    }
}
