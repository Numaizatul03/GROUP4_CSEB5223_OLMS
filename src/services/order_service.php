<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/order.php';
require_once __DIR__ . '/cart_service.php';
require_once __DIR__ . '/business_rule_exception.php';

/**
 * Class Name:  OrderService
 * Purpose:     Create orders from the cart and read, search, update, cancel and delete orders.
 * Description: Implements createOrder(), updateStatus() and cancelOrder() from
 *              the Order class in the class diagram. Checkout and cancellation
 *              run inside database transactions: either every step succeeds
 *              (order, items, stock, cart) or nothing is changed.
 */
class OrderService
{
    private const SELECT_ORDERS_SQL = 'SELECT o.order_id, o.user_id, o.order_date, o.total_amount, o.order_status,
                o.shipping_address, o.updated_at, u.name AS customer_name, u.email AS customer_email
            FROM orders o
            INNER JOIN users u ON u.user_id = o.user_id';

    private PDO $connection;
    private CartService $cartService;

    public function __construct(PDO $connection, CartService $cartService)
    {
        $this->connection = $connection;
        $this->cartService = $cartService;
    }

    /**
     * Function/Method Name: createOrderFromCart
     * Purpose:      Checkout - turn the customer's cart into an order.
     * Parameters:   int $userId - the customer
     *               string $shippingAddress - delivery address (validated)
     * Return Value: int - ID of the new order (status "Pending Payment")
     * Description:  In one transaction: copy each cart line into order_items with
     *               the current price, take the quantity out of stock, then empty
     *               the cart. The stock UPDATE only succeeds while enough stock is
     *               left, so two customers cannot buy the last unit at the same time.
     */
    public function createOrderFromCart(int $userId, string $shippingAddress): int
    {
        $shoppingCart = $this->cartService->getCartForUser($userId);
        if ($shoppingCart->isEmpty()) {
            throw new BusinessRuleException('Your cart is empty. Add some products before checking out.');
        }

        $this->connection->beginTransaction();
        try {
            $orderId = $this->insertOrder($userId, $shoppingCart->calculateTotal(), $shippingAddress);
            foreach ($shoppingCart->getCartItems() as $cartItem) {
                $this->takeStock($cartItem);
                $this->insertOrderItem($orderId, $cartItem);
            }
            $this->cartService->clearCart($userId);
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return $orderId;
    }

    /**
     * Function/Method Name: findOrderById
     * Purpose:      Fetch one order with its items and payment.
     * Parameters:   int $orderId
     * Return Value: ?Order - the order, or null when it does not exist
     * Description:  Used by the order details pages for customers and administrators.
     */
    public function findOrderById(int $orderId): ?Order
    {
        $orders = $this->searchOrders(['orderId' => $orderId]);

        return $orders[0] ?? null;
    }

    /**
     * Function/Method Name: getOrdersForUser
     * Purpose:      Order history for one customer (viewOrders() in the class diagram).
     * Parameters:   int $userId
     * Return Value: Order[] - newest first
     * Description:  Customers only ever see their own orders.
     */
    public function getOrdersForUser(int $userId): array
    {
        return $this->searchOrders(['userId' => $userId]);
    }

    public function getAllOrders(): array
    {
        return $this->searchOrders([]);
    }

    /**
     * Function/Method Name: searchOrders
     * Purpose:      Find orders that match the search criteria.
     * Parameters:   array $searchCriteria - any of: orderId (int), userId (int), keyword
     *               (customer name/email), orderStatus (string), dateFrom / dateTo (Y-m-d)
     * Return Value: Order[] - matching orders, newest first, each with items and payment
     * Description:  Builds the WHERE clause only from the criteria that were given.
     */
    public function searchOrders(array $searchCriteria): array
    {
        $conditions = [];
        $parameters = [];

        if (($searchCriteria['orderId'] ?? null) !== null) {
            $conditions[] = 'o.order_id = :orderId';
            $parameters['orderId'] = $searchCriteria['orderId'];
        }

        if (($searchCriteria['userId'] ?? null) !== null) {
            $conditions[] = 'o.user_id = :userId';
            $parameters['userId'] = $searchCriteria['userId'];
        }

        if (($searchCriteria['keyword'] ?? '') !== '') {
            $conditions[] = '(u.name LIKE :keywordName OR u.email LIKE :keywordEmail)';
            $parameters['keywordName'] = '%' . $searchCriteria['keyword'] . '%';
            $parameters['keywordEmail'] = '%' . $searchCriteria['keyword'] . '%';
        }

        if (($searchCriteria['orderStatus'] ?? '') !== '') {
            $conditions[] = 'o.order_status = :orderStatus';
            $parameters['orderStatus'] = $searchCriteria['orderStatus'];
        }

        if (($searchCriteria['dateFrom'] ?? '') !== '') {
            $conditions[] = 'o.order_date >= :dateFrom';
            $parameters['dateFrom'] = $searchCriteria['dateFrom'] . ' 00:00:00';
        }

        if (($searchCriteria['dateTo'] ?? '') !== '') {
            $conditions[] = 'o.order_date <= :dateTo';
            $parameters['dateTo'] = $searchCriteria['dateTo'] . ' 23:59:59';
        }

        $searchSql = self::SELECT_ORDERS_SQL;
        if ($conditions !== []) {
            $searchSql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $statement = $this->connection->prepare($searchSql . ' ORDER BY o.order_date DESC, o.order_id DESC');
        $statement->execute($parameters);
        $orders = array_map(static fn (array $orderRow) => Order::fromDatabaseRow($orderRow), $statement->fetchAll());

        return $this->attachItemsAndPayments($orders);
    }

    /**
     * Function/Method Name: updateStatus
     * Purpose:      Move an order to its next status (administrator).
     * Parameters:   int $orderId, string $newStatus - must be allowed by Order::getAllowedNextStatuses()
     * Return Value: void
     * Description:  Choosing "Cancelled" goes through cancelOrder() so stock is returned.
     *               Marking a Cash on Delivery order "Delivered" also completes its payment.
     */
    public function updateStatus(int $orderId, string $newStatus): void
    {
        $order = $this->requireOrder($orderId);
        if (!$order->canChangeStatusTo($newStatus)) {
            throw new BusinessRuleException(
                'An order that is "' . $order->getOrderStatus() . '" cannot be changed to "' . $newStatus . '".'
            );
        }

        if ($newStatus === Order::STATUS_CANCELLED) {
            $this->cancelOrder($orderId);
            return;
        }

        $this->connection->beginTransaction();
        try {
            $this->saveStatus($orderId, $newStatus);
            $payment = $order->getPayment();
            if ($newStatus === Order::STATUS_DELIVERED && $payment !== null && $payment->canBeVerified()) {
                $this->savePaymentStatus($orderId, Payment::STATUS_COMPLETED);
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /**
     * Function/Method Name: cancelOrder
     * Purpose:      Cancel an order that has not been shipped.
     * Parameters:   int $orderId
     * Return Value: void
     * Description:  In one transaction: put every item back into stock, mark the
     *               order "Cancelled", and refund a completed payment (a pending
     *               Cash on Delivery payment is simply cancelled).
     */
    public function cancelOrder(int $orderId): void
    {
        $order = $this->requireOrder($orderId);
        if (!$order->canBeCancelled()) {
            throw new BusinessRuleException('Order #' . $orderId . ' is "' . $order->getOrderStatus() . '" and can no longer be cancelled.');
        }

        $this->connection->beginTransaction();
        try {
            foreach ($order->getOrderItems() as $orderItem) {
                $this->returnStock($orderItem);
            }
            $this->saveStatus($orderId, Order::STATUS_CANCELLED);

            $payment = $order->getPayment();
            if ($payment !== null) {
                $this->savePaymentStatus($orderId, $payment->canBeRefunded() ? Payment::STATUS_REFUNDED : Payment::STATUS_CANCELLED);
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /**
     * Function/Method Name: deleteOrder
     * Purpose:      Permanently remove a finished order (administrator).
     * Parameters:   int $orderId
     * Return Value: bool - true when the order was deleted
     * Description:  Only Delivered or Cancelled orders may be deleted, so stock and
     *               payments for active orders are never lost. Items and payment are
     *               deleted with it (ON DELETE CASCADE).
     */
    public function deleteOrder(int $orderId): bool
    {
        $order = $this->requireOrder($orderId);
        if (!in_array($order->getOrderStatus(), [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true)) {
            throw new BusinessRuleException(
                'Only Delivered or Cancelled orders can be deleted. Order #' . $orderId . ' is "' . $order->getOrderStatus() . '".'
            );
        }

        $statement = $this->connection->prepare('DELETE FROM orders WHERE order_id = :orderId');
        $statement->execute(['orderId' => $orderId]);

        return $statement->rowCount() > 0;
    }

    /**
     * Function/Method Name: countOrdersByStatus
     * Purpose:      Number of orders in each status (administrator dashboard).
     * Parameters:   none
     * Return Value: array - status => count, including statuses with 0 orders
     * Description:  Every status in Order::ORDER_STATUSES is always present.
     */
    public function countOrdersByStatus(): array
    {
        $orderCounts = array_fill_keys(Order::ORDER_STATUSES, 0);
        $statement = $this->connection->query('SELECT order_status, COUNT(*) AS order_count FROM orders GROUP BY order_status');
        foreach ($statement->fetchAll() as $countRow) {
            $orderCounts[$countRow['order_status']] = (int) $countRow['order_count'];
        }

        return $orderCounts;
    }

    public function countOrders(): int
    {
        return (int) $this->connection->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    private function requireOrder(int $orderId): Order
    {
        $order = $this->findOrderById($orderId);
        if ($order === null) {
            throw new BusinessRuleException('Order #' . $orderId . ' was not found.');
        }

        return $order;
    }

    private function insertOrder(int $userId, float $totalAmount, string $shippingAddress): int
    {
        $currentTime = date('Y-m-d H:i:s');
        $statement = $this->connection->prepare(
            'INSERT INTO orders (user_id, order_date, total_amount, order_status, shipping_address, updated_at)
             VALUES (:userId, :orderDate, :totalAmount, :orderStatus, :shippingAddress, :updatedAt)'
        );
        $statement->execute([
            'userId' => $userId,
            'orderDate' => $currentTime,
            'totalAmount' => $totalAmount,
            'orderStatus' => Order::STATUS_PENDING_PAYMENT,
            'shippingAddress' => $shippingAddress,
            'updatedAt' => $currentTime,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function insertOrderItem(int $orderId, CartItem $cartItem): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal)
             VALUES (:orderId, :productId, :productName, :quantity, :price, :subtotal)'
        );
        $statement->execute([
            'orderId' => $orderId,
            'productId' => $cartItem->getProductId(),
            'productName' => $cartItem->getProductName(),
            'quantity' => $cartItem->getQuantity(),
            'price' => $cartItem->getPrice(),
            'subtotal' => $cartItem->calculateSubtotal(),
        ]);
    }

    /**
     * Function/Method Name: takeStock
     * Purpose:      Reduce stock for one checkout line.
     * Parameters:   CartItem $cartItem
     * Return Value: void
     * Description:  The WHERE clause only matches while enough stock is left; if
     *               no row changes, someone else bought it first and the whole
     *               checkout is rolled back with a clear message.
     */
    private function takeStock(CartItem $cartItem): void
    {
        $statement = $this->connection->prepare(
            'UPDATE products SET stock_quantity = stock_quantity - :quantity, updated_at = :updatedAt
             WHERE product_id = :productId AND stock_quantity >= :requiredQuantity'
        );
        $statement->execute([
            'quantity' => $cartItem->getQuantity(),
            'requiredQuantity' => $cartItem->getQuantity(),
            'updatedAt' => date('Y-m-d H:i:s'),
            'productId' => $cartItem->getProductId(),
        ]);

        if ($statement->rowCount() === 0) {
            throw new BusinessRuleException(
                'Not enough stock for "' . $cartItem->getProductName() . '". Please update the quantity in your cart.'
            );
        }
    }

    private function returnStock(OrderItem $orderItem): void
    {
        if ($orderItem->getProductId() === null) {
            return; // the product was deleted, so there is nothing to restock
        }

        $statement = $this->connection->prepare(
            'UPDATE products SET stock_quantity = stock_quantity + :quantity, updated_at = :updatedAt WHERE product_id = :productId'
        );
        $statement->execute([
            'quantity' => $orderItem->getQuantity(),
            'updatedAt' => date('Y-m-d H:i:s'),
            'productId' => $orderItem->getProductId(),
        ]);
    }

    private function saveStatus(int $orderId, string $orderStatus): void
    {
        $statement = $this->connection->prepare(
            'UPDATE orders SET order_status = :orderStatus, updated_at = :updatedAt WHERE order_id = :orderId'
        );
        $statement->execute(['orderStatus' => $orderStatus, 'updatedAt' => date('Y-m-d H:i:s'), 'orderId' => $orderId]);
    }

    private function savePaymentStatus(int $orderId, string $paymentStatus): void
    {
        $statement = $this->connection->prepare('UPDATE payments SET payment_status = :paymentStatus WHERE order_id = :orderId');
        $statement->execute(['paymentStatus' => $paymentStatus, 'orderId' => $orderId]);
    }

    /**
     * Function/Method Name: attachItemsAndPayments
     * Purpose:      Load the items and payment for a list of orders.
     * Parameters:   Order[] $orders
     * Return Value: Order[] - the same orders, now with items and payment attached
     * Description:  Uses two queries for the whole list instead of two per order.
     */
    private function attachItemsAndPayments(array $orders): array
    {
        if ($orders === []) {
            return [];
        }

        $orderIds = array_map(static fn (Order $order) => $order->getOrderId(), $orders);
        $placeholders = implode(', ', array_fill(0, count($orderIds), '?'));

        $itemStatement = $this->connection->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY order_item_id");
        $itemStatement->execute($orderIds);
        $itemsByOrder = [];
        foreach ($itemStatement->fetchAll() as $orderItemRow) {
            $itemsByOrder[(int) $orderItemRow['order_id']][] = OrderItem::fromDatabaseRow($orderItemRow);
        }

        $paymentStatement = $this->connection->prepare("SELECT * FROM payments WHERE order_id IN ($placeholders)");
        $paymentStatement->execute($orderIds);
        $paymentsByOrder = [];
        foreach ($paymentStatement->fetchAll() as $paymentRow) {
            $paymentsByOrder[(int) $paymentRow['order_id']] = Payment::fromDatabaseRow($paymentRow);
        }

        foreach ($orders as $order) {
            $order->attachItems($itemsByOrder[$order->getOrderId()] ?? []);
            $order->attachPayment($paymentsByOrder[$order->getOrderId()] ?? null);
        }

        return $orders;
    }
}
