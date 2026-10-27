<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/shopping_cart.php';
require_once __DIR__ . '/business_rule_exception.php';

/**
 * Class Name:  CartService
 * Purpose:     Manage each customer's shopping cart (add, read, update, remove, clear).
 * Description: Implements addItem(), removeItem(), updateQuantity() and
 *              clearCart() from the ShoppingCart class in the class diagram.
 *              Every change re-checks the product's current stock.
 */
class CartService
{
    public const MAX_QUANTITY_PER_ITEM = 20;

    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Function/Method Name: getCartForUser
     * Purpose:      Load a customer's cart with all its items.
     * Parameters:   int $userId - the customer
     * Return Value: ShoppingCart - an empty cart is created on first use
     * Description:  Each item is joined with the product so the current price and stock are shown.
     */
    public function getCartForUser(int $userId): ShoppingCart
    {
        $cartId = $this->findOrCreateCartId($userId);
        $statement = $this->connection->prepare(
            'SELECT ci.cart_item_id, ci.cart_id, ci.product_id, ci.quantity,
                    p.product_name, p.price, p.stock_quantity
             FROM cart_items ci
             INNER JOIN products p ON p.product_id = ci.product_id
             WHERE ci.cart_id = :cartId
             ORDER BY ci.cart_item_id'
        );
        $statement->execute(['cartId' => $cartId]);
        $cartItems = array_map(static fn (array $cartItemRow) => CartItem::fromDatabaseRow($cartItemRow), $statement->fetchAll());

        return new ShoppingCart($cartId, $userId, $cartItems);
    }

    /**
     * Function/Method Name: addItem
     * Purpose:      Put a product in the cart, or add to the quantity already there.
     * Parameters:   int $userId, int $productId, int $quantity - units to add (1 to 20)
     * Return Value: void
     * Description:  Throws BusinessRuleException when the product is missing, out of
     *               stock, or the total in the cart would be more than the stock.
     */
    public function addItem(int $userId, int $productId, int $quantity): void
    {
        $this->requireValidQuantity($quantity);
        $cartId = $this->findOrCreateCartId($userId);
        $currentQuantity = $this->findQuantityInCart($cartId, $productId);
        $newQuantity = $currentQuantity + $quantity;

        $this->requireStockFor($productId, $newQuantity);
        $this->requireValidQuantity($newQuantity);

        if ($currentQuantity === 0) {
            $statement = $this->connection->prepare(
                'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cartId, :productId, :quantity)'
            );
        } else {
            $statement = $this->connection->prepare(
                'UPDATE cart_items SET quantity = :quantity WHERE cart_id = :cartId AND product_id = :productId'
            );
        }
        $statement->execute(['cartId' => $cartId, 'productId' => $productId, 'quantity' => $newQuantity]);
        $this->touchCart($cartId);
    }

    /**
     * Function/Method Name: updateQuantity
     * Purpose:      Set the quantity of a product already in the cart.
     * Parameters:   int $userId, int $productId, int $quantity - new quantity; 0 removes the item
     * Return Value: void
     * Description:  Throws BusinessRuleException when the product is not in the cart or stock is too low.
     */
    public function updateQuantity(int $userId, int $productId, int $quantity): void
    {
        if ($quantity === 0) {
            $this->removeItem($userId, $productId);
            return;
        }

        $this->requireValidQuantity($quantity);
        $cartId = $this->findOrCreateCartId($userId);
        if ($this->findQuantityInCart($cartId, $productId) === 0) {
            throw new BusinessRuleException('That product is not in your cart any more.');
        }

        $this->requireStockFor($productId, $quantity);
        $statement = $this->connection->prepare(
            'UPDATE cart_items SET quantity = :quantity WHERE cart_id = :cartId AND product_id = :productId'
        );
        $statement->execute(['cartId' => $cartId, 'productId' => $productId, 'quantity' => $quantity]);
        $this->touchCart($cartId);
    }

    /**
     * Function/Method Name: removeItem
     * Purpose:      Take one product out of the cart.
     * Parameters:   int $userId, int $productId
     * Return Value: bool - true when the item was in the cart and was removed
     * Description:  Removing an item that is not there is harmless (returns false).
     */
    public function removeItem(int $userId, int $productId): bool
    {
        $cartId = $this->findOrCreateCartId($userId);
        $statement = $this->connection->prepare('DELETE FROM cart_items WHERE cart_id = :cartId AND product_id = :productId');
        $statement->execute(['cartId' => $cartId, 'productId' => $productId]);
        $this->touchCart($cartId);

        return $statement->rowCount() > 0;
    }

    /**
     * Function/Method Name: clearCart
     * Purpose:      Remove every item from the customer's cart.
     * Parameters:   int $userId
     * Return Value: void
     * Description:  Called by the "Clear cart" button and after a successful checkout.
     */
    public function clearCart(int $userId): void
    {
        $cartId = $this->findOrCreateCartId($userId);
        $statement = $this->connection->prepare('DELETE FROM cart_items WHERE cart_id = :cartId');
        $statement->execute(['cartId' => $cartId]);
        $this->touchCart($cartId);
    }

    public function countItemsForUser(int $userId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COALESCE(SUM(ci.quantity), 0)
             FROM cart_items ci INNER JOIN shopping_carts sc ON sc.cart_id = ci.cart_id
             WHERE sc.user_id = :userId'
        );
        $statement->execute(['userId' => $userId]);

        return (int) $statement->fetchColumn();
    }

    private function findOrCreateCartId(int $userId): int
    {
        $statement = $this->connection->prepare('SELECT cart_id FROM shopping_carts WHERE user_id = :userId');
        $statement->execute(['userId' => $userId]);
        $cartId = $statement->fetchColumn();
        if ($cartId !== false) {
            return (int) $cartId;
        }

        $currentTime = date('Y-m-d H:i:s');
        $statement = $this->connection->prepare(
            'INSERT INTO shopping_carts (user_id, created_at, updated_at) VALUES (:userId, :createdAt, :updatedAt)'
        );
        $statement->execute(['userId' => $userId, 'createdAt' => $currentTime, 'updatedAt' => $currentTime]);

        return (int) $this->connection->lastInsertId();
    }

    private function findQuantityInCart(int $cartId, int $productId): int
    {
        $statement = $this->connection->prepare(
            'SELECT quantity FROM cart_items WHERE cart_id = :cartId AND product_id = :productId'
        );
        $statement->execute(['cartId' => $cartId, 'productId' => $productId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Function/Method Name: requireStockFor
     * Purpose:      Stop the cart holding more than the shop has.
     * Parameters:   int $productId, int $quantity - total quantity wanted in the cart
     * Return Value: void
     * Description:  Throws BusinessRuleException with a message that names the product
     *               and the quantity still available.
     */
    private function requireStockFor(int $productId, int $quantity): void
    {
        $statement = $this->connection->prepare('SELECT product_name, stock_quantity FROM products WHERE product_id = :productId');
        $statement->execute(['productId' => $productId]);
        $productRow = $statement->fetch();

        if ($productRow === false) {
            throw new BusinessRuleException('Sorry, that product is no longer available.');
        }

        $stockQuantity = (int) $productRow['stock_quantity'];
        if ($stockQuantity === 0) {
            throw new BusinessRuleException('Sorry, "' . $productRow['product_name'] . '" is out of stock.');
        }

        if ($quantity > $stockQuantity) {
            throw new BusinessRuleException(
                'Only ' . $stockQuantity . ' unit(s) of "' . $productRow['product_name'] . '" are available.'
            );
        }
    }

    private function requireValidQuantity(int $quantity): void
    {
        if ($quantity < 1 || $quantity > self::MAX_QUANTITY_PER_ITEM) {
            throw new BusinessRuleException('Quantity must be between 1 and ' . self::MAX_QUANTITY_PER_ITEM . ' for each product.');
        }
    }

    private function touchCart(int $cartId): void
    {
        $statement = $this->connection->prepare('UPDATE shopping_carts SET updated_at = :updatedAt WHERE cart_id = :cartId');
        $statement->execute(['updatedAt' => date('Y-m-d H:i:s'), 'cartId' => $cartId]);
    }
}
