<?php

declare(strict_types=1);

namespace App\Tools;

use InvalidArgumentException;
use RuntimeException;

final class CoverageReportGate
{
    /** @return array{line: array{percentage: float, covered: int, total: int, passed: bool}, branch: array{percentage: float, covered: int, total: int, passed: bool}} */
    public static function evaluate(string $report, float $lineMinimum, float $branchMinimum): array
    {
        foreach ([$lineMinimum, $branchMinimum] as $minimum) {
            if (!is_finite($minimum) || $minimum < 0 || $minimum > 100) {
                throw new InvalidArgumentException('Minimum coverage must be between 0 and 100.');
            }
        }

        $xml = @simplexml_load_string($report, \SimpleXMLElement::class, LIBXML_NONET);
        $files = $xml === false ? false : $xml->xpath('/coverage/project/package/file/metrics');
        if ($files === false || count($files) === 0) {
            throw new InvalidArgumentException('Invalid Clover coverage report: expected file-level metrics.');
        }

        $lineCovered = 0;
        $lineTotal = 0;
        $branchCovered = 0;
        $branchTotal = 0;
        foreach ($files as $metrics) {
            [$covered, $total] = self::counts($metrics, 'coveredstatements', 'statements', 'line');
            $lineCovered += $covered;
            $lineTotal += $total;
            [$covered, $total] = self::counts($metrics, 'coveredconditionals', 'conditionals', 'branch');
            $branchCovered += $covered;
            $branchTotal += $total;
        }

        $line = self::metric($lineCovered, $lineTotal, 'line', $lineMinimum);
        $branch = self::metric($branchCovered, $branchTotal, 'branch', $branchMinimum);

        return ['line' => $line, 'branch' => $branch];
    }

    /** @return array{percentage: float, covered: int, total: int, passed: bool} */
    /** @return array{int, int} */
    private static function counts(\SimpleXMLElement $metrics, string $coveredName, string $totalName, string $label): array
    {
        $coveredValue = (string) $metrics[$coveredName];
        $totalValue = (string) $metrics[$totalName];
        if (!ctype_digit($coveredValue) || !ctype_digit($totalValue)) {
            throw new InvalidArgumentException("Invalid {$label} coverage metrics in Clover report.");
        }

        $covered = (int) $coveredValue;
        $total = (int) $totalValue;
        if ($covered > $total) {
            throw new InvalidArgumentException("Invalid {$label} coverage metrics in Clover report.");
        }

        return [$covered, $total];
    }

    /** @return array{percentage: float, covered: int, total: int, passed: bool} */
    private static function metric(int $covered, int $total, string $label, float $minimum): array
    {
        if ($total === 0) {
            throw new RuntimeException("No executable {$label} coverage data in Clover report.");
        }
        $percentage = round($covered / $total * 100, 2);
        return ['percentage' => $percentage, 'covered' => $covered, 'total' => $total, 'passed' => $percentage >= $minimum];
    }
}
