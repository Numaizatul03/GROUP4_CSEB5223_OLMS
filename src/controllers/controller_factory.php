<?php

declare(strict_types=1);

foreach (glob(__DIR__ . '/*_controller.php') as $controllerFile) {
    require_once $controllerFile;
}
require_once __DIR__ . '/../middleware/request.php';
require_once __DIR__ . '/../services/rate_limiter.php';

/**
 * Class Name:  ControllerFactory
 * Purpose:     Build any controller (web or API) together with the services it needs.
 * Description: Services get the request's database connection through their
 *              constructors (dependency injection). Moved here from index.php in
 *              Lab 7 so index.php and api.php share exactly the same set-up.
 */
final class ControllerFactory
{
    /**
     * Function/Method Name: createController
     * Purpose:      Create the controller named by the route.
     * Parameters:   string $controllerClass - class name from routes.php or api_routes.php
     *               Request $request - carries the connection, AuthService and RateLimiter
     * Return Value: object - the controller
     * Description:  An unknown class name is a programming error and is caught by
     *               ErrorHandlingMiddleware (UnhandledMatchError).
     */
    public static function createController(string $controllerClass, Request $request): object
    {
        $connection = $request->getAttribute('connection');
        $authService = $request->getAttribute('authService');
        $productService = new ProductService($connection);
        $categoryService = new CategoryService($connection);
        $userService = new UserService($connection);
        $cartService = new CartService($connection);
        $orderService = new OrderService($connection, $cartService);
        $paymentService = new PaymentService($connection);

        return match ($controllerClass) {
            'AuthController' => new AuthController(
                $authService,
                $userService,
                new UserValidator(),
                $request->getAttribute('rateLimiter') ?? new RateLimiter($connection),
                $request->getClientIp()
            ),
            'ShopController' => new ShopController($productService, $categoryService, new ProductValidator()),
            'CartController' => new CartController($authService, $cartService, new OrderValidator()),
            'OrderController' => new OrderController($authService, $orderService, $cartService, new OrderValidator()),
            'PaymentController' => new PaymentController($authService, $paymentService, $orderService, new OrderValidator()),
            'DashboardController' => new DashboardController($productService, $orderService, $paymentService, $userService, $categoryService),
            'ProductController' => new ProductController($productService, $categoryService, new ProductValidator()),
            'InventoryController' => new InventoryController($productService),
            'ProductHistoryController' => new ProductHistoryController($productService),
            'CategoryController' => new CategoryController($categoryService, new CategoryValidator()),
            'CustomerController' => new CustomerController($userService, new UserValidator(), $orderService),
            'AdminOrderController' => new AdminOrderController($orderService, new OrderValidator()),
            'ApiCatalogController' => new ApiCatalogController($productService, $categoryService, new ProductValidator(), $connection),
            'ApiCartController' => new ApiCartController($authService, $cartService, $productService, new OrderValidator()),
            'ApiOrderController' => new ApiOrderController($orderService, new OrderValidator()),
        };
    }
}
