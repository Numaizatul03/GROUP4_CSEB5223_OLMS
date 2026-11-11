<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../services/session_service.php';

/**
 * Class Name:  CsrfMiddleware
 * Purpose:     Refuse any change (POST) that did not come from this website.
 * Description: Web forms send the token in a hidden "csrfToken" field; the
 *              website's JavaScript sends it to the API in the X-CSRF-Token
 *              header. Checking it here once protects every form, including
 *              new ones, even if a controller forgets its own check.
 */
final class CsrfMiddleware implements Middleware
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Function/Method Name: handle
     * Purpose:      Check the CSRF token on every changing request, then continue.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  An expired form gets a friendly "419" page with a link back,
     *               instead of the plain text message used before Lab 7.
     */
    public function handle(Request $request, callable $next): void
    {
        if (in_array($request->getHttpMethod(), self::SAFE_METHODS, true)) {
            $next($request);
            return;
        }

        $submittedToken = $request->isApi() ? $request->getHeader('X-CSRF-Token') : ($request->getBody()['csrfToken'] ?? null);
        if (!SessionService::isValidCsrfToken(is_string($submittedToken) ? $submittedToken : null)) {
            MiddlewareResponder::respondWithError(
                $request,
                419,
                'csrf_token_invalid',
                'Form Expired',
                'This form has expired or did not come from our website. Please go back, refresh the page and try again.'
            );
            return;
        }

        $next($request);
    }
}
