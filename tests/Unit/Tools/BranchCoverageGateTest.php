<?php

declare(strict_types=1);

namespace App\Tests\Unit\Tools;

use App\Tools\BranchCoverageGate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once dirname(__DIR__, 3) . '/tools/BranchCoverageGate.php';

final class BranchCoverageGateTest extends TestCase
{
    public function testPassesAtNinetyFivePercent(): void
    {
        self::assertSame(
            ['percentage' => 95.0, 'covered' => 19, 'total' => 20, 'passed' => true],
            BranchCoverageGate::evaluate($this->report(19, 20), 95.0)
        );
    }

    public function testFailsBelowNinetyFivePercent(): void
    {
        $result = BranchCoverageGate::evaluate($this->report(94, 100), 95.0);

        self::assertSame(94.0, $result['percentage']);
        self::assertFalse($result['passed']);
    }

    public function testRejectsEmptyReport(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid branch coverage report');

        BranchCoverageGate::evaluate('', 95.0);
    }

    public function testRejectsMalformedReport(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid branch coverage report');

        BranchCoverageGate::evaluate('<coverage><broken>', 95.0);
    }

    public function testRejectsEmptyBranchMetrics(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No executable branches');

        BranchCoverageGate::evaluate($this->report(0, 0), 95.0);
    }

    public function testRejectsInconsistentBranchMetrics(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid branch metrics');

        BranchCoverageGate::evaluate($this->report(21, 20), 95.0);
    }

    public function testRejectsMinimumOutsidePercentageRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum coverage must be between 0 and 100.');

        BranchCoverageGate::evaluate($this->report(20, 20), 101.0);
    }

    public function testRejectsLineOnlyCoverageReport(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid branch metrics');

        BranchCoverageGate::evaluate('<coverage><project><metrics statements="20" coveredstatements="20"/></project></coverage>', 95.0);
    }

    private function report(int $covered, int $total): string
    {
        return sprintf(
            '<coverage><project><metrics conditionals="%d" coveredconditionals="%d"/></project></coverage>',
            $total,
            $covered
        );
    }
}
