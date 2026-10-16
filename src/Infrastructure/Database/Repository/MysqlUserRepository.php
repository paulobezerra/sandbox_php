<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Repository;

use App\Domain\User\{User, UserRepository};
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
        $statement = $this->pdo->prepare('INSERT INTO users(name, email, password_hash) VALUES(:name, :email, :hash)');
        $statement->execute(['name' => $name, 'email' => $email, 'hash' => $passwordHash]);
        return $this->findById((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('User was not persisted.');
    }

    /** @param array<string, mixed>|null $row */
    private function map(?array $row): ?User
    {
        return $row ? new User(
            (int) $row['id'],
            $row['name'],
            $row['email'],
            $row['password_hash'],
            new \DateTimeImmutable($row['created_at'])
        ) : null;
    }
}
