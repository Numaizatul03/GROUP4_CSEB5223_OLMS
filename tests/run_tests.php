<?php

declare(strict_types=1);

/**
 * File:    run_tests.php
 * Purpose: Run every test file. Usage: php tests/run_tests.php
 *          Exit code 0 = all passed, 1 = at least one failure (used by GitHub Actions).
 */

require_once __DIR__ . '/test_helper.php';

$testFiles = [
    'ProductService' => __DIR__ . '/product_service_test.php',
    'ProductValidator' => __DIR__ . '/product_validator_test.php',
];

$totalFailed = 0;
foreach ($testFiles as $suiteName => $testFilePath) {
    echo "{$suiteName}\n";
    $totalFailed += runTests(require $testFilePath);
}

echo $totalFailed === 0 ? "\nAll tests passed.\n" : "\n{$totalFailed} test(s) failed.\n";
exit($totalFailed === 0 ? 0 : 1);
