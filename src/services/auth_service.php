<?php

declare(strict_types=1);

require_once __DIR__ . '/user_service.php';

/**
 * Class Name:  AuthService
 * Purpose:     Log users in and out and remember who is logged in.
 * Description: Implements login() and logout() from the User class in the class
 *              diagram. Only the user ID is kept in the session; the user is
 *              re-read from the database on each request so role changes and
 *              deleted accounts take effect immediately.
 */
class AuthService
{
    private const SESSION_USER_KEY = 'loggedInUserId';

    private UserService $userService;
    private ?User $currentUser = null;
    private bool $isCurrentUserLoaded = false;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Function/Method Name: loginUser
     * Purpose:      Log a user in when the email and password are correct.
     * Parameters:   string $email, string $password - values from the login form
     * Return Value: ?User - the logged-in user, or null when the login failed
     * Description:  The same "incorrect email or password" result is returned for an
     *               unknown email and a wrong password, so attackers cannot find out
     *               which emails are registered. The session ID is regenerated to
     *               prevent session fixation.
     */
    public function loginUser(string $email, string $password): ?User
    {
        $user = $this->userService->findUserByEmail($email);
        if ($user === null || !$user->verifyPassword($password)) {
            return null;
        }

        $this->renewSessionId();
        $_SESSION[self::SESSION_USER_KEY] = $user->getUserId();
        $this->currentUser = $user;
        $this->isCurrentUserLoaded = true;

        return $user;
    }

    /**
     * Function/Method Name: logoutUser
     * Purpose:      End the current login.
     * Parameters:   none
     * Return Value: void
     * Description:  Removes the user from the session and gives the browser a new session ID.
     */
    public function logoutUser(): void
    {
        unset($_SESSION[self::SESSION_USER_KEY]);
        $this->renewSessionId();
        $this->currentUser = null;
        $this->isCurrentUserLoaded = true;
    }

    /**
     * Function/Method Name: getCurrentUser
     * Purpose:      Return the user who is logged in on this browser.
     * Parameters:   none
     * Return Value: ?User - the user, or null for a visitor who is not logged in
     * Description:  Loaded once per request and then reused.
     */
    public function getCurrentUser(): ?User
    {
        if (!$this->isCurrentUserLoaded) {
            $userId = $_SESSION[self::SESSION_USER_KEY] ?? null;
            $this->currentUser = is_int($userId) ? $this->userService->findUserById($userId) : null;
            $this->isCurrentUserLoaded = true;
        }

        return $this->currentUser;
    }

    /**
     * Function/Method Name: hasAccess
     * Purpose:      Check whether the current visitor may open a page.
     * Parameters:   string $accessLevel - "public", "guest", "user", "customer" or "administrator"
     * Return Value: bool - true when access is allowed
     * Description:  "guest" pages (login, register) are only for visitors who are not logged in.
     */
    public function hasAccess(string $accessLevel): bool
    {
        $currentUser = $this->getCurrentUser();

        return match ($accessLevel) {
            'public' => true,
            'guest' => $currentUser === null,
            'user' => $currentUser !== null,
            User::ROLE_CUSTOMER, User::ROLE_ADMINISTRATOR => $currentUser?->getRole() === $accessLevel,
            default => false,
        };
    }

    /**
     * Function/Method Name: renewSessionId
     * Purpose:      Give the browser a new session ID when the login state changes.
     * Parameters:   none
     * Return Value: void
     * Description:  Prevents session fixation. Skipped when there is no active
     *               session (for example in the automated tests).
     */
    private function renewSessionId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
