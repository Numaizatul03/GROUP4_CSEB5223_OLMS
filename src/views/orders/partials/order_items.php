<?php
/**
 * Partial:  orders/partials/order_items.php
 * Purpose:  An order's item lines and total, plus its payment - shared by the
 *           customer and administrator order pages.
 * Data:     Order $order
 */
$payment = $order->getPayment();
?>
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr><th>Product</th><th class="is-number">Price</th><th class="is-number">Qty</th><th class="is-number">Subtotal</th></tr>
        </thead>
        <tbody>
        <?php foreach ($order->getOrderItems() as $orderItem): ?>
            <tr>
                <td><?= escapeHtml($orderItem->getProductName()) ?>
                    <?php if ($orderItem->getProductId() === null): ?><span class="cell-note">No longer sold</span><?php endif; ?></td>
                <td class="is-number"><?= escapeHtml(formatPrice($orderItem->getPrice())) ?></td>
                <td class="is-number"><?= $orderItem->getQuantity() ?></td>
                <td class="is-number"><?= escapeHtml(formatPrice($orderItem->getSubtotal())) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr class="total-row"><td colspan="3">Total</td><td class="is-number"><?= escapeHtml(formatPrice($order->getTotalAmount())) ?></td></tr>
        </tfoot>
    </table>
</div>

<dl class="detail-list detail-list--compact">
    <dt>Deliver to</dt>
    <dd><?= nl2br(escapeHtml($order->getShippingAddress())) ?></dd>
    <dt>Payment</dt>
    <dd>
        <?php if ($payment === null): ?>
            Not paid yet
        <?php else: ?>
            <?= escapeHtml($payment->getPaymentMethod()) ?> &middot; <?= escapeHtml(formatPrice($payment->getAmount())) ?> &middot;
            <?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $payment->getPaymentStatus()]); ?>
            <span class="cell-note">Payment #<?= $payment->getPaymentId() ?>, <?= escapeHtml(formatDateTime($payment->getPaymentDate())) ?></span>
        <?php endif; ?>
    </dd>
</dl>
