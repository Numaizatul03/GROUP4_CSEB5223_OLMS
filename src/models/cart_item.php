<?php

declare(strict_types=1);

/**
 * Class Name:  CartItem
 * Purpose:     One product line inside a shopping cart (Assignment Part 1, Section 3.7).
 * Description: Keeps the current product name, price and stock next to the
 *              quantity so the cart page and checkout can check availability.
 */
class CartItem
{
    private int $cartItemId = 0;
    private int $cartId = 0;
    private int $productId = 0;
    private string $productName = '';
    private float $price = 0.0;
    private int $quantity = 0;
    private int $stockQuantity = 0;

    /**
     * Function/Method Name: fromDatabaseRow
     * Purpose:      Build a CartItem from a cart_items row joined with products.
     * Parameters:   array $cartItemRow - row with cart and product columns
     * Return Value: CartItem
     * Description:  Casts each column to the attribute's data type.
     */
    public static function fromDatabaseRow(array $cartItemRow): CartItem
    {
        $cartItem = new CartItem();
        $cartItem->cartItemId = (int) $cartItemRow['cart_item_id'];
        $cartItem->cartId = (int) $cartItemRow['cart_id'];
        $cartItem->productId = (int) $cartItemRow['product_id'];
        $cartItem->productName = (string) $cartItemRow['product_name'];
        $cartItem->price = (float) $cartItemRow['price'];
        $cartItem->stockQuantity = (int) $cartItemRow['stock_quantity'];
        $cartItem->updateQuantity((int) $cartItemRow['quantity']);

        return $cartItem;
    }

    /**
     * Function/Method Name: calculateSubtotal
     * Purpose:      Price of this line.
     * Parameters:   none
     * Return Value: float - price x quantity, rounded to 2 decimal places
     * Description:  Rounded so totals never show floating-point noise (e.g. 4.8000001).
     */
    public function calculateSubtotal(): float
    {
        return round($this->price * $this->quantity, 2);
    }

    /**
     * Function/Method Name: updateQuantity
     * Purpose:      Change how many of the product are in the cart.
     * Parameters:   int $quantity - new quantity (validated by CartService)
     * Return Value: void
     * Description:  Matches updateQuantity() in the class diagram.
     */
    public function updateQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * Function/Method Name: isQuantityAvailable
     * Purpose:      Check that the shop still has enough stock for this line.
     * Parameters:   none
     * Return Value: bool - true when stock covers the quantity in the cart
     * Description:  Stock may drop after the item was added, so the cart re-checks.
     */
    public function isQuantityAvailable(): bool
    {
        return $this->stockQuantity >= $this->quantity;
    }

    public function getCartItemId(): int
    {
        return $this->cartItemId;
    }

    public function getCartId(): int
    {
        return $this->cartId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }
}
