<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/error_handling_middleware.php';
require_once __DIR__ . '/../services/auth_service.php';

/**
 * Class Name:  AuthenticationMiddleware
 * Purpose:     Connect to the database and find out who is logged in.
 * Description: Stores the database connection, AuthService and current user on
 *              the request, so later middleware and the controller reuse them.
 *              A failed connection becomes DatabaseUnavailableException, which
 *              ErrorHandlingMiddleware turns into a friendly "try again later" page.
 */
final class AuthenticationMiddleware implements Middleware
{
    /** @var callable(): PDO */
    private $connectionProvider;

    public function __construct(callable $connectionProvider)
    {
        $this->connectionProvider = $connectionProvider;
    }

    /**
     * Function/Method Name: handle
     * Purpose:      Open the connection, load the current user, then continue.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  The database password is never shown, only logged as "connection failed".
     */
    public function handle(Request $request, callable $next): void
    {
        try {
            $connection = ($this->connectionProvider)();
        } catch (PDOException $exception) {
            throw new DatabaseUnavailableException('Database connection failed: ' . $exception->getMessage(), 0, $exception);
        }

        $authService = new AuthService(new UserService($connection));
        $request->setAttribute('connection', $connection);
        $request->setAttribute('authService', $authService);
        $request->setAttribute('currentUser', $authService->getCurrentUser());

        $next($request);
    }
}
