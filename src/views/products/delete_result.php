<?php
/**
 * View:     products/delete_result.php
 * Purpose:  Display all data to validate the deletion (Delete, step 4).
 * Data:     array     $deletedProductDetails
 *           bool      $isDeletionVerified - true when the database no longer has the product
 *           int       $productCountBefore, int $productCountAfter
 *           Product[] $products
 */
?>
<div class="alert <?= $isDeletionVerified ? 'alert--success' : 'alert--error' ?>" role="status">
    <?php if ($isDeletionVerified): ?>
        Product #<?= escapeHtml($deletedProductDetails['productId']) ?>
        "<?= escapeHtml($deletedProductDetails['productName']) ?>" was deleted.
    <?php else: ?>
        Product #<?= escapeHtml($deletedProductDetails['productId']) ?> could not be deleted. Please try again.
    <?php endif; ?>
</div>

<div class="stat-row">
    <div class="stat"><span class="stat__label">Products before</span><span class="stat__value"><?= $productCountBefore ?></span></div>
    <div class="stat"><span class="stat__label">Products after</span><span class="stat__value"><?= $productCountAfter ?></span></div>
    <div class="stat">
        <span class="stat__label">Database check</span>
        <span class="stat__value stat__value--text"><?= $isDeletionVerified ? 'Product ID no longer exists' : 'Product still exists' ?></span>
    </div>
</div>

<section class="card">
    <div class="card__header">
        <h2>Deleted Record</h2>
    </div>
    <?php ViewService::renderPartial('products/partials/product_details', ['productDetails' => $deletedProductDetails]); ?>
</section>

<section class="card card--muted">
    <div class="card__header">
        <h2>All Products <span class="count-pill"><?= count($products) ?> item(s)</span></h2>
    </div>
    <?php ViewService::renderPartial('products/partials/product_table', ['products' => $products]); ?>
</section>
