<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Plan\PlanData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PlanDataTest extends TestCase
{
    public function testFromArrayCreatesTypedPlanData(): void
    {
        $data = PlanData::fromArray([
            'name' => 'Plano Básico',
            'price' => '19.90',
            'description' => 'Descrição',
        ]);

        self::assertSame('Plano Básico', $data->name);
        self::assertSame(1990, $data->price->toCents());
        self::assertSame('Descrição', $data->description);
    }

    public function testFromArrayRejectsInvalidPriceInsteadOfDefaultingToZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PlanData::fromArray([
            'name' => 'Plano Básico',
            'price' => 'inválido',
        ]);
    }
}
