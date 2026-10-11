<?php

declare(strict_types=1);

/**
 * File:        app_constants.php
 * Purpose:     Application-wide constants (UPPER_SNAKE_CASE, Assignment Part 1 Section 6.1).
 * Description: Kept apart from config.php so they are defined exactly once,
 *              while config.php can be read many times.
 */

const APP_NAME = 'Online Local Mart System';
const APP_SHORT_NAME = 'OLMS';
const CURRENCY_SYMBOL = 'RM';
const APP_TIMEZONE = 'Asia/Kuala_Lumpur';

date_default_timezone_set(APP_TIMEZONE);
