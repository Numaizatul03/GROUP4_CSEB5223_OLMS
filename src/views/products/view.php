<?php
/**
 * View:     products/view.php
 * Purpose:  Show the complete record of one product.
 * Data:     Product $product
 */
?>
<section class="card card--narrow">
    <?php ViewService::renderPartial('products/partials/product_details', ['productDetails' => $product->getProductDetails()]); ?>
    <div class="form__actions">
        <a class="button button--primary" href="<?= escapeHtml(buildUrl('edit', ['productId' => $product->getProductId()])) ?>">Edit</a>
        <a class="button button--danger-ghost" href="<?= escapeHtml(buildUrl('delete_confirm', ['productId' => $product->getProductId()])) ?>">Delete</a>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('list')) ?>">Back to list</a>
    </div>
</section>
