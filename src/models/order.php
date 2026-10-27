<?php

declare(strict_types=1);

require_once __DIR__ . '/order_item.php';
require_once __DIR__ . '/payment.php';

/**
 * Class Name:  Order
 * Purpose:     An order placed by a customer (Assignment Part 1, Section 3.8).
 * Description: Holds the order header, its OrderItem lines and its Payment
 *              (zero or one). The status rules live here so every page and
 *              service applies the same rules.
 */
class Order
{
    public const STATUS_PENDING_PAYMENT = 'Pending Payment';
    public const STATUS_PAID = 'Paid';
    public const STATUS_PROCESSING = 'Processing';
    public const STATUS_SHIPPED = 'Shipped';
    public const STATUS_DELIVERED = 'Delivered';
    public const STATUS_CANCELLED = 'Cancelled';
    public const ORDER_STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    // Which status an order may move to next (used by the administrator's status form)
    private const NEXT_STATUSES = [
        self::STATUS_PENDING_PAYMENT => [self::STATUS_CANCELLED],
        self::STATUS_PAID => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
    ];

    private int $orderId = 0;
    private int $userId = 0;
    private string $customerName = '';
    private string $customerEmail = '';
    private string $orderDate = '';
    private float $totalAmount = 0.0;
    private string $orderStatus = self::STATUS_PENDING_PAYMENT;
    private string $shippingAddress = '';
    private string $updatedAt = '';
    /** @var OrderItem[] */
    private array $orderItems = [];
    private ?Payment $payment = null;

    /**
     * Function/Method Name: fromDatabaseRow
     * Purpose:      Build an Order header from an orders row joined with users.
     * Parameters:   array $orderRow - associative row
     * Return Value: Order - items and payment are attached later by OrderService
     * Description:  Casts each column to the attribute's data type.
     */
    public static function fromDatabaseRow(array $orderRow): Order
    {
        $order = new Order();
        $order->orderId = (int) $orderRow['order_id'];
        $order->userId = (int) $orderRow['user_id'];
        $order->customerName = (string) ($orderRow['customer_name'] ?? '');
        $order->customerEmail = (string) ($orderRow['customer_email'] ?? '');
        $order->orderDate = (string) $orderRow['order_date'];
        $order->totalAmount = (float) $orderRow['total_amount'];
        $order->orderStatus = (string) $orderRow['order_status'];
        $order->shippingAddress = (string) $orderRow['shipping_address'];
        $order->updatedAt = (string) $orderRow['updated_at'];

        return $order;
    }

    /**
     * Function/Method Name: calculateTotal
     * Purpose:      Add up the order lines.
     * Parameters:   none
     * Return Value: float - sum of every OrderItem subtotal
     * Description:  Matches calculateTotal() in the class diagram.
     */
    public function calculateTotal(): float
    {
        $totalAmount = 0.0;
        foreach ($this->orderItems as $orderItem) {
            $totalAmount += $orderItem->getSubtotal();
        }

        return round($totalAmount, 2);
    }

    /**
     * Function/Method Name: getAllowedNextStatuses
     * Purpose:      List the statuses this order may move to.
     * Parameters:   none
     * Return Value: string[] - e.g. ["Shipped", "Cancelled"] for a Processing order
     * Description:  Stops impossible changes such as Delivered back to Paid.
     */
    public function getAllowedNextStatuses(): array
    {
        return self::NEXT_STATUSES[$this->orderStatus] ?? [];
    }

    /**
     * Function/Method Name: canChangeStatusTo
     * Purpose:      Check one status change against the rules.
     * Parameters:   string $newStatus - status the administrator chose
     * Return Value: bool - true when the change is allowed
     * Description:  Used by updateStatus() in OrderService.
     */
    public function canChangeStatusTo(string $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedNextStatuses(), true);
    }

    /**
     * Function/Method Name: canBeCancelled
     * Purpose:      Can this order still be cancelled?
     * Parameters:   none
     * Return Value: bool - true before the order is shipped
     * Description:  Matches cancelOrder() in the class diagram.
     */
    public function canBeCancelled(): bool
    {
        return $this->canChangeStatusTo(self::STATUS_CANCELLED);
    }

    public function isAwaitingPayment(): bool
    {
        return $this->orderStatus === self::STATUS_PENDING_PAYMENT && $this->payment === null;
    }

    /**
     * Function/Method Name: getOrderDetails
     * Purpose:      Return the order header in one array for display.
     * Parameters:   none
     * Return Value: array - attribute name => value
     * Description:  Used for the before/after table when the status changes.
     */
    public function getOrderDetails(): array
    {
        return [
            'orderId' => $this->orderId,
            'customerName' => $this->customerName,
            'orderDate' => $this->orderDate,
            'totalAmount' => $this->totalAmount,
            'orderStatus' => $this->orderStatus,
            'paymentStatus' => $this->payment?->getPaymentStatus() ?? 'Not paid',
            'shippingAddress' => $this->shippingAddress,
            'updatedAt' => $this->updatedAt,
        ];
    }

    public function attachItems(array $orderItems): void
    {
        $this->orderItems = $orderItems;
    }

    public function attachPayment(?Payment $payment): void
    {
        $this->payment = $payment;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getOrderDate(): string
    {
        return $this->orderDate;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /** @return OrderItem[] */
    public function getOrderItems(): array
    {
        return $this->orderItems;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }
}
