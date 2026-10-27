<?php
/**
 * View:     customers/list.php
 * Purpose:  Manage Customers - search, results / "Not found", then all customers.
 * Data:     array $searchInput, array $searchErrors, bool $hasSearched, Customer[] $searchResults,
 *           Customer[] $customers, array $orderCounts, ?int $highlightCustomerId
 */
?>
<form class="card form search-form" method="get" action="index.php" novalidate>
    <input type="hidden" name="action" value="customers">
    <div class="form__grid form__grid--search-short">
        <div class="form__row">
            <label for="customerId">Customer ID</label>
            <input type="text" id="customerId" name="customerId" inputmode="numeric" value="<?= escapeHtml($searchInput['customerId'] ?? '') ?>" placeholder="e.g. 3">
            <?= fieldErrorHtml($searchErrors, 'customerId') ?>
        </div>
        <div class="form__row">
            <label for="keyword">Name, email or phone contains</label>
            <input type="search" id="keyword" name="keyword" value="<?= escapeHtml($searchInput['keyword'] ?? '') ?>" placeholder="e.g. siti or 012-">
        </div>
    </div>
    <div class="form__actions">
        <button type="submit" class="button button--primary">Search</button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('customers')) ?>">Clear</a>
        <a class="button button--ghost push-right" href="<?= escapeHtml(buildUrl('customer_create')) ?>">+ Add Customer</a>
    </div>
</form>

<?php if ($hasSearched): ?>
    <section class="card">
        <?php if ($searchResults === []): ?>
            <?php ViewService::renderPartial('shared/partials/not_found_box', ['notFoundMessage' => 'No customer matches your search. Check the ID, name, email or phone number.']); ?>
        <?php else: ?>
            <div class="card__header"><h2>Search Results <span class="count-pill"><?= count($searchResults) ?> found</span></h2></div>
            <?php ViewService::renderPartial('customers/partials/customer_table', ['customers' => $searchResults, 'orderCounts' => $orderCounts]); ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card card--muted">
    <div class="card__header"><h2>All Customers <span class="count-pill"><?= count($customers) ?> account(s)</span></h2></div>
    <?php ViewService::renderPartial('customers/partials/customer_table', [
        'customers' => $customers,
        'orderCounts' => $orderCounts,
        'highlightCustomerId' => $highlightCustomerId,
    ]); ?>
</section>
