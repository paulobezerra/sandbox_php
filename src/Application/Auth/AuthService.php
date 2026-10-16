<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\User\User;
use App\Domain\User\UserRepository;

final readonly class AuthService
{
    public function __construct(private UserRepository $users)
    {
    }

    public function register(string $name, string $email, string $password): User
    {
        $name = trim($name);
        $email = mb_strtolower(trim($email));
        if ($name === '' || mb_strlen($name) > 120) {
            throw new AuthException('invalid_name');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 254) {
            throw new AuthException('invalid_email');
        }
        $this->validatePassword($password);
        if ($this->users->findByEmail($email) !== null) {
            throw new AuthException('email_exists');
        }
        return $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
    }

    public function login(string $email, string $password): User
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $user = $this->users->findByEmail($normalizedEmail);
        if ($user === null || !password_verify($password, $user->passwordHash)) {
            throw new AuthException('invalid_credentials');
        }
        return $user;
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 12 || strlen($password) > 4096) {
            throw new AuthException('invalid_password');
        }
    }
}
