<?php

declare(strict_types=1);

/**
 * File:    cart_order_payment_test.php
 * Purpose: Check the shopping cart, checkout, order status, payment and inventory rules.
 */

const CUSTOMER_KAVITHA_ID = 4;

function createShopServices(): array
{
    $connection = createTestConnection();
    $cartService = new CartService($connection);

    return [
        'cartService' => $cartService,
        'orderService' => new OrderService($connection, $cartService),
        'paymentService' => new PaymentService($connection),
        'productService' => new ProductService($connection),
    ];
}

function testCartAddUpdateRemoveAndClear(): void
{
    ['cartService' => $cartService] = createShopServices();

    $cartService->addItem(CUSTOMER_KAVITHA_ID, 1, 2);
    $cartService->addItem(CUSTOMER_KAVITHA_ID, 1, 1);
    $cartService->addItem(CUSTOMER_KAVITHA_ID, 7, 5);
    $shoppingCart = $cartService->getCartForUser(CUSTOMER_KAVITHA_ID);

    assertSame(2, count($shoppingCart->getCartItems()), 'Adding the same product twice should keep one line');
    assertSame(8, $shoppingCart->countItems(), '3 eggs + 5 water = 8 units');
    assertSame(31.7, $shoppingCart->calculateTotal(), '3 x 7.90 + 5 x 1.60 = 31.70');

    $cartService->updateQuantity(CUSTOMER_KAVITHA_ID, 7, 2);
    assertSame(26.9, $cartService->getCartForUser(CUSTOMER_KAVITHA_ID)->calculateTotal(), 'Total should follow the new quantity');

    assertTrue($cartService->removeItem(CUSTOMER_KAVITHA_ID, 1), 'Remove should report success');
    $cartService->clearCart(CUSTOMER_KAVITHA_ID);
    assertTrue($cartService->getCartForUser(CUSTOMER_KAVITHA_ID)->isEmpty(), 'Cart should be empty after clearing');
}

function testCartRefusesMoreThanStock(): void
{
    ['cartService' => $cartService] = createShopServices();

    assertThrows(fn () => $cartService->addItem(CUSTOMER_KAVITHA_ID, 9, 1), 'out of stock', 'Out-of-stock product cannot be added');
    assertThrows(fn () => $cartService->addItem(CUSTOMER_KAVITHA_ID, 3, 4), 'Only 3 unit(s)', 'Cannot add more than the 3 tomatoes left');
    assertThrows(fn () => $cartService->addItem(CUSTOMER_KAVITHA_ID, 1, 0), 'between 1 and 20', 'Zero quantity is refused');
    assertThrows(fn () => $cartService->addItem(CUSTOMER_KAVITHA_ID, 999, 1), 'no longer available', 'Unknown product is refused');
}

function testCheckoutCreatesOrderTakesStockAndEmptiesCart(): void
{
    ['cartService' => $cartService, 'orderService' => $orderService, 'productService' => $productService] = createShopServices();
    $cartService->addItem(CUSTOMER_KAVITHA_ID, 3, 3);
    $cartService->addItem(CUSTOMER_KAVITHA_ID, 5, 2);

    $orderId = $orderService->createOrderFromCart(CUSTOMER_KAVITHA_ID, '45, Lorong Cempaka 2, Klang');
    $order = $orderService->findOrderById($orderId);

    assertSame(Order::STATUS_PENDING_PAYMENT, $order->getOrderStatus(), 'New order waits for payment');
    assertSame(24.6, $order->getTotalAmount(), '3 x 4.20 + 2 x 6.00 = 24.60');
    assertSame($order->calculateTotal(), $order->getTotalAmount(), 'Stored total should equal the sum of the lines');
    assertSame(0, $productService->findProductById(3)->getStockQuantity(), 'All 3 tomatoes should be taken from stock');
    assertTrue($cartService->getCartForUser(CUSTOMER_KAVITHA_ID)->isEmpty(), 'Cart should be emptied after checkout');
    assertThrows(fn () => $orderService->createOrderFromCart(CUSTOMER_KAVITHA_ID, 'x'), 'cart is empty', 'Empty cart cannot be checked out');
}

function testCheckoutRollsBackWhenStockRunsOut(): void
{
    ['cartService' => $cartService, 'orderService' => $orderService, 'productService' => $productService] = createShopServices();
    $cartService->addItem(CUSTOMER_KAVITHA_ID, 1, 2);
    $cartService->addItem(CUSTOMER_KAVITHA_ID, 3, 3);
    $productService->adjustStock(3, -2); // someone else bought 2 tomatoes after they were added to the cart

    assertThrows(
        fn () => $orderService->createOrderFromCart(CUSTOMER_KAVITHA_ID, '45, Lorong Cempaka 2, Klang'),
        'Not enough stock for "Cameron Highlands Tomatoes 500g"',
        'Checkout must fail when stock ran out'
    );
    assertSame(40, $productService->findProductById(1)->getStockQuantity(), 'Egg stock must be restored by the rollback');
    assertSame(3, $orderService->countOrders(), 'No half-finished order should be saved');
    assertSame(2, count($cartService->getCartForUser(CUSTOMER_KAVITHA_ID)->getCartItems()), 'Cart should be kept so the customer can fix it');
}

function testPaymentMethodsSetOrderAndPaymentStatus(): void
{
    ['cartService' => $cartService, 'orderService' => $orderService, 'paymentService' => $paymentService] = createShopServices();
    $cartService->addItem(CUSTOMER_KAVITHA_ID, 7, 2);
    $onlineOrderId = $orderService->createOrderFromCart(CUSTOMER_KAVITHA_ID, 'Klang');

    assertThrows(fn () => $paymentService->processPayment($orderService->findOrderById($onlineOrderId), 'Credit Card'), 'listed payment methods', 'Unknown method is refused');

    $paymentService->processPayment($orderService->findOrderById($onlineOrderId), Payment::METHOD_E_WALLET);
    $paidOrder = $orderService->findOrderById($onlineOrderId);
    assertSame(Order::STATUS_PAID, $paidOrder->getOrderStatus(), 'Online payment marks the order Paid');
    assertSame(3.2, $paidOrder->getPayment()->getAmount(), 'Payment amount comes from the order total');
    assertThrows(fn () => $paymentService->processPayment($paidOrder, Payment::METHOD_E_WALLET), 'does not need a payment', 'An order cannot be paid twice');

    $codPaymentId = $paymentService->processPayment($orderService->findOrderById(3), Payment::METHOD_CASH_ON_DELIVERY);
    assertSame(Order::STATUS_PROCESSING, $orderService->findOrderById(3)->getOrderStatus(), 'Cash on Delivery goes to Processing');
    $paymentService->verifyPayment($codPaymentId);
    assertSame(Payment::STATUS_COMPLETED, $paymentService->findPaymentById($codPaymentId)->getPaymentStatus(), 'Verified cash payment is Completed');
    assertThrows(fn () => $paymentService->verifyPayment($codPaymentId), 'cannot be verified', 'A payment cannot be verified twice');
}

function testOrderStatusRulesAndCancellationRestock(): void
{
    ['orderService' => $orderService, 'productService' => $productService, 'paymentService' => $paymentService] = createShopServices();

    assertThrows(fn () => $orderService->updateStatus(1, Order::STATUS_PAID), 'cannot be changed', 'Delivered order cannot go back to Paid');
    assertThrows(fn () => $orderService->cancelOrder(1), 'can no longer be cancelled', 'Delivered order cannot be cancelled');

    $orderService->updateStatus(2, Order::STATUS_SHIPPED);
    $orderService->updateStatus(2, Order::STATUS_DELIVERED);
    assertSame(Payment::STATUS_COMPLETED, $orderService->findOrderById(2)->getPayment()->getPaymentStatus(), 'Delivering a COD order completes its payment');

    $riceStockBefore = $productService->findProductById(4)->getStockQuantity();
    $orderService->updateStatus(3, Order::STATUS_CANCELLED);
    assertSame(Order::STATUS_CANCELLED, $orderService->findOrderById(3)->getOrderStatus(), 'Order 3 should be cancelled');
    assertSame($riceStockBefore + 2, $productService->findProductById(4)->getStockQuantity(), 'Cancelling returns the 2 loaves to stock');

    $paymentService->refundPayment(1);
    assertThrows(fn () => $paymentService->refundPayment(1), 'cannot be refunded', 'A payment cannot be refunded twice');
}

function testDeleteOnlyFinishedOrders(): void
{
    ['orderService' => $orderService] = createShopServices();

    assertThrows(fn () => $orderService->deleteOrder(2), 'Only Delivered or Cancelled', 'An active order must not be deleted');
    assertTrue($orderService->deleteOrder(1), 'A delivered order can be deleted');
    assertSame(null, $orderService->findOrderById(1), 'Deleted order should be gone, with its items and payment');
}

function testInventoryAdjustStock(): void
{
    ['productService' => $productService] = createShopServices();

    assertSame(25, $productService->adjustStock(9, 25), 'Restocking sambal from 0 by 25 gives 25');
    assertThrows(fn () => $productService->adjustStock(3, -10), 'only 3 in stock', 'Stock cannot go below zero');
    assertSame('Cameron Highlands Tomatoes 500g', $productService->getProductsByStockLevel()[0]->getProductName(), 'Lowest stock is listed first');
}

return [
    'testCartAddUpdateRemoveAndClear',
    'testCartRefusesMoreThanStock',
    'testCheckoutCreatesOrderTakesStockAndEmptiesCart',
    'testCheckoutRollsBackWhenStockRunsOut',
    'testPaymentMethodsSetOrderAndPaymentStatus',
    'testOrderStatusRulesAndCancellationRestock',
    'testDeleteOnlyFinishedOrders',
    'testInventoryAdjustStock',
];
