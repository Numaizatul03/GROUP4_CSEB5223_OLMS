<?php

declare(strict_types=1);

/**
 * File:        index.php
 * Purpose:     Front controller - the single entry point of the OLMS website.
 * Description: Reads ?action=... from the URL, checks the HTTP method and calls
 *              the matching ProductController method. Run locally with:
 *              php -S localhost:8000   (then open http://localhost:8000)
 */

require_once __DIR__ . '/src/config/app_constants.php';
require_once __DIR__ . '/src/services/database_service.php';
require_once __DIR__ . '/src/controllers/product_controller.php';

const DEFAULT_ACTION = 'list';

// action name => [HTTP method, ProductController method]
const PRODUCT_ROUTES = [
    'list' => ['GET', 'listProducts'],
    'view' => ['GET', 'showProduct'],
    'create' => ['GET', 'showCreateForm'],
    'store' => ['POST', 'storeProduct'],
    'search' => ['GET', 'searchProducts'],
    'edit_search' => ['GET', 'showEditSearch'],
    'edit' => ['GET', 'showEditForm'],
    'update' => ['POST', 'updateProduct'],
    'edit_result' => ['GET', 'showEditResult'],
    'delete_search' => ['GET', 'showDeleteSearch'],
    'delete_confirm' => ['GET', 'confirmDelete'],
    'destroy' => ['POST', 'deleteProduct'],
    'delete_result' => ['GET', 'showDeleteResult'],
];

SessionService::startSession();

$requestedAction = (string) ($_GET['action'] ?? DEFAULT_ACTION);
$route = PRODUCT_ROUTES[$requestedAction] ?? null;

if ($route === null) {
    http_response_code(404);
    exit('Page not found.');
}

[$allowedHttpMethod, $controllerMethod] = $route;
if ($_SERVER['REQUEST_METHOD'] !== $allowedHttpMethod) {
    http_response_code(405);
    exit('This page only accepts ' . $allowedHttpMethod . ' requests.');
}

try {
    $connection = DatabaseService::getConnection();
} catch (PDOException $exception) {
    http_response_code(500);
    error_log('OLMS database connection failed: ' . $exception->getMessage());
    exit('Cannot connect to the database. Check src/config/config_local.php and that MySQL is running.');
}

$productController = new ProductController(
    new ProductService($connection),
    new CategoryService($connection),
    new ProductValidator()
);
$productController->$controllerMethod();
