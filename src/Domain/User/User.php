<?php

declare(strict_types=1);

namespace App\Domain\User;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public string $role,
        public DateTimeImmutable $createdAt,
        public int $sessionVersion = 0
    ) {
        if ($id < 0 || $sessionVersion < 0 || trim($name) === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Dados de usuário inválidos.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 254) {
            throw new InvalidArgumentException('E-mail de usuário inválido.');
        }
        if (!in_array($role, ['USER', 'ADMIN'], true) || $passwordHash === '') {
            throw new InvalidArgumentException('Credenciais ou perfil de usuário inválidos.');
        }
    }

    /** @return array{id:int,name:string,email:string,role:string,createdAt:string} */
    public function publicData(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email,
            'role' => $this->role, 'createdAt' => $this->createdAt->format(DATE_ATOM)];
    }
}
