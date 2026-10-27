<?php
/**
 * View:     dashboard/index.php
 * Purpose:  Administrator overview of every module.
 * Data:     int $productCount, $lowStockCount, $categoryCount, $customerCount, $orderCount,
 *           array $ordersByStatus, float $totalRevenue, Product[] $lowStockProducts, Order[] $recentOrders
 */
$statTiles = [
    ['Products', (string) $productCount, buildUrl('list'), $lowStockCount . ' low or out of stock'],
    ['Categories', (string) $categoryCount, buildUrl('categories'), 'Product groups'],
    ['Customers', (string) $customerCount, buildUrl('customers'), 'Registered accounts'],
    ['Orders', (string) $orderCount, buildUrl('admin_orders'), ($ordersByStatus['Processing'] + $ordersByStatus['Paid']) . ' to prepare'],
    ['Revenue', formatPrice($totalRevenue), buildUrl('payments'), 'Completed payments'],
];
?>
<div class="dashboard-tiles">
    <?php foreach ($statTiles as [$tileLabel, $tileValue, $tileUrl, $tileNote]): ?>
        <a class="dashboard-tile" href="<?= escapeHtml($tileUrl) ?>">
            <span class="stat__label"><?= escapeHtml($tileLabel) ?></span>
            <span class="dashboard-tile__value"><?= escapeHtml($tileValue) ?></span>
            <span class="cell-note"><?= escapeHtml($tileNote) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<section class="card">
    <div class="card__header"><h2>Orders by status</h2></div>
    <div class="status-strip">
        <?php foreach ($ordersByStatus as $orderStatus => $statusCount): ?>
            <a class="status-strip__item" href="<?= escapeHtml(buildUrl('admin_orders', ['orderStatus' => $orderStatus])) ?>">
                <span class="status-strip__count"><?= $statusCount ?></span>
                <?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $orderStatus]); ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<div class="two-column">
    <section class="card">
        <div class="card__header">
            <h2>Needs restocking</h2>
            <a class="button button--small button--ghost" href="<?= escapeHtml(buildUrl('inventory')) ?>">Manage inventory</a>
        </div>
        <ul class="summary-list">
            <?php foreach ($lowStockProducts as $product): ?>
                <li><span><?= escapeHtml($product->getProductName()) ?></span>
                    <span><?= $product->getStockQuantity() ?> <?php ViewService::renderPartial('products/partials/stock_badge', ['stockStatus' => $product->getStockStatus()]); ?></span></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="card">
        <div class="card__header">
            <h2>Recent orders</h2>
            <a class="button button--small button--ghost" href="<?= escapeHtml(buildUrl('admin_orders')) ?>">All orders</a>
        </div>
        <ul class="summary-list">
            <?php foreach ($recentOrders as $order): ?>
                <li><a href="<?= escapeHtml(buildUrl('admin_order', ['orderId' => $order->getOrderId()])) ?>">#<?= $order->getOrderId() ?> &middot; <?= escapeHtml($order->getCustomerName()) ?></a>
                    <span><?= escapeHtml(formatPrice($order->getTotalAmount())) ?> <?php ViewService::renderPartial('shared/partials/status_badge', ['status' => $order->getOrderStatus()]); ?></span></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
