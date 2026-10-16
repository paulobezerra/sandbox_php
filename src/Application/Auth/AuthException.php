<?php

declare(strict_types=1);

namespace App\Application\Auth;

use RuntimeException;

final class AuthException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
