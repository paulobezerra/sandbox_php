<?php

declare(strict_types=1);

namespace App\Tests\Unit\Tools;

use App\Tools\CoverageReportGate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once dirname(__DIR__, 3) . '/tools/CoverageReportGate.php';

final class CoverageReportGateTest extends TestCase
{
    public function testPassesAtBothMinimumsAndReportsIndependentCounts(): void
    {
        self::assertSame([
            'line' => ['percentage' => 95.0, 'covered' => 95, 'total' => 100, 'passed' => true],
            'branch' => ['percentage' => 85.0, 'covered' => 17, 'total' => 20, 'passed' => true],
        ], CoverageReportGate::evaluate($this->report(95, 100, 17, 20), 95, 85));

        self::assertSame(100.0, CoverageReportGate::evaluate($this->report(101, 101, 21, 21), 95, 85)['line']['percentage']);
    }

    public function testLineAndBranchThresholdsFailIndependently(): void
    {
        $lineFailure = CoverageReportGate::evaluate($this->report(94, 100, 20, 20), 95, 85);
        self::assertFalse($lineFailure['line']['passed']);
        self::assertTrue($lineFailure['branch']['passed']);

        $branchFailure = CoverageReportGate::evaluate($this->report(100, 100, 16, 20), 95, 85);
        self::assertTrue($branchFailure['line']['passed']);
        self::assertFalse($branchFailure['branch']['passed']);
    }

    public function testAggregatesAllFilesIncludingAnUncoveredFile(): void
    {
        $report = '<coverage><project><package>'
            . '<file name="covered.php"><metrics coveredstatements="100" statements="100" coveredconditionals="10" conditionals="10"/></file>'
            . '<file name="uncovered.php"><metrics coveredstatements="0" statements="20" coveredconditionals="0" conditionals="0"/></file>'
            . '</package></project></coverage>';

        $result = CoverageReportGate::evaluate($report, 95, 85);
        self::assertSame(120, $result['line']['total']);
        self::assertSame(100, $result['line']['covered']);
        self::assertFalse($result['line']['passed']);
    }

    public function testRejectsMissingLineOrBranchMetrics(): void
    {
        foreach ([
            '<coverage><project><metrics coveredconditionals="20" conditionals="20"/></project></coverage>',
            '<coverage><project><metrics coveredstatements="20" statements="20"/></project></coverage>',
        ] as $report) {
            try {
                CoverageReportGate::evaluate($report, 95, 85);
                self::fail('Both line and branch data are required.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRejectsMalformedReportAndEmptyDenominators(): void
    {
        try {
            CoverageReportGate::evaluate('<coverage><broken>', 95, 85);
            self::fail('Malformed Clover must fail.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No executable line');
        CoverageReportGate::evaluate($this->report(0, 0, 20, 20), 95, 85);
    }

    public function testRejectsEmptyBranchDenominatorAndInvalidMetrics(): void
    {
        try {
            CoverageReportGate::evaluate($this->report(20, 20, 0, 0), 95, 85);
            self::fail('An empty branch denominator must fail.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('branch', $exception->getMessage());
        }

        foreach ([
            '<coverage><project><metrics coveredstatements="x" statements="20" coveredconditionals="20" conditionals="20"/></project></coverage>',
            '<coverage><project><metrics coveredstatements="20" statements="20" coveredconditionals="21" conditionals="20"/></project></coverage>',
        ] as $report) {
            try {
                CoverageReportGate::evaluate($report, 95, 85);
                self::fail('Invalid coverage metrics must fail.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    private function report(int $coveredLines, int $lines, int $coveredBranches, int $branches): string
    {
        return sprintf(
            '<coverage><project><package><file name="fixture.php"><metrics coveredstatements="%d" statements="%d" coveredconditionals="%d" conditionals="%d"/></file></package></project></coverage>',
            $coveredLines,
            $lines,
            $coveredBranches,
            $branches
        );
    }
}
