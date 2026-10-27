<?php
/**
 * View:     admin_orders/list.php
 * Purpose:  Manage Orders - search by ID, customer, status and date; results / "Not found"; all orders.
 * Data:     array $searchInput, array $searchErrors, bool $hasSearched, Order[] $searchResults, Order[] $orders
 */
?>
<form class="card form search-form" method="get" action="index.php" novalidate>
    <input type="hidden" name="action" value="admin_orders">
    <div class="form__grid form__grid--orders">
        <div class="form__row">
            <label for="orderId">Order ID</label>
            <input type="text" id="orderId" name="orderId" inputmode="numeric" value="<?= escapeHtml($searchInput['orderId'] ?? '') ?>" placeholder="e.g. 2">
            <?= fieldErrorHtml($searchErrors, 'orderId') ?>
        </div>
        <div class="form__row">
            <label for="keyword">Customer name or email</label>
            <input type="search" id="keyword" name="keyword" value="<?= escapeHtml($searchInput['keyword'] ?? '') ?>" placeholder="e.g. daniel">
        </div>
        <div class="form__row">
            <label for="orderStatus">Status</label>
            <select id="orderStatus" name="orderStatus">
                <option value="">Any status</option>
                <?php foreach (Order::ORDER_STATUSES as $orderStatus): ?>
                    <option value="<?= escapeHtml($orderStatus) ?>" <?= ($searchInput['orderStatus'] ?? '') === $orderStatus ? 'selected' : '' ?>><?= escapeHtml($orderStatus) ?></option>
                <?php endforeach; ?>
            </select>
            <?= fieldErrorHtml($searchErrors, 'orderStatus') ?>
        </div>
        <div class="form__row">
            <label for="dateFrom">From</label>
            <input type="date" id="dateFrom" name="dateFrom" value="<?= escapeHtml($searchInput['dateFrom'] ?? '') ?>">
            <?= fieldErrorHtml($searchErrors, 'dateFrom') ?>
        </div>
        <div class="form__row">
            <label for="dateTo">To</label>
            <input type="date" id="dateTo" name="dateTo" value="<?= escapeHtml($searchInput['dateTo'] ?? '') ?>">
            <?= fieldErrorHtml($searchErrors, 'dateTo') ?>
        </div>
    </div>
    <div class="form__actions">
        <button type="submit" class="button button--primary">Search</button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('admin_orders')) ?>">Clear</a>
    </div>
</form>

<?php if ($hasSearched): ?>
    <section class="card">
        <?php if ($searchResults === []): ?>
            <?php ViewService::renderPartial('shared/partials/not_found_box', ['notFoundMessage' => 'No order matches your search. Try fewer criteria or a wider date range.']); ?>
        <?php else: ?>
            <div class="card__header"><h2>Search Results <span class="count-pill"><?= count($searchResults) ?> found</span></h2></div>
            <?php ViewService::renderPartial('admin_orders/partials/order_table', ['orders' => $searchResults]); ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card card--muted">
    <div class="card__header"><h2>All Orders <span class="count-pill"><?= count($orders) ?> order(s)</span></h2></div>
    <?php ViewService::renderPartial('admin_orders/partials/order_table', ['orders' => $orders]); ?>
</section>
