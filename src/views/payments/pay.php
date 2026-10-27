<?php
/**
 * View:     payments/pay.php
 * Purpose:  Make Payment - choose a payment method for an unpaid order.
 * Data:     Order $order, string[] $paymentMethods, ?string $paymentError, string $csrfToken
 */
$methodNotes = [
    Payment::METHOD_ONLINE_BANKING => 'Pay now from your bank account (simulated - no bank details needed).',
    Payment::METHOD_E_WALLET => 'Pay now with your e-wallet (simulated - no account details needed).',
    Payment::METHOD_CASH_ON_DELIVERY => 'Pay the rider in cash when your order arrives.',
];
?>
<div class="two-column two-column--checkout">
    <section class="card">
        <div class="card__header"><h2>How would you like to pay?</h2></div>
        <?php if ($paymentError !== null): ?>
            <div class="alert alert--error" role="alert"><?= escapeHtml($paymentError) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= escapeHtml(buildUrl('payment_submit')) ?>" novalidate>
            <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
            <input type="hidden" name="orderId" value="<?= $order->getOrderId() ?>">
            <div class="choice-list">
                <?php foreach ($paymentMethods as $methodIndex => $paymentMethod): ?>
                    <label class="choice">
                        <input type="radio" name="paymentMethod" value="<?= escapeHtml($paymentMethod) ?>" <?= $methodIndex === 1 ? 'checked' : '' ?> required>
                        <span><strong><?= escapeHtml($paymentMethod) ?></strong><span class="cell-note"><?= escapeHtml($methodNotes[$paymentMethod]) ?></span></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form__actions">
                <button type="submit" class="button button--primary">Pay <?= escapeHtml(formatPrice($order->getTotalAmount())) ?></button>
                <a class="button button--ghost" href="<?= escapeHtml(buildUrl('order', ['orderId' => $order->getOrderId()])) ?>">Pay later</a>
            </div>
        </form>
    </section>

    <section class="card card--muted">
        <div class="card__header"><h2>Order #<?= $order->getOrderId() ?></h2></div>
        <ul class="summary-list">
            <?php foreach ($order->getOrderItems() as $orderItem): ?>
                <li><span><?= $orderItem->getQuantity() ?> &times; <?= escapeHtml($orderItem->getProductName()) ?></span>
                    <span><?= escapeHtml(formatPrice($orderItem->getSubtotal())) ?></span></li>
            <?php endforeach; ?>
            <li class="summary-list__total"><span>Amount due</span><span><?= escapeHtml(formatPrice($order->getTotalAmount())) ?></span></li>
        </ul>
    </section>
</div>
