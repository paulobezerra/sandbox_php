<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bootstrap\Config;
use App\Domain\Plan\Money;
use App\Domain\Plan\Plan;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\Repository\MysqlPlanRepository;
use App\Tests\TestCase;

class MysqlPlanRepositoryTest extends TestCase
{
    private MysqlPlanRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $pdo = ConnectionFactory::create(Config::fromEnvironment());
        $this->repository = new MysqlPlanRepository($pdo);
    }

    public function testRepositoryPersistsAndFindsAndDeletesPlan(): void
    {
        // 1. Save (Insert)
        $plan = Plan::create('Plano Repositório Teste', Money::fromFloat(149.90), 'Descrição Repositório');
        $saved = $this->repository->save($plan);

        $this->assertNotNull($saved->getId());
        $id = $saved->getId();

        // 2. Find by ID
        $found = $this->repository->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('Plano Repositório Teste', $found->getName());
        $this->assertSame(149.90, $found->getPrice()->toFloat());

        // 3. Save (Update)
        $found->update('Plano Repositório Atualizado', Money::fromFloat(199.90), 'Nova Descrição');
        $updated = $this->repository->save($found);
        $this->assertSame('Plano Repositório Atualizado', $updated->getName());
        $this->assertSame(199.90, $updated->getPrice()->toFloat());

        // 4. Find All contains saved
        $all = $this->repository->findAll();
        $this->assertNotEmpty($all);

        // 5. Delete
        $deleted = $this->repository->delete($id);
        $this->assertTrue($deleted);

        // 6. Find after delete returns null
        $this->assertNull($this->repository->findById($id));
    }
}
