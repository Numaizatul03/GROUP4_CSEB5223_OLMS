<?php

declare(strict_types=1);

/**
 * File:        api_routes.php
 * Purpose:     Every endpoint of the OLMS JSON API (api.php).
 * Description: "METHOD /path/{value}" => [controller class, controller method, access level].
 *              Access levels are the same as in routes.php. The full description of
 *              each endpoint (inputs, outputs, errors) is in docs/api.md.
 */

return [
    'GET /health' => ['ApiCatalogController', 'showHealth', 'public'],
    'GET /products' => ['ApiCatalogController', 'listProducts', 'public'],
    'GET /products/{productKey}' => ['ApiCatalogController', 'showProduct', 'public'],
    'GET /products/{productKey}/stock' => ['ApiCatalogController', 'showStock', 'public'],
    'GET /categories' => ['ApiCatalogController', 'listCategories', 'public'],
    'GET /cart' => ['ApiCartController', 'showCart', 'customer'],
    'POST /cart/items' => ['ApiCartController', 'addItem', 'customer'],
    'GET /orders' => ['ApiOrderController', 'listOrders', 'administrator'],
];
