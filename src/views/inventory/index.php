<?php
/**
 * View:     inventory/index.php
 * Purpose:  Manage Inventory - stock levels (lowest first) with a restock / write-off form per product.
 * Data:     Product[] $products, ?int $highlightProductId, string $csrfToken
 */
?>
<p class="page__lead">Enter a positive number to restock (e.g. 20) or a negative number to write off damaged stock (e.g. -2).</p>
<section class="card">
    <div class="card__header"><h2>Stock levels <span class="count-pill"><?= count($products) ?> product(s)</span></h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr><th class="is-number">ID</th><th>Code</th><th>Product</th><th>Category</th><th class="is-number">In stock</th><th>Status</th><th>Adjust stock</th></tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr id="product-<?= $product->getProductId() ?>" class="<?= $product->getProductId() === $highlightProductId ? 'is-highlighted' : '' ?>">
                    <td class="is-number"><?= $product->getProductId() ?></td>
                    <td><span class="code-tag"><?= escapeHtml($product->getProductCode()) ?></span></td>
                    <td><strong><?= escapeHtml($product->getProductName()) ?></strong></td>
                    <td><?= escapeHtml($product->getCategoryName()) ?></td>
                    <td class="is-number"><?= $product->getStockQuantity() ?></td>
                    <td><?php ViewService::renderPartial('products/partials/stock_badge', ['stockStatus' => $product->getStockStatus()]); ?></td>
                    <td>
                        <form method="post" action="<?= escapeHtml(buildUrl('inventory_adjust')) ?>" class="quantity-form">
                            <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                            <input type="hidden" name="productId" value="<?= $product->getProductId() ?>">
                            <input type="number" name="quantityChange" step="1" placeholder="+/-" required aria-label="Stock change for <?= escapeHtml($product->getProductName()) ?>">
                            <button type="submit" class="button button--small button--ghost">Apply</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
