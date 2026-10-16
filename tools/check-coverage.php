<?php

declare(strict_types=1);

if ($argc !== 3 || !is_file($argv[1]) || !is_numeric($argv[2])) {
    fwrite(STDERR, "Usage: php tools/check-coverage.php <clover.xml> <minimum-percent>\n");
    exit(2);
}

$report = simplexml_load_file($argv[1]);
if ($report === false || !isset($report->project->metrics)) {
    fwrite(STDERR, "Invalid Clover coverage report.\n");
    exit(2);
}

$metrics = $report->project->metrics;
$valid = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percentage = $valid === 0 ? 100.0 : $covered / $valid * 100;
$minimum = (float) $argv[2];

printf("Executable-line coverage: %.2f%% (%d/%d), minimum %.2f%%\n", $percentage, $covered, $valid, $minimum);
if ($percentage < $minimum) {
    exit(1);
}
