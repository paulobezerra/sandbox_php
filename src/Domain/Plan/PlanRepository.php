<?php

declare(strict_types=1);

namespace App\Domain\Plan;

interface PlanRepository
{
    /**
     * @return array<int, Plan>
     */
    public function findAll(): array;

    public function findById(int $id): ?Plan;

    public function save(Plan $plan): Plan;

    public function delete(int $id): bool;
}
