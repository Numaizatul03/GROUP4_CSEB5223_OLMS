<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/order_service.php';
require_once __DIR__ . '/../services/order_validator.php';

/**
 * Class Name:  AdminOrderController
 * Purpose:     The "Manage Orders" use case: search, view, update status, delete, display.
 * Description: Orders are created by customers at checkout; administrators move
 *              them through the status flow and remove finished ones.
 */
class AdminOrderController extends BaseController
{
    private const FIELD_LABELS = [
        'orderStatus' => 'Order Status',
        'paymentStatus' => 'Payment Status',
        'totalAmount' => 'Total',
        'updatedAt' => 'Last Updated',
    ];

    private OrderService $orderService;
    private OrderValidator $orderValidator;

    public function __construct(OrderService $orderService, OrderValidator $orderValidator)
    {
        $this->orderService = $orderService;
        $this->orderValidator = $orderValidator;
    }

    /**
     * Function/Method Name: listOrders
     * Purpose:      Display all orders with search by ID, customer, status and date range.
     * Parameters:   none (reads criteria from $_GET)
     * Return Value: void
     * Description:  A search with no match shows "Not found"; the full list follows.
     */
    public function listOrders(): void
    {
        $searchErrors = $this->orderValidator->validateOrderSearchInput($_GET);
        $searchCriteria = $this->orderValidator->toOrderSearchCriteria($_GET);
        $hasSearched = $searchErrors === [] && OrderValidator::hasSearchCriteria($searchCriteria);

        ViewService::renderView('admin_orders/list', [
            'pageTitle' => 'Manage Orders',
            'searchInput' => $_GET,
            'searchErrors' => $searchErrors,
            'hasSearched' => $hasSearched,
            'searchResults' => $hasSearched ? $this->orderService->searchOrders($searchCriteria) : [],
            'orders' => $this->orderService->getAllOrders(),
        ]);
    }

    /**
     * Function/Method Name: viewOrder
     * Purpose:      Show one order with the status form and payment actions.
     * Parameters:   none (reads orderId from $_GET)
     * Return Value: void
     * Description:  The status dropdown only offers the statuses allowed next.
     */
    public function viewOrder(): void
    {
        $order = $this->findRequestedOrder($_GET);
        if ($order === null) {
            return;
        }

        ViewService::renderView('admin_orders/view', [
            'pageTitle' => 'Order #' . $order->getOrderId(),
            'order' => $order,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: updateOrderStatus
     * Purpose:      Change an order's status (updateStatus() in the class diagram).
     * Parameters:   none (reads orderId and orderStatus from $_POST)
     * Return Value: void
     * Description:  Refused changes go back to the order with the reason; successful
     *               ones show before and after.
     */
    public function updateOrderStatus(): void
    {
        $this->requireValidCsrfToken();
        $order = $this->findRequestedOrder($_POST);
        if ($order === null) {
            return;
        }

        $previousDetails = $order->getOrderDetails();
        try {
            $this->orderService->updateStatus($order->getOrderId(), (string) ($_POST['orderStatus'] ?? ''));
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError(buildUrl('admin_order', ['orderId' => $order->getOrderId()]), $exception->getMessage());
        }

        SessionService::setFlashData('previousDetails', $previousDetails);
        ViewService::redirectTo(buildUrl('admin_order_status_result', ['orderId' => $order->getOrderId()]));
    }

    public function showStatusResult(): void
    {
        $order = $this->findRequestedOrder($_GET);
        if ($order === null) {
            return;
        }

        ViewService::renderView('shared/update_result', [
            'pageTitle' => 'Order Updated',
            'successMessage' => 'Order #' . $order->getOrderId() . ' is now "' . $order->getOrderStatus() . '".',
            'fieldLabels' => self::FIELD_LABELS,
            'moneyFields' => ['totalAmount'],
            'previousDetails' => SessionService::pullFlashData('previousDetails'),
            'currentDetails' => $order->getOrderDetails(),
            'backLinks' => ['Back to the order' => buildUrl('admin_order', ['orderId' => $order->getOrderId()]), 'All orders' => buildUrl('admin_orders')],
            'listTitle' => 'All Orders',
            'listPartial' => 'admin_orders/partials/order_table',
            'listData' => ['orders' => $this->orderService->getAllOrders(), 'highlightOrderId' => $order->getOrderId()],
        ]);
    }

    /**
     * Function/Method Name: confirmDelete
     * Purpose:      Show the whole order and ask "Are you sure?".
     * Parameters:   none (reads orderId from $_GET)
     * Return Value: void
     * Description:  Active orders show why they cannot be deleted yet.
     */
    public function confirmDelete(): void
    {
        $order = $this->findRequestedOrder($_GET);
        if ($order === null) {
            return;
        }

        $isFinished = in_array($order->getOrderStatus(), [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true);
        ViewService::renderView('shared/delete_confirm', [
            'pageTitle' => 'Delete Order - Confirm',
            'entityLabel' => 'order',
            'detailRows' => $this->toDetailRows($order),
            'blockedReason' => $isFinished ? null : 'Only Delivered or Cancelled orders can be deleted. This order is "' . $order->getOrderStatus() . '".',
            'deleteAction' => 'admin_order_delete',
            'hiddenFields' => ['orderId' => $order->getOrderId()],
            'cancelUrl' => buildUrl('admin_order', ['orderId' => $order->getOrderId()]),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    public function deleteOrder(): void
    {
        $this->requireValidCsrfToken();
        $order = $this->findRequestedOrder($_POST);
        if ($order === null) {
            return;
        }

        $countBefore = $this->orderService->countOrders();
        try {
            $this->orderService->deleteOrder($order->getOrderId());
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError(buildUrl('admin_order_delete_confirm', ['orderId' => $order->getOrderId()]), $exception->getMessage());
        }

        SessionService::setFlashData('deletedRecord', ['id' => $order->getOrderId(), 'rows' => $this->toDetailRows($order), 'countBefore' => $countBefore]);
        ViewService::redirectTo(buildUrl('admin_order_delete_result'));
    }

    public function showDeleteResult(): void
    {
        $deletedRecord = SessionService::pullFlashData('deletedRecord');
        if ($deletedRecord === null) {
            ViewService::redirectTo(buildUrl('admin_orders'));
        }

        ViewService::renderView('shared/delete_result', [
            'pageTitle' => 'Order Deleted',
            'entityLabel' => 'Order',
            'deletedRecord' => $deletedRecord,
            'isDeletionVerified' => $this->orderService->findOrderById((int) $deletedRecord['id']) === null,
            'countAfter' => $this->orderService->countOrders(),
            'listTitle' => 'All Orders',
            'listPartial' => 'admin_orders/partials/order_table',
            'listData' => ['orders' => $this->orderService->getAllOrders()],
        ]);
    }

    private function toDetailRows(Order $order): array
    {
        $itemSummary = array_map(
            static fn (OrderItem $orderItem) => $orderItem->getQuantity() . ' x ' . $orderItem->getProductName(),
            $order->getOrderItems()
        );

        return [
            'Order ID' => (string) $order->getOrderId(),
            'Customer' => $order->getCustomerName() . ' (' . $order->getCustomerEmail() . ')',
            'Order Date' => formatDateTime($order->getOrderDate()),
            'Items' => implode(', ', $itemSummary),
            'Total' => formatPrice($order->getTotalAmount()),
            'Order Status' => $order->getOrderStatus(),
            'Payment' => $order->getPayment() === null
                ? 'Not paid'
                : $order->getPayment()->getPaymentMethod() . ' - ' . $order->getPayment()->getPaymentStatus(),
        ];
    }

    private function findRequestedOrder(array $requestInput): ?Order
    {
        $orderId = $this->readIdFromRequest($requestInput, 'orderId');
        $order = $orderId === null ? null : $this->orderService->findOrderById($orderId);
        if ($order === null) {
            $this->showNotFound(
                'There is no order with ID "' . ($requestInput['orderId'] ?? '') . '". It may have been deleted already.',
                ['Search orders' => buildUrl('admin_orders')]
            );
        }

        return $order;
    }
}
