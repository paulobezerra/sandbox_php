<?php

declare(strict_types=1);

namespace App\Application\Auth;

use RuntimeException;

final class AuthException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct(match ($reason) {
            'invalid_credentials' => 'Credenciais inválidas.',
            'email_exists' => 'E-mail já cadastrado.',
            'invalid_name' => 'Nome inválido.',
            'invalid_email' => 'E-mail inválido.',
            'invalid_password' => 'A senha deve ter pelo menos 12 caracteres.',
            default => 'Dados inválidos.',
        });
    }
}
