<?php
declare(strict_types=1);

namespace App\Bootstrap;

use App\Domain\Plan\PlanRepository;
use App\Infrastructure\Database\Repository\MysqlPlanRepository;
use PDO;
use Psr\Container\ContainerInterface;
use Slim\App as SlimApp;

final class LegacyPlanModule implements BootstrapModule
{
    public function __construct(private readonly ?PlanRepository $repository = null)
    {
    }

    /** @return array<class-string, object> */
    public function definitions(PDO $pdo): array
    {
        return [PlanRepository::class => $this->repository ?? new MysqlPlanRepository($pdo)];
    }

    /** @return list<string> */
    public function controllerFiles(): array
    {
        return [dirname(__DIR__) . '/Infrastructure/Http/Controller/PlanController.php'];
    }

    public function addMiddlewares(SlimApp $app, ContainerInterface $container, Config $config): void
    {
    }
}
