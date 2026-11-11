<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/request.php';
require_once __DIR__ . '/../services/api_response.php';
require_once __DIR__ . '/../services/input_guard.php';
require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/cart_service.php';
require_once __DIR__ . '/../services/product_service.php';
require_once __DIR__ . '/../services/order_validator.php';

/**
 * Class Name:  ApiCartController
 * Purpose:     The logged-in customer's cart through the API (read it, add to it).
 * Description: Used by the shop's "Add to cart" button so the page does not reload.
 *              The cart owner always comes from the login session, never from the
 *              request, so one customer can never change another customer's cart.
 */
final class ApiCartController
{
    private AuthService $authService;
    private CartService $cartService;
    private ProductService $productService;
    private OrderValidator $orderValidator;

    public function __construct(AuthService $authService, CartService $cartService, ProductService $productService, OrderValidator $orderValidator)
    {
        $this->authService = $authService;
        $this->cartService = $cartService;
        $this->productService = $productService;
        $this->orderValidator = $orderValidator;
    }

    /**
     * Function/Method Name: showCart
     * Purpose:      Return the current customer's cart.
     * Parameters:   Request $request
     * Return Value: ApiResponse - 200 with items, itemCount and total
     * Description:  An empty cart returns items [] and total 0.
     */
    public function showCart(Request $request): ApiResponse
    {
        return ApiResponse::success($this->buildCartData());
    }

    /**
     * Function/Method Name: addItem
     * Purpose:      Add a product to the current customer's cart.
     * Parameters:   Request $request - body (JSON or form): productId, quantity (default 1)
     * Return Value: ApiResponse - 201 with the updated cart, 422 for bad input,
     *               404 for an unknown product, or 409 when stock is too low
     * Description:  CartService applies the same stock and quantity rules as the website.
     */
    public function addItem(Request $request): ApiResponse
    {
        $body = $request->getBody();
        $productId = InputGuard::readId($body, 'productId');
        $quantity = array_key_exists('quantity', $body) ? $this->orderValidator->parseQuantity(InputGuard::readText($body, 'quantity', 10)) : 1;

        $fieldErrors = [];
        if ($productId === null) {
            $fieldErrors['productId'] = 'productId must be a whole number, for example 3.';
        }
        if ($quantity === null) {
            $fieldErrors['quantity'] = 'quantity must be a whole number from 1 to ' . CartService::MAX_QUANTITY_PER_ITEM . '.';
        }
        if ($fieldErrors !== []) {
            return ApiResponse::error(422, 'validation_failed', 'Some values are not valid.', $fieldErrors);
        }

        $product = $this->productService->findProductById($productId);
        if ($product === null) {
            return ApiResponse::error(404, 'not_found', 'Product #' . $productId . ' does not exist or is no longer sold.');
        }

        try {
            $this->cartService->addItem($this->getCustomerId(), $productId, $quantity);
        } catch (BusinessRuleException $exception) {
            return ApiResponse::error(409, 'cannot_add_to_cart', $exception->getMessage());
        }

        return ApiResponse::success(
            $this->buildCartData(),
            ['message' => 'Added ' . $quantity . ' x ' . $product->getProductName() . ' to your cart.'],
            201
        );
    }

    private function buildCartData(): array
    {
        $shoppingCart = $this->cartService->getCartForUser($this->getCustomerId());
        $items = array_map(static fn (CartItem $cartItem) => [
            'productId' => $cartItem->getProductId(),
            'name' => $cartItem->getProductName(),
            'price' => $cartItem->getPrice(),
            'quantity' => $cartItem->getQuantity(),
            'subtotal' => $cartItem->calculateSubtotal(),
            'isAvailable' => $cartItem->isQuantityAvailable(),
        ], $shoppingCart->getCartItems());

        return [
            'items' => $items,
            'itemCount' => $shoppingCart->countItems(),
            'total' => $shoppingCart->calculateTotal(),
            'totalText' => formatPrice($shoppingCart->calculateTotal()),
        ];
    }

    private function getCustomerId(): int
    {
        return (int) $this->authService->getCurrentUser()->getUserId();
    }
}
