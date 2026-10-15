<?php
declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Bootstrap\Config;
use PDO;

class ConnectionFactory
{
    public static function create(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config->databaseHost,
            $config->databasePort,
            $config->databaseName,
            $config->databaseCharset
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $config->databaseUser, $config->databasePassword, $options);
    }
}
