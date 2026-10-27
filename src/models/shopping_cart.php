<?php

declare(strict_types=1);

require_once __DIR__ . '/cart_item.php';

/**
 * Class Name:  ShoppingCart
 * Purpose:     A customer's active cart (Assignment Part 1, Section 3.6).
 * Description: Holds the CartItem lines and calculates the cart total.
 *              addItem(), removeItem(), updateQuantity() and clearCart() from
 *              the class diagram change the database, so CartService does them
 *              and then reloads this object.
 */
class ShoppingCart
{
    private int $cartId;
    private int $userId;
    /** @var CartItem[] */
    private array $cartItems;

    public function __construct(int $cartId, int $userId, array $cartItems)
    {
        $this->cartId = $cartId;
        $this->userId = $userId;
        $this->cartItems = $cartItems;
    }

    /**
     * Function/Method Name: calculateTotal
     * Purpose:      Total price of everything in the cart.
     * Parameters:   none
     * Return Value: float - sum of every line's subtotal
     * Description:  Matches calculateTotal() in the class diagram (totalAmount attribute).
     */
    public function calculateTotal(): float
    {
        $totalAmount = 0.0;
        foreach ($this->cartItems as $cartItem) {
            $totalAmount += $cartItem->calculateSubtotal();
        }

        return round($totalAmount, 2);
    }

    /**
     * Function/Method Name: countItems
     * Purpose:      Number of units in the cart (shown on the Cart link).
     * Parameters:   none
     * Return Value: int - sum of all quantities
     * Description:  Two trays of eggs count as 2.
     */
    public function countItems(): int
    {
        return array_sum(array_map(static fn (CartItem $cartItem) => $cartItem->getQuantity(), $this->cartItems));
    }

    /**
     * Function/Method Name: findUnavailableItems
     * Purpose:      List lines whose quantity is more than the stock left.
     * Parameters:   none
     * Return Value: CartItem[] - lines that block checkout (empty when all fine)
     * Description:  Checkout is refused while this list is not empty.
     */
    public function findUnavailableItems(): array
    {
        return array_values(array_filter($this->cartItems, static fn (CartItem $cartItem) => !$cartItem->isQuantityAvailable()));
    }

    public function isEmpty(): bool
    {
        return $this->cartItems === [];
    }

    public function getCartId(): int
    {
        return $this->cartId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /** @return CartItem[] */
    public function getCartItems(): array
    {
        return $this->cartItems;
    }
}
