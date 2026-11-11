<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../services/session_service.php';

/**
 * Class Name:  SessionMiddleware
 * Purpose:     Start the session with safe cookie settings and log out idle users.
 * Description: The session cookie cannot be read by JavaScript (HttpOnly), is not
 *              sent from other websites' forms (SameSite=Lax), and unknown session
 *              IDs are refused (strict mode). A user who has been inactive for
 *              30 minutes is logged out, so a shared computer is not left logged in.
 */
final class SessionMiddleware implements Middleware
{
    public const IDLE_TIMEOUT_SECONDS = 1800;
    private const LAST_ACTIVITY_KEY = 'lastActivityAt';

    /**
     * Function/Method Name: handle
     * Purpose:      Start the session, apply the idle timeout, then continue.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  The timeout message is shown on the login page the user is sent to.
     */
    public function handle(Request $request, callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off'),
            ]);
        }
        SessionService::startSession();

        if ($this->hasBeenIdleTooLong()) {
            SessionService::endLoggedInSession();
            SessionService::setFlashData('flashError', 'You were logged out after 30 minutes without activity. Please log in again.');
        }
        $_SESSION[self::LAST_ACTIVITY_KEY] = time();

        $next($request);
    }

    private function hasBeenIdleTooLong(): bool
    {
        $lastActivityAt = $_SESSION[self::LAST_ACTIVITY_KEY] ?? null;

        return SessionService::isLoggedIn() && is_int($lastActivityAt) && time() - $lastActivityAt > self::IDLE_TIMEOUT_SECONDS;
    }
}
