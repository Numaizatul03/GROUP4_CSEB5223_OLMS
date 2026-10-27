<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/order.php';
require_once __DIR__ . '/business_rule_exception.php';

/**
 * Class Name:  PaymentService
 * Purpose:     Record, read, search, verify and refund payments.
 * Description: Implements processPayment(), verifyPayment() and refundPayment()
 *              from the Payment class in the class diagram. Payments are
 *              simulated: no card or bank details are ever collected or stored.
 *              Payment records are never deleted, because money records must be kept.
 */
class PaymentService
{
    private const SELECT_PAYMENTS_SQL = 'SELECT pm.payment_id, pm.order_id, pm.payment_method, pm.payment_status,
                pm.amount, pm.payment_date, u.name AS customer_name
            FROM payments pm
            INNER JOIN orders o ON o.order_id = pm.order_id
            INNER JOIN users u ON u.user_id = o.user_id';

    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Function/Method Name: processPayment
     * Purpose:      Pay for an order (makePayment() in the use case).
     * Parameters:   Order $order - must be "Pending Payment" with no payment yet
     *               string $paymentMethod - one of Payment::PAYMENT_METHODS
     * Return Value: int - ID of the new payment
     * Description:  The amount always comes from the order, never from the form.
     *               Online methods complete at once and the order becomes "Paid";
     *               Cash on Delivery stays "Pending" and the order goes to "Processing".
     */
    public function processPayment(Order $order, string $paymentMethod): int
    {
        if (!in_array($paymentMethod, Payment::PAYMENT_METHODS, true)) {
            throw new BusinessRuleException('Please choose one of the listed payment methods.');
        }

        if (!$order->isAwaitingPayment()) {
            throw new BusinessRuleException('Order #' . $order->getOrderId() . ' does not need a payment.');
        }

        $paymentStatus = Payment::getStatusForMethod($paymentMethod);
        $newOrderStatus = $paymentStatus === Payment::STATUS_COMPLETED ? Order::STATUS_PAID : Order::STATUS_PROCESSING;
        $currentTime = date('Y-m-d H:i:s');

        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare(
                'INSERT INTO payments (order_id, payment_method, payment_status, amount, payment_date)
                 VALUES (:orderId, :paymentMethod, :paymentStatus, :amount, :paymentDate)'
            );
            $statement->execute([
                'orderId' => $order->getOrderId(),
                'paymentMethod' => $paymentMethod,
                'paymentStatus' => $paymentStatus,
                'amount' => $order->getTotalAmount(),
                'paymentDate' => $currentTime,
            ]);
            $paymentId = (int) $this->connection->lastInsertId();

            $statement = $this->connection->prepare(
                'UPDATE orders SET order_status = :orderStatus, updated_at = :updatedAt WHERE order_id = :orderId'
            );
            $statement->execute(['orderStatus' => $newOrderStatus, 'updatedAt' => $currentTime, 'orderId' => $order->getOrderId()]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return $paymentId;
    }

    /**
     * Function/Method Name: findPaymentById
     * Purpose:      Fetch one payment.
     * Parameters:   int $paymentId
     * Return Value: ?Payment - the payment, or null when not found
     * Description:  Includes the customer's name for display.
     */
    public function findPaymentById(int $paymentId): ?Payment
    {
        $payments = $this->searchPayments(['paymentId' => $paymentId]);

        return $payments[0] ?? null;
    }

    public function getAllPayments(): array
    {
        return $this->searchPayments([]);
    }

    /**
     * Function/Method Name: searchPayments
     * Purpose:      Find payments that match the search criteria.
     * Parameters:   array $searchCriteria - any of: paymentId, orderId (int),
     *               paymentMethod, paymentStatus (string)
     * Return Value: Payment[] - newest first (empty array when nothing matches)
     * Description:  Builds the WHERE clause only from the criteria that were given.
     */
    public function searchPayments(array $searchCriteria): array
    {
        $conditions = [];
        $parameters = [];
        $columnsByCriterion = [
            'paymentId' => 'pm.payment_id',
            'orderId' => 'pm.order_id',
            'paymentMethod' => 'pm.payment_method',
            'paymentStatus' => 'pm.payment_status',
        ];

        foreach ($columnsByCriterion as $criterionName => $columnName) {
            $criterionValue = $searchCriteria[$criterionName] ?? null;
            if ($criterionValue !== null && $criterionValue !== '') {
                $conditions[] = $columnName . ' = :' . $criterionName;
                $parameters[$criterionName] = $criterionValue;
            }
        }

        $searchSql = self::SELECT_PAYMENTS_SQL;
        if ($conditions !== []) {
            $searchSql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $statement = $this->connection->prepare($searchSql . ' ORDER BY pm.payment_date DESC, pm.payment_id DESC');
        $statement->execute($parameters);

        return array_map(static fn (array $paymentRow) => Payment::fromDatabaseRow($paymentRow), $statement->fetchAll());
    }

    /**
     * Function/Method Name: verifyPayment
     * Purpose:      Confirm a pending (Cash on Delivery) payment was received.
     * Parameters:   int $paymentId
     * Return Value: void
     * Description:  Only a "Pending" payment can be verified.
     */
    public function verifyPayment(int $paymentId): void
    {
        $payment = $this->requirePayment($paymentId);
        if (!$payment->canBeVerified()) {
            throw new BusinessRuleException('Payment #' . $paymentId . ' is "' . $payment->getPaymentStatus() . '" and cannot be verified.');
        }

        $this->saveStatus($paymentId, Payment::STATUS_COMPLETED);
    }

    /**
     * Function/Method Name: refundPayment
     * Purpose:      Refund a completed payment.
     * Parameters:   int $paymentId
     * Return Value: void
     * Description:  Only a "Completed" payment can be refunded, so it can never be refunded twice.
     */
    public function refundPayment(int $paymentId): void
    {
        $payment = $this->requirePayment($paymentId);
        if (!$payment->canBeRefunded()) {
            throw new BusinessRuleException('Payment #' . $paymentId . ' is "' . $payment->getPaymentStatus() . '" and cannot be refunded.');
        }

        $this->saveStatus($paymentId, Payment::STATUS_REFUNDED);
    }

    /**
     * Function/Method Name: getTotalRevenue
     * Purpose:      Money received so far (administrator dashboard).
     * Parameters:   none
     * Return Value: float - sum of all completed payments
     * Description:  Pending, refunded and cancelled payments are not counted.
     */
    public function getTotalRevenue(): float
    {
        $statement = $this->connection->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = :status');
        $statement->execute(['status' => Payment::STATUS_COMPLETED]);

        return round((float) $statement->fetchColumn(), 2);
    }

    private function requirePayment(int $paymentId): Payment
    {
        $payment = $this->findPaymentById($paymentId);
        if ($payment === null) {
            throw new BusinessRuleException('Payment #' . $paymentId . ' was not found.');
        }

        return $payment;
    }

    private function saveStatus(int $paymentId, string $paymentStatus): void
    {
        $statement = $this->connection->prepare('UPDATE payments SET payment_status = :paymentStatus WHERE payment_id = :paymentId');
        $statement->execute(['paymentStatus' => $paymentStatus, 'paymentId' => $paymentId]);
    }
}
