<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/request.php';
require_once __DIR__ . '/../services/api_response.php';
require_once __DIR__ . '/../services/order_service.php';
require_once __DIR__ . '/../services/order_validator.php';

/**
 * Class Name:  ApiOrderController
 * Purpose:     Administrator-only list of orders through the API.
 * Description: Shows that the API applies roles too: customers get 403, visitors 401.
 */
final class ApiOrderController
{
    private OrderService $orderService;
    private OrderValidator $orderValidator;

    public function __construct(OrderService $orderService, OrderValidator $orderValidator)
    {
        $this->orderService = $orderService;
        $this->orderValidator = $orderValidator;
    }

    /**
     * Function/Method Name: listOrders
     * Purpose:      List orders, optionally filtered by status or date range.
     * Parameters:   Request $request - query: orderStatus, dateFrom, dateTo (same rules as Manage Orders)
     * Return Value: ApiResponse - 200 orders, or 422 with field errors
     * Description:  Reuses OrderValidator so the API and the admin page accept the same values.
     */
    public function listOrders(Request $request): ApiResponse
    {
        $fieldErrors = $this->orderValidator->validateOrderSearchInput($request->getQuery());
        if ($fieldErrors !== []) {
            return ApiResponse::error(422, 'validation_failed', 'Some filter values are not valid.', $fieldErrors);
        }

        $orders = array_map(static fn (Order $order) => [
            'id' => $order->getOrderId(),
            'customerName' => $order->getCustomerName(),
            'orderDate' => $order->getOrderDate(),
            'total' => $order->getTotalAmount(),
            'orderStatus' => $order->getOrderStatus(),
            'paymentStatus' => $order->getPayment()?->getPaymentStatus(),
            'itemCount' => count($order->getOrderItems()),
        ], $this->orderService->searchOrders($this->orderValidator->toOrderSearchCriteria($request->getQuery())));

        return ApiResponse::success($orders, ['total' => count($orders)]);
    }
}
