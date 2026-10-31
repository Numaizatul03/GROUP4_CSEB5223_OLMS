<?php
/**
 * View:     products/delete_confirm.php
 * Purpose:  Show the complete product and ask the user to confirm deletion (Delete, step 2).
 * Data:     Product $product, string $csrfToken
 */
?>
<div class="alert alert--warning" role="alert">
    <strong>Are you sure you want to delete this product?</strong>
    It will be moved to Delete History, where it can be restored later.
</div>

<section class="card card--narrow card--danger">
    <?php ViewService::renderPartial('products/partials/product_details', ['productDetails' => $product->getProductDetails()]); ?>

    <form method="post" action="<?= escapeHtml(buildUrl('destroy')) ?>" class="form__actions">
        <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
        <input type="hidden" name="productId" value="<?= escapeHtml($product->getProductId()) ?>">
        <button type="submit" class="button button--danger">Yes, delete this product</button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('delete_search')) ?>">No, keep it</a>
    </form>
</section>
