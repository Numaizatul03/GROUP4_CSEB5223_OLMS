<?php
/**
 * View:     products/list.php
 * Purpose:  Data display page - every product in one organised table.
 * Data:     Product[] $products, ?string $successMessage, ?int $highlightProductId
 */
?>
<?php if ($successMessage): ?>
    <div class="alert alert--success" role="status" data-auto-dismiss><?= escapeHtml($successMessage) ?></div>
<?php endif; ?>

<section class="card">
    <div class="card__header">
        <h2>Product List <span class="count-pill"><?= count($products) ?> item(s)</span></h2>
        <a class="button button--primary" href="<?= escapeHtml(buildUrl('create')) ?>">+ Add Product</a>
    </div>
    <?php ViewService::renderPartial('products/partials/product_table', [
        'products' => $products,
        'highlightProductId' => $highlightProductId,
    ]); ?>
</section>
