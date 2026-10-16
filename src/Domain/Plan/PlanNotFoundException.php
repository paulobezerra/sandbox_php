<?php

declare(strict_types=1);

namespace App\Domain\Plan;

use DomainException;

class PlanNotFoundException extends DomainException
{
    public function __construct(int $id, string $message = 'Plano não encontrado')
    {
        parent::__construct(sprintf('%s (ID: %d)', $message, $id));
    }
}
