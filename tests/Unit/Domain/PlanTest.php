<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Plan\Money;
use App\Domain\Plan\Plan;
use App\Domain\Plan\PlanValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PlanTest extends TestCase
{
    public function testCanCreatePlanSuccessfully(): void
    {
        $money = Money::fromFloat(49.90);
        $plan = Plan::create('Plano Pro', $money, 'Descrição do plano');

        $this->assertNull($plan->getId());
        $this->assertSame('Plano Pro', $plan->getName());
        $this->assertSame(49.90, $plan->getPrice()->toFloat());
        $this->assertSame(4990, $plan->getPrice()->toCents());
        $this->assertSame('Descrição do plano', $plan->getDescription());
    }

    public function testMoneyRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O preço não pode ser negativo.');

        Money::fromCents(-100);
    }

    public function testMoneyConversionsAndFormatting(): void
    {
        $money = Money::fromString('129.50');
        $this->assertSame(12950, $money->toCents());
        $this->assertSame(129.50, $money->toFloat());
        $this->assertSame('129.50', $money->toDecimal());
    }

    public function testPlanRejectsEmptyName(): void
    {
        $this->expectException(PlanValidationException::class);

        Plan::create('   ', Money::fromCents(1000));
    }

    public function testPlanRejectsNameTooShort(): void
    {
        $this->expectException(PlanValidationException::class);

        Plan::create('A', Money::fromCents(1000));
    }

    public function testPlanRejectsNameTooLong(): void
    {
        $this->expectException(PlanValidationException::class);

        Plan::create(str_repeat('A', 101), Money::fromCents(1000));
    }

    public function testPlanUpdateMutatesState(): void
    {
        $plan = Plan::create('Básico', Money::fromFloat(29.90), 'Desc');
        $plan->update('Intermediário', Money::fromFloat(39.90), 'Nova Desc');

        $this->assertSame('Intermediário', $plan->getName());
        $this->assertSame(39.90, $plan->getPrice()->toFloat());
        $this->assertSame('Nova Desc', $plan->getDescription());
    }

    public function testPlanToArrayFormat(): void
    {
        $plan = new Plan(1, 'Básico', Money::fromFloat(29.90), 'Desc', '2026-10-15 12:00:00');
        $array = $plan->toArray();

        $this->assertSame([
            'id' => 1,
            'name' => 'Básico',
            'price' => 29.90,
            'description' => 'Desc',
            'created_at' => '2026-10-15 12:00:00',
        ], $array);
    }
}
