<?php

declare(strict_types=1);

/**
 * Class Name:  Payment
 * Purpose:     The payment for an order (Assignment Part 1, Section 3.10).
 * Description: Payments are simulated for this project: the customer picks a
 *              method and no card or bank details are collected or stored.
 *              Online methods complete immediately; Cash on Delivery stays
 *              "Pending" until the administrator verifies the cash was collected.
 *              A pending payment becomes "Cancelled" if its order is cancelled.
 */
class Payment
{
    public const METHOD_CASH_ON_DELIVERY = 'Cash on Delivery';
    public const METHOD_ONLINE_BANKING = 'Online Banking';
    public const METHOD_E_WALLET = 'E-Wallet';
    public const PAYMENT_METHODS = [self::METHOD_CASH_ON_DELIVERY, self::METHOD_ONLINE_BANKING, self::METHOD_E_WALLET];

    public const STATUS_PENDING = 'Pending';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_REFUNDED = 'Refunded';
    public const STATUS_CANCELLED = 'Cancelled';
    public const PAYMENT_STATUSES = [self::STATUS_PENDING, self::STATUS_COMPLETED, self::STATUS_REFUNDED, self::STATUS_CANCELLED];

    private int $paymentId = 0;
    private int $orderId = 0;
    private string $paymentMethod = '';
    private string $paymentStatus = '';
    private float $amount = 0.0;
    private string $paymentDate = '';
    private string $customerName = '';

    /**
     * Function/Method Name: fromDatabaseRow
     * Purpose:      Build a Payment from a payments row.
     * Parameters:   array $paymentRow - associative row (customer_name is optional)
     * Return Value: Payment
     * Description:  Casts each column to the attribute's data type.
     */
    public static function fromDatabaseRow(array $paymentRow): Payment
    {
        $payment = new Payment();
        $payment->paymentId = (int) $paymentRow['payment_id'];
        $payment->orderId = (int) $paymentRow['order_id'];
        $payment->paymentMethod = (string) $paymentRow['payment_method'];
        $payment->paymentStatus = (string) $paymentRow['payment_status'];
        $payment->amount = (float) $paymentRow['amount'];
        $payment->paymentDate = (string) $paymentRow['payment_date'];
        $payment->customerName = (string) ($paymentRow['customer_name'] ?? '');

        return $payment;
    }

    /**
     * Function/Method Name: getStatusForMethod
     * Purpose:      Decide the starting status of a new payment.
     * Parameters:   string $paymentMethod - one of PAYMENT_METHODS
     * Return Value: string - STATUS_PENDING for Cash on Delivery, otherwise STATUS_COMPLETED
     * Description:  Cash is only collected on delivery, so it starts as pending.
     */
    public static function getStatusForMethod(string $paymentMethod): string
    {
        return $paymentMethod === self::METHOD_CASH_ON_DELIVERY ? self::STATUS_PENDING : self::STATUS_COMPLETED;
    }

    /**
     * Function/Method Name: canBeVerified
     * Purpose:      Can the administrator confirm this payment?
     * Parameters:   none
     * Return Value: bool - true only for a pending payment
     * Description:  Matches verifyPayment() in the class diagram.
     */
    public function canBeVerified(): bool
    {
        return $this->paymentStatus === self::STATUS_PENDING;
    }

    /**
     * Function/Method Name: canBeRefunded
     * Purpose:      Can this payment be refunded?
     * Parameters:   none
     * Return Value: bool - true only for a completed payment
     * Description:  Matches refundPayment() in the class diagram; prevents double refunds.
     */
    public function canBeRefunded(): bool
    {
        return $this->paymentStatus === self::STATUS_COMPLETED;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getPaymentDate(): string
    {
        return $this->paymentDate;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }
}
