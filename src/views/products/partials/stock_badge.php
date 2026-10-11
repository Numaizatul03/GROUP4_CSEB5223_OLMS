<?php
/**
 * Partial:  products/partials/stock_badge.php
 * Purpose:  Coloured label for a stock status.
 * Data:     string $stockStatus - "In stock", "Low stock" or "Out of stock"
 */
$badgeStyles = [
    'In stock' => 'badge--ok',
    'Low stock' => 'badge--warn',
    'Out of stock' => 'badge--bad',
];
?>
<span class="badge <?= $badgeStyles[$stockStatus] ?? '' ?>"><?= escapeHtml($stockStatus) ?></span>
