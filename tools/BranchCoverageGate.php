<?php

declare(strict_types=1);

namespace App\Tools;

use InvalidArgumentException;
use RuntimeException;

final class BranchCoverageGate
{
    /** @return array{percentage: float, covered: int, total: int, passed: bool} */
    public static function evaluate(string $report, float $minimum): array
    {
        if ($minimum < 0 || $minimum > 100) {
            throw new InvalidArgumentException('Minimum coverage must be between 0 and 100.');
        }

        $xml = @simplexml_load_string($report, \SimpleXMLElement::class, LIBXML_NONET);
        $metrics = $xml === false ? false : $xml->xpath('/coverage/project/metrics');
        if ($metrics === false || count($metrics) !== 1) {
            throw new InvalidArgumentException('Invalid branch coverage report: expected a Clover project summary.');
        }

        $coveredValue = (string) $metrics[0]['coveredconditionals'];
        $totalValue = (string) $metrics[0]['conditionals'];
        if (!ctype_digit($coveredValue) || !ctype_digit($totalValue)) {
            throw new InvalidArgumentException('Invalid branch metrics in coverage report.');
        }

        $covered = (int) $coveredValue;
        $total = (int) $totalValue;
        if ($total === 0) {
            throw new RuntimeException('No executable branches in coverage report.');
        }
        if ($covered > $total) {
            throw new InvalidArgumentException('Invalid branch metrics in coverage report.');
        }

        $percentage = round($covered / $total * 100, 2);
        return [
            'percentage' => $percentage,
            'covered' => $covered,
            'total' => $total,
            'passed' => $percentage >= $minimum,
        ];
    }
}
