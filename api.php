<?php

declare(strict_types=1);

/**
 * File:        api.php
 * Purpose:     Entry point of the OLMS JSON API (Lab 7 & 8).
 * Description: Uses the same middleware chain as the website, so the API gets the
 *              same error handling, input checks, login, role, rate-limit and CSRF
 *              protection. Endpoints are listed in src/config/api_routes.php and
 *              explained in docs/api.md.
 *              Examples:  api.php/products?keyword=eggs
 *                         api.php/products/FRP-001
 *                         api.php?path=/products/3/stock   (for servers without PATH_INFO)
 */

require_once __DIR__ . '/src/config/app_constants.php';
require_once __DIR__ . '/src/middleware/application_pipeline.php';

$appConfig = require __DIR__ . '/src/config/config.php';
ini_set('display_errors', '0'); // never mix PHP messages into JSON
ini_set('log_errors', '1');

ApplicationPipeline::build(require __DIR__ . '/src/config/api_routes.php', (bool) $appConfig['app_debug'])
    ->run(Request::fromGlobals(true), [ApplicationPipeline::class, 'dispatchApiRequest']);
