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
    'UserService' => __DIR__ . '/user_service_test.php',
    'CategoryService' => __DIR__ . '/category_service_test.php',
    'Cart, Order and Payment' => __DIR__ . '/cart_order_payment_test.php',
    'Category and Product Codes' => __DIR__ . '/code_generator_test.php',
];

$totalFailed = 0;
$totalTests = 0;
foreach ($testFiles as $suiteName => $testFilePath) {
    echo "{$suiteName}\n";
    $testNames = require $testFilePath;
    $totalTests += count($testNames);
    $totalFailed += runTests($testNames);
}

echo $totalFailed === 0 ? "\nAll {$totalTests} tests passed.\n" : "\n{$totalFailed} of {$totalTests} test(s) failed.\n";
exit($totalFailed === 0 ? 0 : 1);
