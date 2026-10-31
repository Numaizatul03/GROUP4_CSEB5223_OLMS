<?php
/**
 * View:     products/history.php
 * Purpose:  Delete History - deleted products that can be restored or deleted permanently.
 * Data:     Product[] $deletedProducts, string $csrfToken
 */
?>
<p class="page__lead">Deleted products are kept here. Restoring a product brings it back with its original ID, stock and details.</p>

<section class="card">
    <div class="card__header">
        <h2>Deleted Products <span class="count-pill"><?= count($deletedProducts) ?> item(s)</span></h2>
    </div>
    <?php if ($deletedProducts === []): ?>
        <p class="empty-state">Delete History is empty. Deleted products will appear here.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th class="is-number">ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="is-number">Price</th>
                    <th class="is-number">Stock</th>
                    <th>Deleted</th>
                    <th class="is-actions">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($deletedProducts as $product): ?>
                    <tr>
                        <td class="is-number"><?= escapeHtml($product->getProductId()) ?></td>
                        <td><strong><?= escapeHtml($product->getProductName()) ?></strong>
                            <span class="cell-note"><?= escapeHtml($product->getDescription()) ?></span></td>
                        <td><?= escapeHtml($product->getCategoryName()) ?></td>
                        <td class="is-number"><?= escapeHtml(formatPrice($product->getPrice())) ?></td>
                        <td class="is-number"><?= escapeHtml($product->getStockQuantity()) ?></td>
                        <td><?= escapeHtml(formatDateTime($product->getDeletedAt())) ?></td>
                        <td class="is-actions">
                            <form method="post" action="<?= escapeHtml(buildUrl('product_restore')) ?>" class="inline-form">
                                <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                                <input type="hidden" name="productId" value="<?= escapeHtml($product->getProductId()) ?>">
                                <button type="submit" class="button button--small button--restore">Restore</button>
                            </form>
                            <form method="post" action="<?= escapeHtml(buildUrl('product_purge')) ?>" class="inline-form"
                                  data-confirm="Delete &quot;<?= escapeHtml($product->getProductName()) ?>&quot; permanently? It cannot be restored after this.">
                                <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                                <input type="hidden" name="productId" value="<?= escapeHtml($product->getProductId()) ?>">
                                <button type="submit" class="button button--small button--danger-ghost">Delete permanently</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
