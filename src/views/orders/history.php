<?php
/**
 * View:     orders/history.php
 * Purpose:  View Order History - the customer's own orders, newest first.
 * Data:     Order[] $orders, string $selectedStatus
 */
?>
<div class="pill-row pill-row--spaced" aria-label="Filter by status">
    <a class="pill <?= $selectedStatus === '' ? 'is-active' : '' ?>" href="<?= escapeHtml(buildUrl('orders')) ?>">All</a>
    <?php foreach (Order::ORDER_STATUSES as $orderStatus): ?>
        <a class="pill <?= $selectedStatus === $orderStatus ? 'is-active' : '' ?>"
           href="<?= escapeHtml(buildUrl('orders', ['orderStatus' => $orderStatus])) ?>"><?= escapeHtml($orderStatus) ?></a>
    <?php endforeach; ?>
</div>

<section class="card">
    <?php if ($orders === []): ?>
        <?php ViewService::renderPartial('shared/partials/not_found_box', [
            'notFoundMessage' => $selectedStatus === '' ? 'You have not placed any orders yet.' : 'You have no "' . $selectedStatus . '" orders.',
        ]); ?>
        <div class="form__actions form__actions--spaced"><a class="button button--primary" href="<?= escapeHtml(buildUrl('shop')) ?>">Go shopping</a></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr><th>Order</th><th>Date</th><th>Items</th><th class="is-number">Total</th><th>Status</th><th class="is-actions"></th></tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= $order->getOrderId() ?></strong></td>
                        <td><?= escapeHtml(formatDateTime($order->getOrderDate())) ?></td>
                        <td><?= count($order->getOrderItems()) ?> product(s)</td>
                        <td class="is-number"><?= escapeHtml(formatPrice($order->getTotalAmount())) ?></td>
                        <td><?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $order->getOrderStatus()]); ?></td>
                        <td class="is-actions">
                            <?php if ($order->isAwaitingPayment()): ?>
                                <a class="button button--small button--primary" href="<?= escapeHtml(buildUrl('payment', ['orderId' => $order->getOrderId()])) ?>">Pay now</a>
                            <?php endif; ?>
                            <a class="button button--small button--ghost" href="<?= escapeHtml(buildUrl('order', ['orderId' => $order->getOrderId()])) ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
