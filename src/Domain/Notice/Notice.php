<?php

declare(strict_types=1);

namespace App\Domain\Notice;

use InvalidArgumentException;

final readonly class Notice
{
    public function __construct(
        public string $title,
        public string $message
    ) {
        if (trim($title) === '' || mb_strlen($title) > 120) {
            throw new InvalidArgumentException('O título do aviso deve ter entre 1 e 120 caracteres.');
        }
        if (trim($message) === '') {
            throw new InvalidArgumentException('A mensagem do aviso é obrigatória.');
        }
    }
}
