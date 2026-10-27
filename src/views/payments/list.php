<?php
/**
 * View:     payments/list.php
 * Purpose:  Administrator payments page: search by order, method and status; verify and refund.
 * Data:     array $searchInput, array $searchErrors, bool $hasSearched, Payment[] $searchResults,
 *           Payment[] $payments, float $totalRevenue, string $csrfToken
 */
?>
<form class="card form search-form" method="get" action="index.php" novalidate>
    <input type="hidden" name="action" value="payments">
    <div class="form__grid form__grid--three">
        <div class="form__row">
            <label for="orderId">Order ID</label>
            <input type="text" id="orderId" name="orderId" inputmode="numeric" value="<?= escapeHtml($searchInput['orderId'] ?? '') ?>" placeholder="e.g. 1">
            <?= fieldErrorHtml($searchErrors, 'orderId') ?>
        </div>
        <div class="form__row">
            <label for="paymentMethod">Method</label>
            <select id="paymentMethod" name="paymentMethod">
                <option value="">Any method</option>
                <?php foreach (Payment::PAYMENT_METHODS as $paymentMethod): ?>
                    <option value="<?= escapeHtml($paymentMethod) ?>" <?= ($searchInput['paymentMethod'] ?? '') === $paymentMethod ? 'selected' : '' ?>><?= escapeHtml($paymentMethod) ?></option>
                <?php endforeach; ?>
            </select>
            <?= fieldErrorHtml($searchErrors, 'paymentMethod') ?>
        </div>
        <div class="form__row">
            <label for="paymentStatus">Status</label>
            <select id="paymentStatus" name="paymentStatus">
                <option value="">Any status</option>
                <?php foreach (Payment::PAYMENT_STATUSES as $paymentStatus): ?>
                    <option value="<?= escapeHtml($paymentStatus) ?>" <?= ($searchInput['paymentStatus'] ?? '') === $paymentStatus ? 'selected' : '' ?>><?= escapeHtml($paymentStatus) ?></option>
                <?php endforeach; ?>
            </select>
            <?= fieldErrorHtml($searchErrors, 'paymentStatus') ?>
        </div>
    </div>
    <div class="form__actions">
        <button type="submit" class="button button--primary">Search</button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('payments')) ?>">Clear</a>
        <span class="push-right revenue-note">Revenue received: <strong><?= escapeHtml(formatPrice($totalRevenue)) ?></strong></span>
    </div>
</form>

<?php if ($hasSearched): ?>
    <section class="card">
        <?php if ($searchResults === []): ?>
            <?php ViewService::renderPartial('shared/partials/not_found_box', ['notFoundMessage' => 'No payment matches your search.']); ?>
        <?php else: ?>
            <div class="card__header"><h2>Search Results <span class="count-pill"><?= count($searchResults) ?> found</span></h2></div>
            <?php ViewService::renderPartial('payments/partials/payment_table', ['payments' => $searchResults, 'csrfToken' => $csrfToken]); ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card card--muted">
    <div class="card__header"><h2>All Payments <span class="count-pill"><?= count($payments) ?></span></h2></div>
    <?php if ($payments === []): ?>
        <p class="empty-state">No payments yet.</p>
    <?php else: ?>
        <?php ViewService::renderPartial('payments/partials/payment_table', ['payments' => $payments, 'csrfToken' => $csrfToken]); ?>
    <?php endif; ?>
</section>
