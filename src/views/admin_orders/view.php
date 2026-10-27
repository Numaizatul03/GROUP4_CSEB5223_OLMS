<?php
/**
 * View:     admin_orders/view.php
 * Purpose:  One order for the administrator: items, payment actions and the status update form.
 * Data:     Order $order, string $csrfToken
 */
$payment = $order->getPayment();
$nextStatuses = $order->getAllowedNextStatuses();
?>
<div class="two-column two-column--checkout">
    <section class="card">
        <div class="card__header">
            <h2><?= escapeHtml($order->getCustomerName()) ?> &middot; <?= escapeHtml(formatDateTime($order->getOrderDate())) ?></h2>
            <?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $order->getOrderStatus()]); ?>
        </div>
        <?php ViewService::renderPartial('orders/partials/order_items', ['order' => $order]); ?>
    </section>

    <div>
        <section class="card">
            <div class="card__header"><h2>Update status</h2></div>
            <?php if ($nextStatuses === []): ?>
                <p class="empty-state">This order is <?= escapeHtml($order->getOrderStatus()) ?>. Its status can no longer change.</p>
            <?php else: ?>
                <form method="post" action="<?= escapeHtml(buildUrl('admin_order_status')) ?>" novalidate>
                    <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                    <input type="hidden" name="orderId" value="<?= $order->getOrderId() ?>">
                    <div class="form__row">
                        <label for="orderStatus">Move from "<?= escapeHtml($order->getOrderStatus()) ?>" to</label>
                        <select id="orderStatus" name="orderStatus" required>
                            <?php foreach ($nextStatuses as $nextStatus): ?>
                                <option value="<?= escapeHtml($nextStatus) ?>"><?= escapeHtml($nextStatus) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-hint">Cancelling returns the items to stock and refunds a completed payment.</p>
                    </div>
                    <button type="submit" class="button button--primary">Save status</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__header"><h2>Payment</h2></div>
            <?php if ($payment === null): ?>
                <p class="empty-state">The customer has not paid yet.</p>
            <?php else: ?>
                <p><?= escapeHtml($payment->getPaymentMethod()) ?> &middot; <?= escapeHtml(formatPrice($payment->getAmount())) ?>
                    <?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $payment->getPaymentStatus()]); ?></p>
                <?php foreach (['payment_verify' => [$payment->canBeVerified(), 'Mark cash received'], 'payment_refund' => [$payment->canBeRefunded(), 'Refund payment']] as $paymentAction => [$isAllowed, $buttonLabel]): ?>
                    <?php if ($isAllowed): ?>
                        <form method="post" action="<?= escapeHtml(buildUrl($paymentAction)) ?>" class="inline-form" data-confirm="<?= escapeHtml($buttonLabel) ?> for payment #<?= $payment->getPaymentId() ?>?">
                            <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                            <input type="hidden" name="paymentId" value="<?= $payment->getPaymentId() ?>">
                            <input type="hidden" name="orderId" value="<?= $order->getOrderId() ?>">
                            <input type="hidden" name="returnTo" value="order">
                            <button type="submit" class="button button--small <?= $paymentAction === 'payment_refund' ? 'button--danger-ghost' : 'button--ghost' ?>"><?= escapeHtml($buttonLabel) ?></button>
                        </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <div class="form__actions">
            <a class="button button--ghost" href="<?= escapeHtml(buildUrl('admin_orders')) ?>">All orders</a>
            <a class="button button--danger-ghost" href="<?= escapeHtml(buildUrl('admin_order_delete_confirm', ['orderId' => $order->getOrderId()])) ?>">Delete order</a>
        </div>
    </div>
</div>
