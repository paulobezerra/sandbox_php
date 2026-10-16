<?php

declare(strict_types=1);

$config = \App\Bootstrap\Config::fromEnvironment();

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => $config->databaseHost,
            'name' => $config->databaseName,
            'user' => $config->databaseUser,
            'pass' => $config->databasePassword,
            'port' => $config->databasePort,
            'charset' => $config->databaseCharset,
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => $config->databaseHost,
            'name' => $config->databaseName,
            'user' => $config->databaseUser,
            'pass' => $config->databasePassword,
            'port' => $config->databasePort,
            'charset' => $config->databaseCharset,
        ],
    ],
    'version_order' => 'creation',
];
