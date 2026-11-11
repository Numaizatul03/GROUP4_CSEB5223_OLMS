<?php

declare(strict_types=1);

/**
 * Class Name:  AppLogger
 * Purpose:     Write technical errors to a log file instead of showing them to users.
 * Description: Each error gets a short reference code. The user sees only the
 *              reference code; the developer finds the full details (message,
 *              file, line, page) in storage/logs/app.log under the same code.
 */
final class AppLogger
{
    private const LOG_FILE = __DIR__ . '/../../storage/logs/app.log';

    /**
     * Function/Method Name: logError
     * Purpose:      Save the details of an unexpected error.
     * Parameters:   Throwable $error - the exception or PHP error
     *               string $requestDescription - e.g. "GET index.php?action=shop"
     * Return Value: string - reference code such as "ERR-3F9A1C", shown to the user
     * Description:  Writing the log never stops the page: if the folder cannot be
     *               written, the error goes to PHP's own error log instead.
     */
    public static function logError(Throwable $error, string $requestDescription): string
    {
        $referenceCode = 'ERR-' . strtoupper(bin2hex(random_bytes(3)));
        $logLine = sprintf(
            "[%s] %s %s | %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $referenceCode,
            $requestDescription,
            $error::class,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );

        $logDirectory = dirname(self::LOG_FILE);
        $isWritable = (is_dir($logDirectory) || @mkdir($logDirectory, 0775, true)) && @file_put_contents(self::LOG_FILE, $logLine, FILE_APPEND | LOCK_EX) !== false;
        if (!$isWritable) {
            error_log(trim($logLine));
        }

        return $referenceCode;
    }
}
