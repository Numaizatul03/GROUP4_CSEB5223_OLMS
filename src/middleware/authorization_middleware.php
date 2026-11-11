<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';

/**
 * Class Name:  AuthorizationMiddleware
 * Purpose:     Make sure the user may open the route (public, guest, user, customer, administrator).
 * Description: Web visitors are sent to the login page and back afterwards; the
 *              wrong role gets "403 Not Allowed". API callers get JSON 401 / 403.
 */
final class AuthorizationMiddleware implements Middleware
{
    /**
     * Function/Method Name: handle
     * Purpose:      Compare the route's access level with the current user, then continue.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  Uses AuthService::hasAccess(), the same rule the Lab 5 & 6 router used.
     */
    public function handle(Request $request, callable $next): void
    {
        $accessLevel = $request->getAttribute('route')['accessLevel'];
        if ($request->getAttribute('authService')->hasAccess($accessLevel)) {
            $next($request);
            return;
        }

        $request->isApi() ? $this->denyApi($request) : $this->denyWeb($request, $accessLevel);
    }

    private function denyApi(Request $request): void
    {
        $request->getAttribute('currentUser') === null
            ? ApiResponse::error(401, 'login_required', 'Please log in to use this part of the API.')->send()
            : ApiResponse::error(403, 'forbidden', 'Your account is not allowed to use this part of the API.')->send();
    }

    /**
     * Function/Method Name: denyWeb
     * Purpose:      Send a web user to the right place when access is refused.
     * Parameters:   Request $request, string $accessLevel
     * Return Value: void
     * Description:  Logged-in users opening login/register go home; visitors go to
     *               the login page (remembering the page for afterwards); others get 403.
     */
    private function denyWeb(Request $request, string $accessLevel): void
    {
        $currentUser = $request->getAttribute('currentUser');
        if ($accessLevel === 'guest') {
            ViewService::redirectTo(buildUrl($request->getAttribute('homeAction')));
        }

        if ($currentUser === null) {
            if ($request->getHttpMethod() === 'GET') {
                SessionService::setFlashData('returnUrl', 'index.php?' . http_build_query($request->getQuery()));
            }
            SessionService::setFlashData('flashError', 'Please log in to continue.');
            ViewService::redirectTo(buildUrl('login'));
        }

        http_response_code(403);
        ViewService::renderView('shared/access_denied', ['pageTitle' => 'Not Allowed', 'currentUser' => $currentUser]);
    }
}
