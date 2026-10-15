<?php
declare(strict_types=1);

namespace App\Bootstrap;

use App\Domain\Plan\PlanRepository;
use App\Infrastructure\Database\Repository\MysqlPlanRepository;
use PDO;

final class LegacyPlanModule
{
    /** @return array<class-string, object> */
    public static function definitions(PDO $pdo, ?PlanRepository $repository = null): array
    {
        return [PlanRepository::class => $repository ?? new MysqlPlanRepository($pdo)];
    }
}
