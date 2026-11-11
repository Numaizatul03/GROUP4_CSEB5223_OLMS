<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';

/**
 * Class Name:  SecurityHeadersMiddleware
 * Purpose:     Tell the browser to apply extra protection to every page.
 * Description: These headers are a second line of defence on top of escapeHtml():
 *              even if a script slipped into a page, the Content-Security-Policy
 *              stops the browser running it, and the page cannot be shown inside
 *              another website's frame (clickjacking).
 */
final class SecurityHeadersMiddleware implements Middleware
{
    private const CONTENT_SECURITY_POLICY = "default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; "
        . "font-src https://fonts.gstatic.com; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'";

    /**
     * Function/Method Name: handle
     * Purpose:      Add the security headers, then continue.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  Headers are sent before any page content, so they apply to every answer.
     */
    public function handle(Request $request, callable $next): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Content-Security-Policy: ' . self::CONTENT_SECURITY_POLICY);
        header_remove('X-Powered-By');

        $next($request);
    }
}
