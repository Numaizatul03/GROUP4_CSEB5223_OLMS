<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware_pipeline.php';
require_once __DIR__ . '/error_handling_middleware.php';
require_once __DIR__ . '/security_headers_middleware.php';
require_once __DIR__ . '/input_guard_middleware.php';
require_once __DIR__ . '/session_middleware.php';
require_once __DIR__ . '/authentication_middleware.php';
require_once __DIR__ . '/routing_middleware.php';
require_once __DIR__ . '/rate_limit_middleware.php';
require_once __DIR__ . '/authorization_middleware.php';
require_once __DIR__ . '/csrf_middleware.php';
require_once __DIR__ . '/../services/database_service.php';
require_once __DIR__ . '/../controllers/controller_factory.php';

/**
 * Class Name:  ApplicationPipeline
 * Purpose:     Build the middleware chain used by both index.php (website) and api.php (API).
 * Description: The order matters and is the same for every request:
 *                1. ErrorHandling   - safety net around everything below
 *                2. SecurityHeaders - browser protection headers
 *                3. InputGuard      - refuse lists, oversized values, broken JSON
 *                4. Session         - safe cookie, 30-minute idle logout
 *                5. Authentication  - database connection + who is logged in
 *                6. Routing         - which page / endpoint (404, 405)
 *                7. RateLimit       - login lockout, API request limit (429)
 *                8. Authorization   - role check (login redirect, 401, 403)
 *                9. Csrf            - form/token check on every change (419)
 *              then the controller runs.
 */
final class ApplicationPipeline
{
    public static function build(array $routes, bool $isDebugMode): MiddlewarePipeline
    {
        return (new MiddlewarePipeline())
            ->add(new ErrorHandlingMiddleware($isDebugMode))
            ->add(new SecurityHeadersMiddleware())
            ->add(new InputGuardMiddleware())
            ->add(new SessionMiddleware())
            ->add(new AuthenticationMiddleware(static fn (): PDO => DatabaseService::getConnection()))
            ->add(new RoutingMiddleware($routes))
            ->add(new RateLimitMiddleware())
            ->add(new AuthorizationMiddleware())
            ->add(new CsrfMiddleware());
    }

    /**
     * Function/Method Name: dispatchWebRequest
     * Purpose:      Final step for web pages: prepare the layout and call the controller.
     * Parameters:   Request $request - already checked by every middleware
     * Return Value: void
     * Description:  Web controllers print their own page (or redirect).
     */
    public static function dispatchWebRequest(Request $request): void
    {
        $route = $request->getAttribute('route');
        $currentUser = $request->getAttribute('currentUser');
        $cartItemCount = $currentUser instanceof Customer
            ? (new CartService($request->getAttribute('connection')))->countItemsForUser((int) $currentUser->getUserId())
            : 0;

        ViewService::setLayoutData([
            'currentUser' => $currentUser,
            'cartItemCount' => $cartItemCount,
            'currentAction' => $route['name'],
            'currentSection' => $route['section'],
        ]);

        ControllerFactory::createController($route['controllerClass'], $request)->{$route['controllerMethod']}();
    }

    /**
     * Function/Method Name: dispatchApiRequest
     * Purpose:      Final step for the API: call the controller and send its JSON answer.
     * Parameters:   Request $request
     * Return Value: void
     * Description:  API controllers return ApiResponse objects instead of printing.
     */
    public static function dispatchApiRequest(Request $request): void
    {
        $route = $request->getAttribute('route');
        $apiResponse = ControllerFactory::createController($route['controllerClass'], $request)->{$route['controllerMethod']}($request);
        $apiResponse->send();
    }
}
