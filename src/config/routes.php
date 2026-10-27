<?php

declare(strict_types=1);

/**
 * File:        routes.php
 * Purpose:     The list of every page and form submission in OLMS.
 * Description: action name => [HTTP method, controller class, controller method,
 *              access level, section]. index.php checks the HTTP method and the
 *              access level before the controller runs, so no page can be
 *              opened by the wrong kind of user.
 *              Access levels: public (anyone), guest (not logged in),
 *              user (any logged-in user), customer, administrator.
 *              Section decides which menu item is highlighted.
 */

return [
    // Accounts (User, Customer, Administrator)
    'login' => ['GET', 'AuthController', 'showLoginForm', 'guest', 'account'],
    'login_submit' => ['POST', 'AuthController', 'loginUser', 'guest', 'account'],
    'register' => ['GET', 'AuthController', 'showRegisterForm', 'guest', 'account'],
    'register_submit' => ['POST', 'AuthController', 'registerCustomer', 'guest', 'account'],
    'logout' => ['POST', 'AuthController', 'logoutUser', 'user', 'account'],
    'profile' => ['GET', 'AuthController', 'showProfile', 'user', 'account'],
    'profile_update' => ['POST', 'AuthController', 'updateProfile', 'user', 'account'],
    'profile_result' => ['GET', 'AuthController', 'showProfileResult', 'user', 'account'],
    'password_update' => ['POST', 'AuthController', 'updatePassword', 'user', 'account'],

    // Shop (Customer: browse, search, view product details)
    'shop' => ['GET', 'ShopController', 'browseProducts', 'public', 'shop'],
    'shop_product' => ['GET', 'ShopController', 'viewProductDetails', 'public', 'shop'],

    // Shopping cart (ShoppingCart, CartItem)
    'cart' => ['GET', 'CartController', 'viewCart', 'customer', 'cart'],
    'cart_add' => ['POST', 'CartController', 'addToCart', 'customer', 'cart'],
    'cart_update' => ['POST', 'CartController', 'updateCart', 'customer', 'cart'],
    'cart_remove' => ['POST', 'CartController', 'removeFromCart', 'customer', 'cart'],
    'cart_clear' => ['POST', 'CartController', 'clearCart', 'customer', 'cart'],

    // Checkout, order history and payment (Order, OrderItem, Payment)
    'checkout' => ['GET', 'OrderController', 'showCheckout', 'customer', 'cart'],
    'checkout_submit' => ['POST', 'OrderController', 'placeOrder', 'customer', 'cart'],
    'orders' => ['GET', 'OrderController', 'viewOrderHistory', 'customer', 'orders'],
    'order' => ['GET', 'OrderController', 'viewOrder', 'customer', 'orders'],
    'order_cancel' => ['POST', 'OrderController', 'cancelOrder', 'customer', 'orders'],
    'payment' => ['GET', 'PaymentController', 'showPaymentForm', 'customer', 'orders'],
    'payment_submit' => ['POST', 'PaymentController', 'makePayment', 'customer', 'orders'],

    // Administrator dashboard
    'dashboard' => ['GET', 'DashboardController', 'showDashboard', 'administrator', 'dashboard'],

    // Manage Products (Lab 3 & 4 routes, now administrator-only)
    'list' => ['GET', 'ProductController', 'listProducts', 'administrator', 'products'],
    'view' => ['GET', 'ProductController', 'showProduct', 'administrator', 'products'],
    'create' => ['GET', 'ProductController', 'showCreateForm', 'administrator', 'products'],
    'store' => ['POST', 'ProductController', 'storeProduct', 'administrator', 'products'],
    'search' => ['GET', 'ProductController', 'searchProducts', 'administrator', 'products'],
    'edit_search' => ['GET', 'ProductController', 'showEditSearch', 'administrator', 'products'],
    'edit' => ['GET', 'ProductController', 'showEditForm', 'administrator', 'products'],
    'update' => ['POST', 'ProductController', 'updateProduct', 'administrator', 'products'],
    'edit_result' => ['GET', 'ProductController', 'showEditResult', 'administrator', 'products'],
    'delete_search' => ['GET', 'ProductController', 'showDeleteSearch', 'administrator', 'products'],
    'delete_confirm' => ['GET', 'ProductController', 'confirmDelete', 'administrator', 'products'],
    'destroy' => ['POST', 'ProductController', 'deleteProduct', 'administrator', 'products'],
    'delete_result' => ['GET', 'ProductController', 'showDeleteResult', 'administrator', 'products'],

    // Manage Inventory
    'inventory' => ['GET', 'InventoryController', 'showInventory', 'administrator', 'inventory'],
    'inventory_adjust' => ['POST', 'InventoryController', 'adjustStock', 'administrator', 'inventory'],

    // Manage Product Categories
    'categories' => ['GET', 'CategoryController', 'listCategories', 'administrator', 'categories'],
    'category_create' => ['GET', 'CategoryController', 'showCreateForm', 'administrator', 'categories'],
    'category_store' => ['POST', 'CategoryController', 'storeCategory', 'administrator', 'categories'],
    'category_edit' => ['GET', 'CategoryController', 'showEditForm', 'administrator', 'categories'],
    'category_update' => ['POST', 'CategoryController', 'updateCategory', 'administrator', 'categories'],
    'category_edit_result' => ['GET', 'CategoryController', 'showEditResult', 'administrator', 'categories'],
    'category_delete_confirm' => ['GET', 'CategoryController', 'confirmDelete', 'administrator', 'categories'],
    'category_delete' => ['POST', 'CategoryController', 'deleteCategory', 'administrator', 'categories'],
    'category_delete_result' => ['GET', 'CategoryController', 'showDeleteResult', 'administrator', 'categories'],

    // Manage Customers
    'customers' => ['GET', 'CustomerController', 'listCustomers', 'administrator', 'customers'],
    'customer' => ['GET', 'CustomerController', 'viewCustomer', 'administrator', 'customers'],
    'customer_create' => ['GET', 'CustomerController', 'showCreateForm', 'administrator', 'customers'],
    'customer_store' => ['POST', 'CustomerController', 'storeCustomer', 'administrator', 'customers'],
    'customer_edit' => ['GET', 'CustomerController', 'showEditForm', 'administrator', 'customers'],
    'customer_update' => ['POST', 'CustomerController', 'updateCustomer', 'administrator', 'customers'],
    'customer_edit_result' => ['GET', 'CustomerController', 'showEditResult', 'administrator', 'customers'],
    'customer_delete_confirm' => ['GET', 'CustomerController', 'confirmDelete', 'administrator', 'customers'],
    'customer_delete' => ['POST', 'CustomerController', 'deleteCustomer', 'administrator', 'customers'],
    'customer_delete_result' => ['GET', 'CustomerController', 'showDeleteResult', 'administrator', 'customers'],

    // Manage Orders and Payments
    'admin_orders' => ['GET', 'AdminOrderController', 'listOrders', 'administrator', 'admin_orders'],
    'admin_order' => ['GET', 'AdminOrderController', 'viewOrder', 'administrator', 'admin_orders'],
    'admin_order_status' => ['POST', 'AdminOrderController', 'updateOrderStatus', 'administrator', 'admin_orders'],
    'admin_order_status_result' => ['GET', 'AdminOrderController', 'showStatusResult', 'administrator', 'admin_orders'],
    'admin_order_delete_confirm' => ['GET', 'AdminOrderController', 'confirmDelete', 'administrator', 'admin_orders'],
    'admin_order_delete' => ['POST', 'AdminOrderController', 'deleteOrder', 'administrator', 'admin_orders'],
    'admin_order_delete_result' => ['GET', 'AdminOrderController', 'showDeleteResult', 'administrator', 'admin_orders'],
    'payments' => ['GET', 'PaymentController', 'listPayments', 'administrator', 'payments'],
    'payment_verify' => ['POST', 'PaymentController', 'verifyPayment', 'administrator', 'payments'],
    'payment_refund' => ['POST', 'PaymentController', 'refundPayment', 'administrator', 'payments'],
];
