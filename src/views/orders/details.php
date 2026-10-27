<?php
/**
 * View:     orders/details.php
 * Purpose:  One of the customer's orders: status progress, items, payment, actions.
 * Data:     Order $order, string $csrfToken
 */
$progressSteps = [Order::STATUS_PENDING_PAYMENT, Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED];
$currentStepIndex = array_search($order->getOrderStatus(), $progressSteps, true);
$isCancelled = $order->getOrderStatus() === Order::STATUS_CANCELLED;
?>
<section class="card">
    <div class="card__header">
        <h2>Placed <?= escapeHtml(formatDateTime($order->getOrderDate())) ?></h2>
        <?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $order->getOrderStatus()]); ?>
    </div>

    <?php if ($isCancelled): ?>
        <div class="alert alert--error">This order was cancelled. Items were returned to stock<?= $order->getPayment()?->getPaymentStatus() === Payment::STATUS_REFUNDED ? ' and your payment was refunded' : '' ?>.</div>
    <?php else: ?>
        <ol class="progress-steps" aria-label="Order progress">
            <?php foreach ($progressSteps as $stepIndex => $stepStatus): ?>
                <li class="<?= $currentStepIndex !== false && $stepIndex <= $currentStepIndex ? 'is-done' : '' ?>"><?= escapeHtml($stepStatus) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <?php ViewService::renderPartial('orders/partials/order_items', ['order' => $order]); ?>

    <div class="form__actions form__actions--spaced">
        <?php if ($order->isAwaitingPayment()): ?>
            <a class="button button--primary" href="<?= escapeHtml(buildUrl('payment', ['orderId' => $order->getOrderId()])) ?>">Pay now</a>
        <?php endif; ?>
        <?php if ($order->canBeCancelled()): ?>
            <form method="post" action="<?= escapeHtml(buildUrl('order_cancel')) ?>" class="inline-form" data-confirm="Cancel order #<?= $order->getOrderId() ?>? Items go back to stock and any payment is refunded.">
                <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                <input type="hidden" name="orderId" value="<?= $order->getOrderId() ?>">
                <button type="submit" class="button button--danger-ghost">Cancel order</button>
            </form>
        <?php endif; ?>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('orders')) ?>">All my orders</a>
    </div>
</section>
