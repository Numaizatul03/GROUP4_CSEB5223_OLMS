<?php

declare(strict_types=1);

/**
 * File:        test_helper.php
 * Purpose:     A very small test framework so tests run with plain PHP (no Composer).
 * Description: Each test is a named function that calls assertTrue()/assertSame().
 *              A failed assertion throws an exception that runTests() reports.
 */

require_once __DIR__ . '/../src/config/app_constants.php';
require_once __DIR__ . '/../src/services/database_service.php';
require_once __DIR__ . '/../src/services/product_service.php';
require_once __DIR__ . '/../src/services/category_service.php';
require_once __DIR__ . '/../src/services/product_validator.php';

/**
 * Function/Method Name: createTestConnection
 * Purpose:      Give each test a fresh database with the sample data.
 * Parameters:   none
 * Return Value: PDO - in-memory SQLite connection loaded with schema_sqlite.sql
 * Description:  In-memory means tests never touch the real MySQL data.
 */
function createTestConnection(): PDO
{
    $connection = DatabaseService::createConnection(['db_driver' => 'sqlite', 'db_sqlite_path' => ':memory:']);
    $connection->exec((string) file_get_contents(__DIR__ . '/../src/database/schema_sqlite.sql'));

    return $connection;
}

function assertTrue(bool $condition, string $failureMessage): void
{
    if (!$condition) {
        throw new RuntimeException($failureMessage);
    }
}

function assertSame(mixed $expectedValue, mixed $actualValue, string $failureMessage): void
{
    if ($expectedValue !== $actualValue) {
        throw new RuntimeException(
            $failureMessage . ' (expected ' . var_export($expectedValue, true) . ', got ' . var_export($actualValue, true) . ')'
        );
    }
}

/**
 * Function/Method Name: runTests
 * Purpose:      Run a list of test functions and print a summary.
 * Parameters:   array $testNames - names of the test functions to call
 * Return Value: int - number of failed tests (0 means everything passed)
 * Description:  Prints "PASS" or "FAIL" for every test.
 */
function runTests(array $testNames): int
{
    $failedCount = 0;

    foreach ($testNames as $testName) {
        try {
            $testName();
            echo "  PASS  {$testName}\n";
        } catch (Throwable $exception) {
            $failedCount++;
            echo "  FAIL  {$testName}: {$exception->getMessage()}\n";
        }
    }

    return $failedCount;
}
