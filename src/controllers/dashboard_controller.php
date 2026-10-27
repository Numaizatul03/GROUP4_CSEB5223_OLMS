<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/product_service.php';
require_once __DIR__ . '/../services/order_service.php';
require_once __DIR__ . '/../services/payment_service.php';
require_once __DIR__ . '/../services/user_service.php';
require_once __DIR__ . '/../services/category_service.php';

/**
 * Class Name:  DashboardController
 * Purpose:     The administrator's home page: a summary of every module.
 * Description: Shows that all modules are integrated - products, inventory,
 *              categories, customers, orders and payments on one page.
 */
class DashboardController extends BaseController
{
    private const RECENT_ORDER_LIMIT = 5;
    private const LOW_STOCK_LIMIT = 5;

    private ProductService $productService;
    private OrderService $orderService;
    private PaymentService $paymentService;
    private UserService $userService;
    private CategoryService $categoryService;

    public function __construct(
        ProductService $productService,
        OrderService $orderService,
        PaymentService $paymentService,
        UserService $userService,
        CategoryService $categoryService
    ) {
        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->userService = $userService;
        $this->categoryService = $categoryService;
    }

    /**
     * Function/Method Name: showDashboard
     * Purpose:      Show totals, orders by status, low-stock products and recent orders.
     * Parameters:   none
     * Return Value: void
     * Description:  Every number links to the module page where it can be managed.
     */
    public function showDashboard(): void
    {
        ViewService::renderView('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'productCount' => $this->productService->countProducts(),
            'lowStockCount' => $this->productService->countLowStockProducts(),
            'categoryCount' => $this->categoryService->countCategories(),
            'customerCount' => $this->userService->countCustomers(),
            'orderCount' => $this->orderService->countOrders(),
            'ordersByStatus' => $this->orderService->countOrdersByStatus(),
            'totalRevenue' => $this->paymentService->getTotalRevenue(),
            'lowStockProducts' => array_slice($this->productService->getProductsByStockLevel(), 0, self::LOW_STOCK_LIMIT),
            'recentOrders' => array_slice($this->orderService->getAllOrders(), 0, self::RECENT_ORDER_LIMIT),
        ]);
    }
}
