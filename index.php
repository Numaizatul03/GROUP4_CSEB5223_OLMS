<?php

declare(strict_types=1);

/**
 * File:        index.php
 * Purpose:     Front controller - the single entry point of the OLMS website.
 * Description: For every request it:
 *              1. finds the route for ?action=... (src/config/routes.php)
 *              2. checks the HTTP method (GET or POST)
 *              3. checks the user is allowed to open the page (login and role)
 *              4. builds the controller with its services and calls it.
 *              Run locally with: php -S localhost:8000 (then open http://localhost:8000)
 */

require_once __DIR__ . '/src/config/app_constants.php';
require_once __DIR__ . '/src/services/database_service.php';
require_once __DIR__ . '/src/services/session_service.php';
require_once __DIR__ . '/src/services/view_service.php';
require_once __DIR__ . '/src/services/auth_service.php';
require_once __DIR__ . '/src/services/cart_service.php';
foreach (glob(__DIR__ . '/src/controllers/*_controller.php') as $controllerFile) {
    require_once $controllerFile;
}

const DEFAULT_ACTION_FOR_VISITORS = 'shop';
const DEFAULT_ACTION_FOR_ADMINISTRATORS = 'dashboard';

/**
 * Function/Method Name: createController
 * Purpose:      Build a controller together with the services it needs.
 * Parameters:   string $controllerClass - class name from routes.php
 *               PDO $connection - shared database connection
 *               AuthService $authService - knows who is logged in
 * Return Value: object - the controller
 * Description:  Services are passed in through constructors (dependency
 *               injection), so controllers never create their own connections.
 */
function createController(string $controllerClass, PDO $connection, AuthService $authService): object
{
    $productService = new ProductService($connection);
    $categoryService = new CategoryService($connection);
    $userService = new UserService($connection);
    $cartService = new CartService($connection);
    $orderService = new OrderService($connection, $cartService);
    $paymentService = new PaymentService($connection);

    return match ($controllerClass) {
        'AuthController' => new AuthController($authService, $userService, new UserValidator()),
        'ShopController' => new ShopController($productService, $categoryService, new ProductValidator()),
        'CartController' => new CartController($authService, $cartService, new OrderValidator()),
        'OrderController' => new OrderController($authService, $orderService, $cartService, new OrderValidator()),
        'PaymentController' => new PaymentController($authService, $paymentService, $orderService, new OrderValidator()),
        'DashboardController' => new DashboardController($productService, $orderService, $paymentService, $userService, $categoryService),
        'ProductController' => new ProductController($productService, $categoryService, new ProductValidator()),
        'InventoryController' => new InventoryController($productService),
        'CategoryController' => new CategoryController($categoryService, new CategoryValidator()),
        'CustomerController' => new CustomerController($userService, new UserValidator(), $orderService),
        'AdminOrderController' => new AdminOrderController($orderService, new OrderValidator()),
    };
}

/**
 * Function/Method Name: denyAccess
 * Purpose:      Respond when the visitor may not open the requested page.
 * Parameters:   string $accessLevel - the route's access level
 *               ?User $currentUser - who is logged in (null for a visitor)
 * Return Value: never
 * Description:  Visitors are sent to the login page (and back afterwards);
 *               logged-in users get a "not allowed" page; logged-in users
 *               opening login/register go to their home page.
 */
function denyAccess(string $accessLevel, ?User $currentUser): never
{
    if ($accessLevel === 'guest') {
        ViewService::redirectTo(buildUrl($currentUser?->isAdministrator() ? DEFAULT_ACTION_FOR_ADMINISTRATORS : DEFAULT_ACTION_FOR_VISITORS));
    }

    if ($currentUser === null) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            SessionService::setFlashData('returnUrl', 'index.php?' . ($_SERVER['QUERY_STRING'] ?? ''));
        }
        SessionService::setFlashData('flashError', 'Please log in to continue.');
        ViewService::redirectTo(buildUrl('login'));
    }

    http_response_code(403);
    ViewService::renderView('shared/access_denied', ['pageTitle' => 'Not Allowed']);
    exit;
}

SessionService::startSession();
$routes = require __DIR__ . '/src/config/routes.php';

try {
    $connection = DatabaseService::getConnection();
} catch (PDOException $exception) {
    http_response_code(500);
    error_log('OLMS database connection failed: ' . $exception->getMessage());
    exit('Cannot connect to the database. Check src/config/config_local.php and that MySQL is running.');
}

$authService = new AuthService(new UserService($connection));
$currentUser = $authService->getCurrentUser();
$defaultAction = $currentUser?->isAdministrator() ? DEFAULT_ACTION_FOR_ADMINISTRATORS : DEFAULT_ACTION_FOR_VISITORS;
$requestedAction = (string) ($_GET['action'] ?? $defaultAction);
$route = $routes[$requestedAction] ?? null;

if ($route === null) {
    http_response_code(404);
    ViewService::renderView('shared/not_found_page', [
        'pageTitle' => 'Page Not Found',
        'notFoundMessage' => 'The page you asked for does not exist.',
        'notFoundLinks' => ['Go to the home page' => buildUrl($defaultAction)],
    ]);
    exit;
}

[$allowedHttpMethod, $controllerClass, $controllerMethod, $accessLevel, $section] = $route;
if ($_SERVER['REQUEST_METHOD'] !== $allowedHttpMethod) {
    http_response_code(405);
    exit('This page only accepts ' . $allowedHttpMethod . ' requests.');
}

if (!$authService->hasAccess($accessLevel)) {
    denyAccess($accessLevel, $currentUser);
}

ViewService::setLayoutData([
    'currentUser' => $currentUser,
    'cartItemCount' => $currentUser instanceof Customer ? (new CartService($connection))->countItemsForUser((int) $currentUser->getUserId()) : 0,
    'currentAction' => $requestedAction,
    'currentSection' => $section,
]);

createController($controllerClass, $connection, $authService)->$controllerMethod();
