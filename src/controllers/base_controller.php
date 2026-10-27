<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/session_service.php';
require_once __DIR__ . '/../services/view_service.php';
require_once __DIR__ . '/../services/business_rule_exception.php';

/**
 * Class Name:  BaseController
 * Purpose:     Small helpers shared by every module controller.
 * Description: Reading an ID from the request, checking the CSRF token and
 *              redirecting with a message are needed by every module, so they
 *              are written once here (no duplicated code).
 */
abstract class BaseController
{
    /**
     * Function/Method Name: readIdFromRequest
     * Purpose:      Read a record ID (e.g. "categoryId") from $_GET or $_POST.
     * Parameters:   array $requestInput - $_GET or $_POST; string $idName - field name
     * Return Value: ?int - the ID, or null when it is missing or not a whole number
     * Description:  Stops values such as "3abc" or "-1" reaching the database.
     */
    protected function readIdFromRequest(array $requestInput, string $idName): ?int
    {
        $idText = (string) ($requestInput[$idName] ?? '');

        return ctype_digit($idText) ? (int) $idText : null;
    }

    /**
     * Function/Method Name: requireValidCsrfToken
     * Purpose:      Refuse a form that did not come from this website.
     * Parameters:   none (reads csrfToken from $_POST)
     * Return Value: void - stops with HTTP 419 when the token is wrong
     * Description:  Every form that changes data includes the session's CSRF token.
     */
    protected function requireValidCsrfToken(): void
    {
        if (!SessionService::isValidCsrfToken($_POST['csrfToken'] ?? null)) {
            http_response_code(419);
            exit('Your form has expired. Please go back, refresh the page and try again.');
        }
    }

    protected function redirectWithSuccess(string $url, string $message): never
    {
        SessionService::setFlashData('flashSuccess', $message);
        ViewService::redirectTo($url);
    }

    protected function redirectWithError(string $url, string $message): never
    {
        SessionService::setFlashData('flashError', $message);
        ViewService::redirectTo($url);
    }

    /**
     * Function/Method Name: showNotFound
     * Purpose:      Show the shared "Not found" page.
     * Parameters:   string $message - what was not found
     *               array $links - button label => URL
     * Return Value: void
     * Description:  Sends HTTP 404 so browsers and tests can see the record is missing.
     */
    protected function showNotFound(string $message, array $links): void
    {
        http_response_code(404);
        ViewService::renderView('shared/not_found_page', [
            'pageTitle' => 'Not Found',
            'notFoundMessage' => $message,
            'notFoundLinks' => $links,
        ]);
    }
}
