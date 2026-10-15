<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Plan\PlanData;
use App\Application\Plan\PlanService;
use App\Domain\Plan\Money;
use App\Domain\Plan\Plan;
use App\Domain\Plan\PlanNotFoundException;
use App\Domain\Plan\PlanRepository;
use PHPUnit\Framework\TestCase;

class InMemoryPlanRepository implements PlanRepository
{
    /** @var array<int, Plan> */
    private array $plans = [];
    private int $autoIncrement = 1;

    public function findAll(): array
    {
        return array_values($this->plans);
    }

    public function findById(int $id): ?Plan
    {
        return $this->plans[$id] ?? null;
    }

    public function save(Plan $plan): Plan
    {
        if ($plan->getId() === null) {
            $id = $this->autoIncrement++;
            $plan->setId($id);
            $this->plans[$id] = $plan;
            return $plan;
        }

        $this->plans[$plan->getId()] = $plan;
        return $plan;
    }

    public function delete(int $id): bool
    {
        if (isset($this->plans[$id])) {
            unset($this->plans[$id]);
            return true;
        }

        return false;
    }
}

class PlanServiceTest extends TestCase
{
    private InMemoryPlanRepository $repository;
    private PlanService $service;

    protected function setUp(): void
    {
        $this->repository = new InMemoryPlanRepository();
        $this->service = new PlanService($this->repository);
    }

    public function testListPlansReturnsEmptyInitially(): void
    {
        $this->assertEmpty($this->service->listPlans());
    }

    public function testCreateAndGetPlan(): void
    {
        $dto = new PlanData('Plano Startup', Money::fromFloat(99.00), 'Para startups');
        $created = $this->service->createPlan($dto);

        $this->assertSame(1, $created->getId());
        $this->assertSame('Plano Startup', $created->getName());

        $fetched = $this->service->getPlan(1);
        $this->assertSame('Plano Startup', $fetched->getName());

        $list = $this->service->listPlans();
        $this->assertCount(1, $list);
        $this->assertSame('Plano Startup', $list[0]['name']);
    }

    public function testGetNonExistentPlanThrowsException(): void
    {
        $this->expectException(PlanNotFoundException::class);
        $this->service->getPlan(999);
    }

    public function testUpdatePlan(): void
    {
        $dto = new PlanData('Plano Antigo', Money::fromFloat(50.00), 'Desc');
        $created = $this->service->createPlan($dto);

        $updateDto = new PlanData('Plano Renovado', Money::fromFloat(75.00), 'Nova Desc');
        $updated = $this->service->updatePlan($created->getId(), $updateDto);

        $this->assertSame('Plano Renovado', $updated->getName());
        $this->assertSame(75.00, $updated->getPrice()->toFloat());
    }

    public function testUpdateNonExistentPlanThrowsException(): void
    {
        $this->expectException(PlanNotFoundException::class);
        $updateDto = new PlanData('Inexistente', Money::fromFloat(10.00));
        $this->service->updatePlan(999, $updateDto);
    }

    public function testDeletePlan(): void
    {
        $dto = new PlanData('Plano Para Deletar', Money::fromFloat(10.00));
        $created = $this->service->createPlan($dto);

        $this->assertTrue($this->service->deletePlan($created->getId()));
        $this->assertEmpty($this->service->listPlans());
    }

    public function testDeleteNonExistentPlanThrowsException(): void
    {
        $this->expectException(PlanNotFoundException::class);
        $this->service->deletePlan(999);
    }
}
