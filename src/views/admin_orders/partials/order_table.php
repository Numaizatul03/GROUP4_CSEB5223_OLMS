<?php
/**
 * Partial:  admin_orders/partials/order_table.php
 * Purpose:  Table of orders with View/Delete actions (administrator).
 * Data:     Order[] $orders, ?int $highlightOrderId
 */
$highlightOrderId ??= null;
?>
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr><th class="is-number">Order</th><th>Customer</th><th>Date</th><th class="is-number">Total</th><th>Status</th><th>Payment</th><th class="is-actions">Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr class="<?= $order->getOrderId() === $highlightOrderId ? 'is-highlighted' : '' ?>">
                <td class="is-number"><strong>#<?= $order->getOrderId() ?></strong></td>
                <td><?= escapeHtml($order->getCustomerName()) ?><span class="cell-note"><?= escapeHtml($order->getCustomerEmail()) ?></span></td>
                <td><?= escapeHtml(formatDateTime($order->getOrderDate())) ?></td>
                <td class="is-number"><?= escapeHtml(formatPrice($order->getTotalAmount())) ?></td>
                <td><?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $order->getOrderStatus()]); ?></td>
                <td>
                    <?php if ($order->getPayment() === null): ?>
                        <span class="cell-note">Not paid</span>
                    <?php else: ?>
                        <?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $order->getPayment()->getPaymentStatus()]); ?>
                        <span class="cell-note"><?= escapeHtml($order->getPayment()->getPaymentMethod()) ?></span>
                    <?php endif; ?>
                </td>
                <td class="is-actions">
                    <a class="button button--small button--ghost" href="<?= escapeHtml(buildUrl('admin_order', ['orderId' => $order->getOrderId()])) ?>">View / Update</a>
                    <a class="button button--small button--danger-ghost" href="<?= escapeHtml(buildUrl('admin_order_delete_confirm', ['orderId' => $order->getOrderId()])) ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
