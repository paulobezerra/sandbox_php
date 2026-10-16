<?php

declare(strict_types=1);

namespace App\Application\Post;

use DomainException;

final class PostException extends DomainException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct();
    }
}
