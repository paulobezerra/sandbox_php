<?php
declare(strict_types=1);

namespace App\Domain\Plan;

use DomainException;

class PlanValidationException extends DomainException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Erro de validação do plano.'
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
