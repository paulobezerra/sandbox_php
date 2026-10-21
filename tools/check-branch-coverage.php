<?php

declare(strict_types=1);

use App\Tools\BranchCoverageGate;

require_once __DIR__ . '/BranchCoverageGate.php';

if ($argc !== 3 || !is_numeric($argv[2]) || (float) $argv[2] < 0 || (float) $argv[2] > 100) {
    fwrite(STDERR, "Usage: php tools/check-branch-coverage.php <clover-report> <minimum-percent>\n");
    exit(2);
}

if (!is_file($argv[1]) || !is_readable($argv[1])) {
    fwrite(STDERR, "Coverage report not found or unreadable: {$argv[1]}\n");
    exit(2);
}

$report = file_get_contents($argv[1]);
if ($report === false) {
    fwrite(STDERR, "Could not read coverage report: {$argv[1]}\n");
    exit(2);
}

try {
    $result = BranchCoverageGate::evaluate($report, (float) $argv[2]);
} catch (InvalidArgumentException | RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(2);
}

printf("PHP branch coverage: %.2f%% (%d/%d), minimum %.2f%%\n", $result['percentage'], $result['covered'], $result['total'], (float) $argv[2]);
if (!$result['passed']) {
    exit(1);
}
