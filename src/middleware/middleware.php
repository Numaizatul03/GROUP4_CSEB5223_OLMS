<?php

declare(strict_types=1);

require_once __DIR__ . '/request.php';
require_once __DIR__ . '/../services/api_response.php';
require_once __DIR__ . '/../services/view_service.php';

/**
 * Interface Name: Middleware
 * Purpose:        One step that every request passes through before the controller.
 * Description:    A middleware either lets the request continue by calling
 *                 $next($request), or stops it and answers straight away
 *                 (for example "please log in" or "too many attempts").
 */
interface Middleware
{
    /**
     * Function/Method Name: handle
     * Purpose:      Check or prepare the request, then pass it on (or stop it).
     * Parameters:   Request $request - the current request
     *               callable $next - the next step; call it as $next($request)
     * Return Value: void
     * Description:  Each middleware does one job only (Assignment Part 1, Section 7).
     */
    public function handle(Request $request, callable $next): void;
}

/**
 * Class Name:  MiddlewareResponder
 * Purpose:     Send a stop/error answer in the right format for the request.
 * Description: Web requests get a normal HTML page; API requests get JSON.
 *              Shared by every middleware so all error answers look the same.
 */
final class MiddlewareResponder
{
    /**
     * Function/Method Name: respondWithError
     * Purpose:      Stop a request with an error page or JSON error.
     * Parameters:   Request $request, int $statusCode, string $errorCode,
     *               string $title, string $message, array $headers - extra HTTP headers
     * Return Value: void
     * Description:  The HTML version uses the shared error_page view with a link home.
     */
    public static function respondWithError(
        Request $request,
        int $statusCode,
        string $errorCode,
        string $title,
        string $message,
        array $headers = []
    ): void {
        if ($request->isApi()) {
            ApiResponse::error($statusCode, $errorCode, $message, [], $headers)->send();
            return;
        }

        if (!headers_sent()) {
            http_response_code($statusCode);
            foreach ($headers as $headerName => $headerValue) {
                header($headerName . ': ' . $headerValue);
            }
        }
        ViewService::renderView('shared/error_page', [
            'pageTitle' => $title,
            'errorMessage' => $message,
            'statusCode' => $statusCode,
            'referenceCode' => null,
        ]);
    }
}
