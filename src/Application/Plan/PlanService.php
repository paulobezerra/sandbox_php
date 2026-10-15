<?php
declare(strict_types=1);

namespace App\Application\Plan;

use App\Domain\Plan\Plan;
use App\Domain\Plan\PlanNotFoundException;
use App\Domain\Plan\PlanRepository;

class PlanService
{
    public function __construct(
        private readonly PlanRepository $repository
    ) {
    }

    /**
     * @return array<int, array{id: ?int, name: string, price: float, description: ?string, created_at: ?string}>
     */
    public function listPlans(): array
    {
        $plans = $this->repository->findAll();
        return array_map(static fn(Plan $plan): array => $plan->toArray(), $plans);
    }

    public function getPlan(int $id): Plan
    {
        $plan = $this->repository->findById($id);
        if ($plan === null) {
            throw new PlanNotFoundException($id);
        }

        return $plan;
    }

    public function createPlan(PlanData $data): Plan
    {
        $plan = Plan::create(
            name: $data->name,
            price: $data->price,
            description: $data->description
        );

        return $this->repository->save($plan);
    }

    public function updatePlan(int $id, PlanData $data): Plan
    {
        $plan = $this->repository->findById($id);
        if ($plan === null) {
            throw new PlanNotFoundException($id);
        }

        $plan->update(
            name: $data->name,
            price: $data->price,
            description: $data->description
        );

        return $this->repository->save($plan);
    }

    public function deletePlan(int $id): bool
    {
        $deleted = $this->repository->delete($id);
        if (!$deleted) {
            throw new PlanNotFoundException($id);
        }

        return true;
    }
}
