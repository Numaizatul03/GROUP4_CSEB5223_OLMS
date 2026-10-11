<?php
/**
 * Partial:  products/partials/product_details.php
 * Purpose:  Show every attribute of one product as a label/value list.
 * Data:     array $productDetails - output of Product::getProductDetails()
 */
?>
<dl class="detail-list">
    <dt>Product ID</dt>
    <dd><?= escapeHtml($productDetails['productId']) ?></dd>
    <dt>Product Name</dt>
    <dd><?= escapeHtml($productDetails['productName']) ?></dd>
    <dt>Category</dt>
    <dd><?= escapeHtml($productDetails['categoryName']) ?></dd>
    <dt>Description</dt>
    <dd><?= nl2br(escapeHtml($productDetails['description'])) ?></dd>
    <dt>Price</dt>
    <dd><?= escapeHtml(formatPrice($productDetails['price'])) ?></dd>
    <dt>Stock Quantity</dt>
    <dd><?= escapeHtml($productDetails['stockQuantity']) ?>
        <?php ViewService::renderPartial('products/partials/stock_badge', ['stockStatus' => $productDetails['stockStatus']]); ?></dd>
    <dt>Created</dt>
    <dd><?= escapeHtml($productDetails['createdAt']) ?></dd>
    <dt>Last Updated</dt>
    <dd><?= escapeHtml($productDetails['updatedAt']) ?></dd>
</dl>
