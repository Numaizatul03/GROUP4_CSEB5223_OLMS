<?php
/**
 * View:     shop/product.php
 * Purpose:  View Product Details with the Add to Cart form.
 * Data:     Product $product, int $maxQuantity, string $csrfToken, ?User $currentUser (layout)
 */
$isInStock = $product->checkStock();
?>
<div class="product-detail">
    <div class="product-detail__visual product-card__swatch--<?= $product->getCategoryId() % 3 ?>" aria-hidden="true">
        <?= escapeHtml(mb_strtoupper(mb_substr($product->getProductName(), 0, 1))) ?>
    </div>
    <section class="card product-detail__info">
        <span class="product-card__category"><?= escapeHtml($product->getCategoryName()) ?> &middot; Code <?= escapeHtml($product->getProductCode()) ?></span>
        <p class="product-detail__price"><?= escapeHtml(formatPrice($product->getPrice())) ?></p>
        <p><?= nl2br(escapeHtml($product->getDescription())) ?></p>
        <p>
            <?php ViewService::renderPartial('products/partials/stock_badge', ['stockStatus' => $product->getStockStatus()]); ?>
            <span class="field-hint"><?= $product->getStockQuantity() ?> unit(s) left</span>
        </p>

        <?php if (!$isInStock): ?>
            <div class="alert alert--warning">This product is out of stock. Please check again later.</div>
        <?php elseif ($currentUser instanceof Customer): ?>
            <form method="post" action="<?= escapeHtml(buildUrl('cart_add')) ?>" class="add-to-cart" novalidate>
                <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                <input type="hidden" name="productId" value="<?= escapeHtml($product->getProductId()) ?>">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $maxQuantity ?>" step="1" required>
                <button type="submit" class="button button--primary">Add to cart</button>
            </form>
        <?php elseif ($currentUser === null): ?>
            <a class="button button--primary" href="<?= escapeHtml(buildUrl('login')) ?>">Log in to buy</a>
        <?php else: ?>
            <p class="field-hint">Administrators cannot place orders. Log in as a customer to buy.</p>
        <?php endif; ?>
        <p><a href="<?= escapeHtml(buildUrl('shop')) ?>">&larr; Back to the shop</a></p>
    </section>
</div>
