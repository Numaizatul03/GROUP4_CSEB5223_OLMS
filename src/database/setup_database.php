<?php

declare(strict_types=1);

/**
 * File:        setup_database.php
 * Purpose:     Create the OLMS tables and load the sample data.
 * Usage:       php src/database/setup_database.php
 * Description: Reads the driver from src/config/config.php and runs the
 *              matching schema file. WARNING: existing products and
 *              categories are dropped and replaced with the sample data.
 */

if (PHP_SAPI !== 'cli') {
    exit('Run this script from the command line: php src/database/setup_database.php');
}

$appConfig = require __DIR__ . '/../config/config.php';
$isSqlite = $appConfig['db_driver'] === 'sqlite';

try {
    if ($isSqlite) {
        $connection = new PDO('sqlite:' . $appConfig['db_sqlite_path']);
        $schemaPath = __DIR__ . '/schema_sqlite.sql';
    } else {
        // Connect without a database name because the schema creates it.
        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $appConfig['db_host'], $appConfig['db_port']);
        $connection = new PDO($serverDsn, $appConfig['db_user'], $appConfig['db_password']);
        $schemaPath = __DIR__ . '/schema_mysql.sql';
    }

    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->exec((string) file_get_contents($schemaPath));

    echo 'Database ready (' . $appConfig['db_driver'] . '): ' . basename($schemaPath) . " loaded.\n";
} catch (PDOException $exception) {
    fwrite(STDERR, 'Database setup failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
