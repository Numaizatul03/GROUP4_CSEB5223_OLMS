<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/cart_service.php';
require_once __DIR__ . '/../services/order_validator.php';

/**
 * Class Name:  CartController
 * Purpose:     The "Manage Shopping Cart" use case: add, view, update, remove and clear.
 * Description: Every action works on the logged-in customer's own cart only;
 *              the cart owner is never taken from the form.
 */
class CartController extends BaseController
{
    private AuthService $authService;
    private CartService $cartService;
    private OrderValidator $orderValidator;

    public function __construct(AuthService $authService, CartService $cartService, OrderValidator $orderValidator)
    {
        $this->authService = $authService;
        $this->cartService = $cartService;
        $this->orderValidator = $orderValidator;
    }

    /**
     * Function/Method Name: viewCart
     * Purpose:      Show the cart with quantities, subtotals and the total.
     * Parameters:   none
     * Return Value: void
     * Description:  Lines with more than the current stock are flagged, and checkout
     *               is disabled until they are fixed.
     */
    public function viewCart(): void
    {
        $shoppingCart = $this->cartService->getCartForUser($this->getCustomerId());

        ViewService::renderView('cart/view', [
            'pageTitle' => 'My Cart',
            'shoppingCart' => $shoppingCart,
            'unavailableItems' => $shoppingCart->findUnavailableItems(),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: addToCart
     * Purpose:      Add a product to the cart (addToCart() in the class diagram).
     * Parameters:   none (reads productId and quantity from $_POST)
     * Return Value: void
     * Description:  Stock and quantity rules are checked by CartService; a refusal
     *               returns the customer to the product page with the reason.
     */
    public function addToCart(): void
    {
        $this->requireValidCsrfToken();
        $productId = $this->readIdFromRequest($_POST, 'productId');
        $quantity = $this->orderValidator->parseQuantity($_POST['quantity'] ?? '');
        $productUrl = buildUrl('shop_product', ['productId' => $productId]);

        if ($productId === null || $quantity === null) {
            $this->redirectWithError($productId === null ? buildUrl('shop') : $productUrl, 'Please enter a whole number for the quantity.');
        }

        try {
            $this->cartService->addItem($this->getCustomerId(), $productId, $quantity);
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError($productUrl, $exception->getMessage());
        }

        $this->redirectWithSuccess(buildUrl('cart'), 'Added ' . $quantity . ' item(s) to your cart.');
    }

    /**
     * Function/Method Name: updateCart
     * Purpose:      Change the quantity of one cart line (updateCart()).
     * Parameters:   none (reads productId and quantity from $_POST; 0 removes the line)
     * Return Value: void
     * Description:  Shows the refusal reason on the cart page when stock is too low.
     */
    public function updateCart(): void
    {
        $this->requireValidCsrfToken();
        $productId = $this->readIdFromRequest($_POST, 'productId');
        $quantity = $this->orderValidator->parseQuantity($_POST['quantity'] ?? '');
        if ($productId === null || $quantity === null) {
            $this->redirectWithError(buildUrl('cart'), 'Please enter a whole number for the quantity.');
        }

        try {
            $this->cartService->updateQuantity($this->getCustomerId(), $productId, $quantity);
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError(buildUrl('cart'), $exception->getMessage());
        }

        $this->redirectWithSuccess(buildUrl('cart'), $quantity === 0 ? 'Item removed from your cart.' : 'Cart updated.');
    }

    public function removeFromCart(): void
    {
        $this->requireValidCsrfToken();
        $productId = $this->readIdFromRequest($_POST, 'productId');
        $isRemoved = $productId !== null && $this->cartService->removeItem($this->getCustomerId(), $productId);

        $isRemoved
            ? $this->redirectWithSuccess(buildUrl('cart'), 'Item removed from your cart.')
            : $this->redirectWithError(buildUrl('cart'), 'That item was not in your cart.');
    }

    public function clearCart(): void
    {
        $this->requireValidCsrfToken();
        $this->cartService->clearCart($this->getCustomerId());
        $this->redirectWithSuccess(buildUrl('cart'), 'Your cart is now empty.');
    }

    private function getCustomerId(): int
    {
        return (int) $this->authService->getCurrentUser()->getUserId();
    }
}
