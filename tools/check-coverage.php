<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/check-coverage.php <clover.xml> <minimum-percent>\n");
    exit(2);
}

$cloverPath = $argv[1];
$minimum = (float) $argv[2];

if (!is_file($cloverPath)) {
    fwrite(STDERR, sprintf("Coverage file not found: %s\n", $cloverPath));
    exit(2);
}

$xml = file_get_contents($cloverPath);

if ($xml === false) {
    fwrite(STDERR, sprintf("Unable to read coverage file: %s\n", $cloverPath));
    exit(2);
}

$matched = preg_match(
    '/<metrics\b(?=[^>]*\bfiles="\d+")(?=[^>]*\bstatements="(\d+)")(?=[^>]*\bcoveredstatements="(\d+)")[^>]*\/?>/s',
    $xml,
    $matches
);

if ($matched !== 1) {
    fwrite(STDERR, "Unable to read aggregate statement coverage from Clover XML.\n");
    exit(2);
}

$statements = (int) $matches[1];
$coveredStatements = (int) $matches[2];
$coverage = $statements === 0 ? 100.0 : ($coveredStatements / $statements) * 100;

printf(
    "Statement coverage: %.2f%% (%d/%d), required minimum: %.2f%%\n",
    $coverage,
    $coveredStatements,
    $statements,
    $minimum
);

if ($coverage + 0.00001 < $minimum) {
    fwrite(STDERR, "Coverage gate failed.\n");
    exit(1);
}

fwrite(STDOUT, "Coverage gate passed.\n");
