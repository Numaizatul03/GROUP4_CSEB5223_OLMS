<?php
/**
 * Partial:  shared/partials/status_badge.php
 * Purpose:  Coloured pill for an order or payment status.
 * Data:     string $status
 */
$badgeStyles = [
    'Pending Payment' => 'badge--warn',
    'Pending' => 'badge--warn',
    'Paid' => 'badge--ok',
    'Completed' => 'badge--ok',
    'Processing' => 'badge--sky',
    'Shipped' => 'badge--sky',
    'Delivered' => 'badge--done',
    'Cancelled' => 'badge--bad',
    'Refunded' => 'badge--bad',
];
?>
<span class="badge <?= $badgeStyles[$status] ?? '' ?>"><?= escapeHtml($status) ?></span>
