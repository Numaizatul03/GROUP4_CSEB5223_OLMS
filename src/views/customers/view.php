<?php
/**
 * View:     customers/view.php
 * Purpose:  One customer's details and order history (administrator).
 * Data:     Customer $customer, array $detailRows, Order[] $orders
 */
?>
<div class="two-column">
    <section class="card">
        <div class="card__header"><h2>Customer details</h2></div>
        <?php ViewService::renderPartial('shared/partials/detail_list', ['detailRows' => $detailRows]); ?>
        <div class="form__actions">
            <a class="button button--edit" href="<?= escapeHtml(buildUrl('customer_edit', ['customerId' => $customer->getUserId()])) ?>">Edit</a>
            <a class="button button--danger-ghost" href="<?= escapeHtml(buildUrl('customer_delete_confirm', ['customerId' => $customer->getUserId()])) ?>">Delete</a>
            <a class="button button--ghost" href="<?= escapeHtml(buildUrl('customers')) ?>">All customers</a>
        </div>
    </section>
    <section class="card">
        <div class="card__header"><h2>Orders <span class="count-pill"><?= count($orders) ?></span></h2></div>
        <?php if ($orders === []): ?>
            <p class="empty-state">This customer has not ordered yet.</p>
        <?php else: ?>
            <ul class="summary-list">
                <?php foreach ($orders as $order): ?>
                    <li><a href="<?= escapeHtml(buildUrl('admin_order', ['orderId' => $order->getOrderId()])) ?>">#<?= $order->getOrderId() ?> &middot; <?= escapeHtml(formatDateTime($order->getOrderDate())) ?></a>
                        <span><?= escapeHtml(formatPrice($order->getTotalAmount())) ?> <?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $order->getOrderStatus()]); ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
