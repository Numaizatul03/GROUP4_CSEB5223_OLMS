<?php

declare(strict_types=1);

/**
 * Class Name:  SessionService
 * Purpose:     Manage the PHP session: one-time (flash) messages and CSRF tokens.
 * Description: Flash data survives exactly one redirect, which lets the system
 *              follow the Post/Redirect/Get pattern (refreshing the page after
 *              saving never submits the form twice).
 */
class SessionService
{
    private const FLASH_KEY = 'flashData';
    private const CSRF_TOKEN_KEY = 'csrfToken';

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Function/Method Name: setFlashData
     * Purpose:      Store a value that is shown on the next page only.
     * Parameters:   string $flashKey - name of the value, e.g. "message"
     *               mixed $flashValue - the value to keep
     * Return Value: void
     * Description:  Used to carry success messages and before/after data across a redirect.
     */
    public static function setFlashData(string $flashKey, mixed $flashValue): void
    {
        $_SESSION[self::FLASH_KEY][$flashKey] = $flashValue;
    }

    /**
     * Function/Method Name: pullFlashData
     * Purpose:      Read a flash value and remove it from the session.
     * Parameters:   string $flashKey - name of the value
     * Return Value: mixed - the stored value, or null when there is none
     * Description:  Removing the value makes sure a message is shown only once.
     */
    public static function pullFlashData(string $flashKey): mixed
    {
        $flashValue = $_SESSION[self::FLASH_KEY][$flashKey] ?? null;
        unset($_SESSION[self::FLASH_KEY][$flashKey]);

        return $flashValue;
    }

    /**
     * Function/Method Name: getCsrfToken
     * Purpose:      Return the secret token placed in every form.
     * Parameters:   none
     * Return Value: string - 64-character random token for this session
     * Description:  Protects create/update/delete from requests sent by other websites.
     */
    public static function getCsrfToken(): string
    {
        if (empty($_SESSION[self::CSRF_TOKEN_KEY])) {
            $_SESSION[self::CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    /**
     * Function/Method Name: isValidCsrfToken
     * Purpose:      Check the token sent back by a form.
     * Parameters:   ?string $submittedToken - value of the hidden csrfToken field
     * Return Value: bool - true when it matches the session token
     * Description:  hash_equals() compares in constant time to avoid timing attacks.
     */
    public static function isValidCsrfToken(?string $submittedToken): bool
    {
        return is_string($submittedToken) && hash_equals(self::getCsrfToken(), $submittedToken);
    }
}
