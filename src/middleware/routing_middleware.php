<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';

/**
 * Class Name:  RoutingMiddleware
 * Purpose:     Find the route for the request, or answer "not found" / "wrong method".
 * Description: Web routes come from src/config/routes.php (by ?action=) and API
 *              routes from src/config/api_routes.php (by path, e.g. GET /products/{id}).
 *              The route found is stored on the request for the next middleware.
 */
final class RoutingMiddleware implements Middleware
{
    private array $routes;
    private string $visitorHomeAction;
    private string $administratorHomeAction;

    public function __construct(array $routes, string $visitorHomeAction = 'shop', string $administratorHomeAction = 'dashboard')
    {
        $this->routes = $routes;
        $this->visitorHomeAction = $visitorHomeAction;
        $this->administratorHomeAction = $administratorHomeAction;
    }

    /**
     * Function/Method Name: handle
     * Purpose:      Match the route and check the HTTP method, then continue.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  404 for an unknown page; 405 with an "Allow" header when the
     *               page exists but was called with the wrong method (e.g. GET on a delete form).
     */
    public function handle(Request $request, callable $next): void
    {
        $route = $request->isApi() ? $this->matchApiRoute($request) : $this->matchWebRoute($request);
        if ($route === null) {
            return;
        }

        $request->setAttribute('route', $route);
        $next($request);
    }

    private function matchWebRoute(Request $request): ?array
    {
        $currentUser = $request->getAttribute('currentUser');
        $homeAction = $currentUser?->isAdministrator() ? $this->administratorHomeAction : $this->visitorHomeAction;
        $action = $request->getPath() === '' ? $homeAction : $request->getPath();
        $request->setAttribute('homeAction', $homeAction);

        if (!isset($this->routes[$action])) {
            http_response_code(404);
            ViewService::renderView('shared/not_found_page', [
                'pageTitle' => 'Page Not Found',
                'notFoundMessage' => 'The page you asked for does not exist.',
                'notFoundLinks' => ['Go to the home page' => buildUrl($homeAction)],
            ]);
            return null;
        }

        [$httpMethod, $controllerClass, $controllerMethod, $accessLevel, $section] = $this->routes[$action];
        if ($request->getHttpMethod() !== $httpMethod) {
            $this->respondWrongMethod($request, [$httpMethod]);
            return null;
        }

        return [
            'name' => $action,
            'controllerClass' => $controllerClass,
            'controllerMethod' => $controllerMethod,
            'accessLevel' => $accessLevel,
            'section' => $section,
            'params' => [],
        ];
    }

    /**
     * Function/Method Name: matchApiRoute
     * Purpose:      Match an API path such as "/products/12" to a route pattern.
     * Parameters:   Request $request
     * Return Value: ?array - the route with its path values (e.g. ["productId" => "12"]), or null after answering
     * Description:  Patterns use {name} for a path value; values are checked by the controller.
     */
    private function matchApiRoute(Request $request): ?array
    {
        $allowedMethods = [];
        foreach ($this->routes as $routeKey => [$controllerClass, $controllerMethod, $accessLevel]) {
            [$httpMethod, $pathPattern] = explode(' ', $routeKey, 2);
            $pathRegex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pathPattern) . '$#';
            if (!preg_match($pathRegex, $request->getPath(), $pathMatches)) {
                continue;
            }

            if ($request->getHttpMethod() !== $httpMethod) {
                $allowedMethods[] = $httpMethod;
                continue;
            }

            return [
                'name' => $routeKey,
                'controllerClass' => $controllerClass,
                'controllerMethod' => $controllerMethod,
                'accessLevel' => $accessLevel,
                'section' => 'api',
                'params' => array_filter($pathMatches, 'is_string', ARRAY_FILTER_USE_KEY),
            ];
        }

        if ($allowedMethods !== []) {
            $this->respondWrongMethod($request, $allowedMethods);
            return null;
        }

        ApiResponse::error(404, 'not_found', 'There is no API endpoint at ' . mb_substr($request->getPath(), 0, 80) . '. See docs/api.md for the list.')->send();

        return null;
    }

    private function respondWrongMethod(Request $request, array $allowedMethods): void
    {
        MiddlewareResponder::respondWithError(
            $request,
            405,
            'method_not_allowed',
            'Not Allowed',
            'This address only accepts ' . implode(' or ', $allowedMethods) . ' requests. Please use the buttons on the website.',
            ['Allow' => implode(', ', $allowedMethods)]
        );
    }
}
