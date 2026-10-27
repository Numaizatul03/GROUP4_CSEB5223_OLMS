<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/order_service.php';
require_once __DIR__ . '/../services/order_validator.php';

/**
 * Class Name:  OrderController
 * Purpose:     Customer side of orders: Checkout, View Order History, order details and cancel.
 * Description: A customer can only ever open or cancel their own orders; an
 *              order ID belonging to someone else is treated as "Not found".
 */
class OrderController extends BaseController
{
    private AuthService $authService;
    private OrderService $orderService;
    private CartService $cartService;
    private OrderValidator $orderValidator;

    public function __construct(AuthService $authService, OrderService $orderService, CartService $cartService, OrderValidator $orderValidator)
    {
        $this->authService = $authService;
        $this->orderService = $orderService;
        $this->cartService = $cartService;
        $this->orderValidator = $orderValidator;
    }

    /**
     * Function/Method Name: showCheckout
     * Purpose:      Show the order summary and the delivery address form.
     * Parameters:   none
     * Return Value: void
     * Description:  An empty cart, or a cart with too little stock, sends the
     *               customer back to the cart with the reason.
     */
    public function showCheckout(): void
    {
        $this->renderCheckout(['shippingAddress' => $this->getCustomer()->getShippingAddress()], []);
    }

    /**
     * Function/Method Name: placeOrder
     * Purpose:      Create the order (checkout() in the class diagram), then go to payment.
     * Parameters:   none (reads shippingAddress from $_POST)
     * Return Value: void
     * Description:  OrderService does the whole checkout in one transaction.
     */
    public function placeOrder(): void
    {
        $this->requireValidCsrfToken();
        $validationErrors = $this->orderValidator->validateCheckoutInput($_POST);
        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderCheckout($_POST, $validationErrors);
            return;
        }

        try {
            $orderId = $this->orderService->createOrderFromCart($this->getCustomerId(), trim((string) $_POST['shippingAddress']));
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError(buildUrl('cart'), $exception->getMessage());
        }

        $this->redirectWithSuccess(buildUrl('payment', ['orderId' => $orderId]), 'Order #' . $orderId . ' was placed. Please choose how to pay.');
    }

    /**
     * Function/Method Name: viewOrderHistory
     * Purpose:      List the customer's orders (View Order History use case).
     * Parameters:   none (optional orderStatus filter in $_GET)
     * Return Value: void
     * Description:  Newest orders first.
     */
    public function viewOrderHistory(): void
    {
        $orderStatus = (string) ($_GET['orderStatus'] ?? '');
        $isValidStatus = in_array($orderStatus, Order::ORDER_STATUSES, true);
        $orders = $this->orderService->searchOrders(['userId' => $this->getCustomerId(), 'orderStatus' => $isValidStatus ? $orderStatus : '']);

        ViewService::renderView('orders/history', [
            'pageTitle' => 'My Orders',
            'orders' => $orders,
            'selectedStatus' => $isValidStatus ? $orderStatus : '',
        ]);
    }

    /**
     * Function/Method Name: viewOrder
     * Purpose:      Show one of the customer's orders with items, payment and actions.
     * Parameters:   none (reads orderId from $_GET)
     * Return Value: void
     * Description:  Shows "Pay now" while unpaid and "Cancel order" while cancellable.
     */
    public function viewOrder(): void
    {
        $order = $this->findOwnOrder($this->readIdFromRequest($_GET, 'orderId'));
        if ($order === null) {
            $this->showNotFound('We could not find that order in your order history.', ['My orders' => buildUrl('orders')]);
            return;
        }

        ViewService::renderView('orders/details', [
            'pageTitle' => 'Order #' . $order->getOrderId(),
            'order' => $order,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: cancelOrder
     * Purpose:      Let the customer cancel their own order before it is shipped.
     * Parameters:   none (reads orderId from $_POST)
     * Return Value: void
     * Description:  Stock is returned and any completed payment is refunded.
     */
    public function cancelOrder(): void
    {
        $this->requireValidCsrfToken();
        $order = $this->findOwnOrder($this->readIdFromRequest($_POST, 'orderId'));
        if ($order === null) {
            $this->redirectWithError(buildUrl('orders'), 'We could not find that order.');
        }

        $orderUrl = buildUrl('order', ['orderId' => $order->getOrderId()]);
        try {
            $this->orderService->cancelOrder($order->getOrderId());
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError($orderUrl, $exception->getMessage());
        }

        $this->redirectWithSuccess($orderUrl, 'Order #' . $order->getOrderId() . ' was cancelled.');
    }

    private function renderCheckout(array $formInput, array $validationErrors): void
    {
        $shoppingCart = $this->cartService->getCartForUser($this->getCustomerId());
        if ($shoppingCart->isEmpty()) {
            $this->redirectWithError(buildUrl('cart'), 'Your cart is empty. Add some products before checking out.');
        }

        if ($shoppingCart->findUnavailableItems() !== []) {
            $this->redirectWithError(buildUrl('cart'), 'Some items have less stock than the quantity in your cart. Please update them first.');
        }

        ViewService::renderView('orders/checkout', [
            'pageTitle' => 'Checkout',
            'shoppingCart' => $shoppingCart,
            'formInput' => $formInput,
            'validationErrors' => $validationErrors,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    private function findOwnOrder(?int $orderId): ?Order
    {
        $order = $orderId === null ? null : $this->orderService->findOrderById($orderId);

        return $order !== null && $order->getUserId() === $this->getCustomerId() ? $order : null;
    }

    private function getCustomer(): Customer
    {
        return $this->authService->getCurrentUser();
    }

    private function getCustomerId(): int
    {
        return (int) $this->getCustomer()->getUserId();
    }
}
