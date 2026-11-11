<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../services/rate_limiter.php';
require_once __DIR__ . '/../services/input_guard.php';

/**
 * Class Name:  RateLimitMiddleware
 * Purpose:     Stop password guessing and API flooding.
 * Description: Login: after 5 wrong passwords for the same email from the same
 *              computer, that email is locked there for 15 minutes (AuthController
 *              records each failure). API: each computer may make 60 requests a
 *              minute. Blocked requests get "429 Too Many Requests" with Retry-After.
 */
final class RateLimitMiddleware implements Middleware
{
    public const LOGIN_MAX_FAILURES = 5;
    public const LOGIN_WINDOW_SECONDS = 900;
    public const API_MAX_REQUESTS = 60;
    public const API_WINDOW_SECONDS = 60;
    private const LOGIN_ROUTE = 'login_submit';

    /**
     * Function/Method Name: makeLoginKey
     * Purpose:      Build the counter key for login failures.
     * Parameters:   string $email, string $clientIp
     * Return Value: string - e.g. "login:siti@olms.test|127.0.0.1"
     * Description:  Shared with AuthController so both use the same counter.
     */
    public static function makeLoginKey(string $email, string $clientIp): string
    {
        return 'login:' . strtolower(trim($email)) . '|' . $clientIp;
    }

    /**
     * Function/Method Name: handle
     * Purpose:      Block the request when its counter is used up, then continue.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  Other web pages are not limited.
     */
    public function handle(Request $request, callable $next): void
    {
        $rateLimiter = new RateLimiter($request->getAttribute('connection'));
        $routeName = $request->getAttribute('route')['name'];

        if ($request->isApi()) {
            $apiKey = 'api:' . $request->getClientIp();
            if ($rateLimiter->tooManyAttempts($apiKey, self::API_MAX_REQUESTS, self::API_WINDOW_SECONDS)) {
                $this->respondTooMany($request, $rateLimiter->secondsUntilReset($apiKey, self::API_WINDOW_SECONDS), 'Too many requests. The API allows ' . self::API_MAX_REQUESTS . ' requests per minute.');
                return;
            }
            $rateLimiter->hit($apiKey, self::API_WINDOW_SECONDS);
        }

        if ($routeName === self::LOGIN_ROUTE) {
            $loginKey = self::makeLoginKey(InputGuard::readText($request->getBody(), 'email', 150), $request->getClientIp());
            if ($rateLimiter->tooManyAttempts($loginKey, self::LOGIN_MAX_FAILURES, self::LOGIN_WINDOW_SECONDS)) {
                $waitMinutes = (int) ceil($rateLimiter->secondsUntilReset($loginKey, self::LOGIN_WINDOW_SECONDS) / 60);
                $this->respondTooMany($request, $waitMinutes * 60, 'Too many wrong passwords for this account. For your safety, please wait ' . $waitMinutes . ' minute(s) and try again.');
                return;
            }
        }

        $request->setAttribute('rateLimiter', $rateLimiter);
        $next($request);
    }

    private function respondTooMany(Request $request, int $retryAfterSeconds, string $message): void
    {
        MiddlewareResponder::respondWithError($request, 429, 'too_many_requests', 'Please Wait', $message, ['Retry-After' => (string) max(1, $retryAfterSeconds)]);
    }
}
