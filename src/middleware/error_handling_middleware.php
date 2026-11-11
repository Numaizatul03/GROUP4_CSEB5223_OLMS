<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../services/app_logger.php';
require_once __DIR__ . '/../services/business_rule_exception.php';

/**
 * Class Name:  DatabaseUnavailableException
 * Purpose:     Signal that the database cannot be reached (MySQL stopped, wrong password).
 * Description: Lets ErrorHandlingMiddleware answer "503 - try again later"
 *              instead of a general error.
 */
final class DatabaseUnavailableException extends RuntimeException
{
}

/**
 * Class Name:  ErrorHandlingMiddleware
 * Purpose:     Catch every unexpected error and show a safe, friendly page instead.
 * Description: The outermost middleware. It turns PHP warnings into exceptions,
 *              throws away any half-printed page, logs the details with a reference
 *              code (AppLogger), and shows the user only a plain message and the code.
 *              Stack traces, SQL and file paths are never shown to users.
 */
final class ErrorHandlingMiddleware implements Middleware
{
    private bool $isDebugMode;

    public function __construct(bool $isDebugMode = false)
    {
        $this->isDebugMode = $isDebugMode;
    }

    /**
     * Function/Method Name: handle
     * Purpose:      Run the rest of the request inside a safety net.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  Output is buffered so a page that fails half-way is not sent in pieces.
     */
    public function handle(Request $request, callable $next): void
    {
        $bufferLevel = ob_get_level();
        ob_start();
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ((error_reporting() & $severity) === 0) {
                return false; // the error was silenced with @ on purpose
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $next($request);
            ob_end_flush();
        } catch (Throwable $error) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            $this->respondToError($request, $error);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Function/Method Name: respondToError
     * Purpose:      Choose the right answer for each kind of problem.
     * Parameters:   Request $request, Throwable $error
     * Return Value: void
     * Description:  Database down -> 503; a shop rule that no controller caught -> 409
     *               with its own message; anything else -> 500 with a reference code.
     */
    private function respondToError(Request $request, Throwable $error): void
    {
        if ($error instanceof DatabaseUnavailableException) {
            AppLogger::logError($error, $request->describe());
            MiddlewareResponder::respondWithError(
                $request,
                503,
                'service_unavailable',
                'Shop Temporarily Unavailable',
                'We cannot reach the shop database right now. Please try again in a few minutes.',
                ['Retry-After' => '60']
            );
            return;
        }

        if ($error instanceof BusinessRuleException) {
            MiddlewareResponder::respondWithError($request, 409, 'rule_broken', 'Action Not Allowed', $error->getMessage());
            return;
        }

        $referenceCode = AppLogger::logError($error, $request->describe());
        $message = 'Something went wrong on our side. Please try again. If it keeps happening, give the staff this code: ' . $referenceCode . '.';
        if ($this->isDebugMode) {
            $message .= ' [debug: ' . $error->getMessage() . ']';
        }

        if ($request->isApi()) {
            ApiResponse::error(500, 'server_error', $message)->send();
            return;
        }

        http_response_code(500);
        ViewService::renderView('shared/error_page', [
            'pageTitle' => 'Something Went Wrong',
            'errorMessage' => $message,
            'statusCode' => 500,
            'referenceCode' => $referenceCode,
        ]);
    }
}
