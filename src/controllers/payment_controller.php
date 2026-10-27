<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/order_service.php';
require_once __DIR__ . '/../services/payment_service.php';
require_once __DIR__ . '/../services/order_validator.php';

/**
 * Class Name:  PaymentController
 * Purpose:     Make Payment (customer) and view, search, verify and refund payments (administrator).
 * Description: Payments are simulated. The payment form only asks for a method;
 *              no card or bank details are ever requested or stored.
 */
class PaymentController extends BaseController
{
    private AuthService $authService;
    private PaymentService $paymentService;
    private OrderService $orderService;
    private OrderValidator $orderValidator;

    public function __construct(AuthService $authService, PaymentService $paymentService, OrderService $orderService, OrderValidator $orderValidator)
    {
        $this->authService = $authService;
        $this->paymentService = $paymentService;
        $this->orderService = $orderService;
        $this->orderValidator = $orderValidator;
    }

    /**
     * Function/Method Name: showPaymentForm
     * Purpose:      Show the order total and the payment method choices.
     * Parameters:   none (reads orderId from $_GET)
     * Return Value: void
     * Description:  Only the customer's own unpaid orders can be paid.
     */
    public function showPaymentForm(): void
    {
        $order = $this->findOwnUnpaidOrder($this->readIdFromRequest($_GET, 'orderId'));
        if ($order === null) {
            return;
        }

        ViewService::renderView('payments/pay', [
            'pageTitle' => 'Make Payment',
            'order' => $order,
            'paymentMethods' => Payment::PAYMENT_METHODS,
            'paymentError' => null,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: makePayment
     * Purpose:      Pay for an order (processPayment() in the class diagram).
     * Parameters:   none (reads orderId and paymentMethod from $_POST)
     * Return Value: void
     * Description:  The amount is taken from the saved order, never from the form.
     */
    public function makePayment(): void
    {
        $this->requireValidCsrfToken();
        $order = $this->findOwnUnpaidOrder($this->readIdFromRequest($_POST, 'orderId'));
        if ($order === null) {
            return;
        }

        try {
            $this->paymentService->processPayment($order, (string) ($_POST['paymentMethod'] ?? ''));
        } catch (BusinessRuleException $exception) {
            http_response_code(422);
            ViewService::renderView('payments/pay', [
                'pageTitle' => 'Make Payment',
                'order' => $order,
                'paymentMethods' => Payment::PAYMENT_METHODS,
                'paymentError' => $exception->getMessage(),
                'csrfToken' => SessionService::getCsrfToken(),
            ]);
            return;
        }

        $this->redirectWithSuccess(
            buildUrl('order', ['orderId' => $order->getOrderId()]),
            'Thank you! Payment for order #' . $order->getOrderId() . ' was recorded.'
        );
    }

    /**
     * Function/Method Name: listPayments
     * Purpose:      Administrator list of payments with search by order, method and status.
     * Parameters:   none (reads criteria from $_GET)
     * Return Value: void
     * Description:  Shows "Not found" for a search with no match.
     */
    public function listPayments(): void
    {
        $searchErrors = $this->orderValidator->validatePaymentSearchInput($_GET);
        $searchCriteria = $this->orderValidator->toPaymentSearchCriteria($_GET);
        $hasSearched = $searchErrors === [] && OrderValidator::hasSearchCriteria($searchCriteria);

        ViewService::renderView('payments/list', [
            'pageTitle' => 'Payments',
            'searchInput' => $_GET,
            'searchErrors' => $searchErrors,
            'hasSearched' => $hasSearched,
            'searchResults' => $hasSearched ? $this->paymentService->searchPayments($searchCriteria) : [],
            'payments' => $this->paymentService->getAllPayments(),
            'totalRevenue' => $this->paymentService->getTotalRevenue(),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: verifyPayment
     * Purpose:      Mark a pending Cash on Delivery payment as received.
     * Parameters:   none (reads paymentId and returnTo from $_POST)
     * Return Value: void
     * Description:  Returns to the page the button was pressed on.
     */
    public function verifyPayment(): void
    {
        $this->changePaymentStatus(fn (int $paymentId) => $this->paymentService->verifyPayment($paymentId), 'verified');
    }

    public function refundPayment(): void
    {
        $this->changePaymentStatus(fn (int $paymentId) => $this->paymentService->refundPayment($paymentId), 'refunded');
    }

    private function changePaymentStatus(callable $statusChange, string $doneWord): void
    {
        $this->requireValidCsrfToken();
        $paymentId = $this->readIdFromRequest($_POST, 'paymentId');
        $returnUrl = ($_POST['returnTo'] ?? '') === 'order' && $this->readIdFromRequest($_POST, 'orderId') !== null
            ? buildUrl('admin_order', ['orderId' => $this->readIdFromRequest($_POST, 'orderId')])
            : buildUrl('payments');

        try {
            if ($paymentId === null) {
                throw new BusinessRuleException('No payment was selected.');
            }
            $statusChange($paymentId);
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError($returnUrl, $exception->getMessage());
        }

        $this->redirectWithSuccess($returnUrl, 'Payment #' . $paymentId . ' was ' . $doneWord . '.');
    }

    /**
     * Function/Method Name: findOwnUnpaidOrder
     * Purpose:      Load an order the current customer may pay for.
     * Parameters:   ?int $orderId
     * Return Value: ?Order - the order, or null after showing "Not found" / redirecting
     * Description:  Someone else's order is "Not found"; an already-paid order
     *               sends the customer to its details page.
     */
    private function findOwnUnpaidOrder(?int $orderId): ?Order
    {
        $order = $orderId === null ? null : $this->orderService->findOrderById($orderId);
        if ($order === null || $order->getUserId() !== $this->authService->getCurrentUser()->getUserId()) {
            $this->showNotFound('We could not find that order in your order history.', ['My orders' => buildUrl('orders')]);
            return null;
        }

        if (!$order->isAwaitingPayment()) {
            $this->redirectWithError(buildUrl('order', ['orderId' => $orderId]), 'Order #' . $orderId . ' does not need a payment.');
        }

        return $order;
    }
}
