<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

class DatabaseHealthProbe
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function check(): void
    {
        $statement = $this->pdo->query('SELECT 1');
        $statement->fetch();
    }
}
