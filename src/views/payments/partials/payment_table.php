<?php
/**
 * Partial:  payments/partials/payment_table.php
 * Purpose:  Table of payments with Verify / Refund buttons where allowed.
 * Data:     Payment[] $payments, string $csrfToken
 */
$paymentActions = ['payment_verify' => 'Verify', 'payment_refund' => 'Refund'];
?>
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr><th class="is-number">Payment</th><th>Order</th><th>Customer</th><th>Method</th><th class="is-number">Amount</th><th>Date</th><th>Status</th><th class="is-actions">Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <?php $allowedActions = ['payment_verify' => $payment->canBeVerified(), 'payment_refund' => $payment->canBeRefunded()]; ?>
            <tr>
                <td class="is-number">#<?= $payment->getPaymentId() ?></td>
                <td><a href="<?= escapeHtml(buildUrl('admin_order', ['orderId' => $payment->getOrderId()])) ?>">Order #<?= $payment->getOrderId() ?></a></td>
                <td><?= escapeHtml($payment->getCustomerName()) ?></td>
                <td><?= escapeHtml($payment->getPaymentMethod()) ?></td>
                <td class="is-number"><?= escapeHtml(formatPrice($payment->getAmount())) ?></td>
                <td><?= escapeHtml(formatDateTime($payment->getPaymentDate())) ?></td>
                <td><?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $payment->getPaymentStatus()]); ?></td>
                <td class="is-actions">
                    <?php foreach ($paymentActions as $paymentAction => $buttonLabel): ?>
                        <?php if ($allowedActions[$paymentAction]): ?>
                            <form method="post" action="<?= escapeHtml(buildUrl($paymentAction)) ?>" class="inline-form" data-confirm="<?= $buttonLabel ?> payment #<?= $payment->getPaymentId() ?>?">
                                <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                                <input type="hidden" name="paymentId" value="<?= $payment->getPaymentId() ?>">
                                <button type="submit" class="button button--small <?= $paymentAction === 'payment_refund' ? 'button--danger-ghost' : 'button--ghost' ?>"><?= $buttonLabel ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
