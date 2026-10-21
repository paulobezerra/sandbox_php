<?php

declare(strict_types=1);

use App\Tools\CoverageReportGate;

require_once __DIR__ . '/CoverageReportGate.php';

if ($argc !== 4 || !is_numeric($argv[2]) || !is_numeric($argv[3])) {
    fwrite(STDERR, "Usage: php tools/check-php-coverage.php <clover-report> <line-minimum> <branch-minimum>\n");
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
    $result = CoverageReportGate::evaluate($report, (float) $argv[2], (float) $argv[3]);
} catch (InvalidArgumentException | RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(2);
}

printf("PHP line coverage: %.2f%% (%d/%d), minimum %.2f%%\n", $result['line']['percentage'], $result['line']['covered'], $result['line']['total'], (float) $argv[2]);
printf("PHP branch coverage: %.2f%% (%d/%d), minimum %.2f%%\n", $result['branch']['percentage'], $result['branch']['covered'], $result['branch']['total'], (float) $argv[3]);
if (!$result['line']['passed'] || !$result['branch']['passed']) {
    exit(1);
}
