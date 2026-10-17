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
        public DateTimeImmutable $createdAt
    ) {
        if ($id < 0 || trim($name) === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Invalid user data.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 254) {
            throw new InvalidArgumentException('Invalid user email.');
        }
        if ($passwordHash === '') {
            throw new InvalidArgumentException('Invalid user credentials.');
        }
    }

    /** @return array{id:int,name:string,email:string,createdAt:string} */
    public function publicData(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email,
            'createdAt' => $this->createdAt->format(DATE_ATOM)];
    }
}
