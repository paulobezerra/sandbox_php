<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;
    public function findById(int $id): ?User;
    public function create(string $name, string $email, string $passwordHash): User;
    public function createAdmin(string $name, string $email, string $passwordHash): User;
    /** @param list<string> $codeHashes */
    public function replaceRecoveryCodes(int $userId, array $codeHashes): void;
    /** @param string $codeHash */
    public function consumeRecoveryCodeAndReplacePassword(string $codeHash, string $passwordHash, string $now): ?int;
    public function sessionVersionFor(int $id): ?int;
    public function recordAttempt(string $key, string $now): int;
    public function clearAttempts(string $key): void;
}
