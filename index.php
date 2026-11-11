<?php

declare(strict_types=1);

/**
 * File:        index.php
 * Purpose:     Front controller - the single entry point of the OLMS website.
 * Description: Every request goes through the middleware chain in
 *              src/middleware/application_pipeline.php (error handling, security
 *              headers, input checks, session, login, routing, rate limits, role
 *              and CSRF checks) before the controller for ?action=... runs.
 *              Run locally with: php -S localhost:8000 (then open http://localhost:8000)
 */

require_once __DIR__ . '/src/config/app_constants.php';
require_once __DIR__ . '/src/middleware/application_pipeline.php';

$appConfig = require __DIR__ . '/src/config/config.php';
ini_set('display_errors', $appConfig['app_debug'] ? '1' : '0');
ini_set('log_errors', '1');

ApplicationPipeline::build(require __DIR__ . '/src/config/routes.php', (bool) $appConfig['app_debug'])
    ->run(Request::fromGlobals(false), [ApplicationPipeline::class, 'dispatchWebRequest']);
