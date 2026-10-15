<?php
declare(strict_types=1);

namespace App\Bootstrap;

use PDO;
use Psr\Container\ContainerInterface;
use Slim\App as SlimApp;

interface BootstrapModule
{
    /** @return array<class-string, mixed> */
    public function definitions(PDO $pdo): array;

    /** @return list<string> */
    public function controllerFiles(): array;

    public function addMiddlewares(SlimApp $app, ContainerInterface $container, Config $config): void;
}
