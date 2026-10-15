<?php
declare(strict_types=1);

namespace App\Domain\User;

use DateTimeImmutable;

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
    }

    /** @return array{id:int,name:string,email:string,role:string,createdAt:string} */
    public function publicData(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email,
            'role' => $this->role, 'createdAt' => $this->createdAt->format(DATE_ATOM)];
    }
}
