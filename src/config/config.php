<?php

declare(strict_types=1);

/**
 * File:        config.php
 * Purpose:     Central configuration for the Online Local Mart System (OLMS).
 * Description: Values can be overridden in two ways (highest priority first):
 *              1. Environment variables (OLMS_DB_DRIVER, OLMS_DB_HOST, ...)
 *              2. src/config/config_local.php (not committed to GitHub)
 *              Each member keeps their own MySQL password in config_local.php
 *              so that no password is ever pushed to the repository.
 */

$defaultConfig = [
    'db_driver' => 'mysql',
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'olms',
    'db_user' => 'root',
    'db_password' => '',
    'db_sqlite_path' => __DIR__ . '/../database/olms.sqlite',
];

$localConfigPath = __DIR__ . '/config_local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];

$environmentConfig = array_filter([
    'db_driver' => getenv('OLMS_DB_DRIVER'),
    'db_host' => getenv('OLMS_DB_HOST'),
    'db_port' => getenv('OLMS_DB_PORT'),
    'db_name' => getenv('OLMS_DB_NAME'),
    'db_user' => getenv('OLMS_DB_USER'),
    'db_password' => getenv('OLMS_DB_PASSWORD'),
    'db_sqlite_path' => getenv('OLMS_DB_SQLITE_PATH'),
], static fn ($configValue) => $configValue !== false);

return array_merge($defaultConfig, $localConfig, $environmentConfig);
