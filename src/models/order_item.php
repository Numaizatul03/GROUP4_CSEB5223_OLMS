<?php

declare(strict_types=1);

/**
 * Class Name:  OrderItem
 * Purpose:     One product line inside an order (Assignment Part 1, Section 3.9).
 * Description: The product name and price are copies taken at checkout, so the
 *              order keeps showing what was actually bought even if the product
 *              is later edited or deleted (productId then becomes null).
 */
class OrderItem
{
    private int $orderItemId = 0;
    private int $orderId = 0;
    private ?int $productId = null;
    private string $productName = '';
    private int $quantity = 0;
    private float $price = 0.0;
    private float $subtotal = 0.0;

    /**
     * Function/Method Name: fromDatabaseRow
     * Purpose:      Build an OrderItem from an order_items row.
     * Parameters:   array $orderItemRow - associative row
     * Return Value: OrderItem
     * Description:  product_id may be NULL when the product was deleted later.
     */
    public static function fromDatabaseRow(array $orderItemRow): OrderItem
    {
        $orderItem = new OrderItem();
        $orderItem->orderItemId = (int) $orderItemRow['order_item_id'];
        $orderItem->orderId = (int) $orderItemRow['order_id'];
        $orderItem->productId = $orderItemRow['product_id'] === null ? null : (int) $orderItemRow['product_id'];
        $orderItem->productName = (string) $orderItemRow['product_name'];
        $orderItem->quantity = (int) $orderItemRow['quantity'];
        $orderItem->price = (float) $orderItemRow['price'];
        $orderItem->subtotal = (float) $orderItemRow['subtotal'];

        return $orderItem;
    }

    /**
     * Function/Method Name: calculateSubtotal
     * Purpose:      Price of this line.
     * Parameters:   none
     * Return Value: float - price x quantity, rounded to 2 decimal places
     * Description:  Used at checkout to fill the subtotal column.
     */
    public function calculateSubtotal(): float
    {
        return round($this->price * $this->quantity, 2);
    }

    public function getOrderItemId(): int
    {
        return $this->orderItemId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }
}
