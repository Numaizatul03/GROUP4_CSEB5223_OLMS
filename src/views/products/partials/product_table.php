<?php
/**
 * Partial:  products/partials/product_table.php
 * Purpose:  Show a list of products as a table (used by every Product page).
 * Data:     Product[] $products
 *           string[]  $rowActions          - any of "view", "edit", "delete"
 *           ?int      $highlightProductId  - row to highlight (newly added / edited)
 *           string    $emptyMessage        - text shown when the list is empty
 */
$rowActions ??= ['view', 'edit', 'delete'];
$highlightProductId ??= null;
$emptyMessage ??= 'There are no products yet.';
$actionLabels = [
    'view' => ['label' => 'View', 'route' => 'view', 'style' => 'button--ghost'],
    'edit' => ['label' => 'Edit', 'route' => 'edit', 'style' => 'button--ghost'],
    'delete' => ['label' => 'Delete', 'route' => 'delete_confirm', 'style' => 'button--danger-ghost'],
];
?>
<?php if ($products === []): ?>
    <p class="empty-state"><?= escapeHtml($emptyMessage) ?></p>
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
                <th>Status</th>
                <th class="is-actions">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr class="<?= $product->getProductId() === $highlightProductId ? 'is-highlighted' : '' ?>">
                    <td class="is-number"><?= escapeHtml($product->getProductId()) ?></td>
                    <td>
                        <strong><?= escapeHtml($product->getProductName()) ?></strong>
                        <span class="cell-note"><?= escapeHtml($product->getDescription()) ?></span>
                    </td>
                    <td><?= escapeHtml($product->getCategoryName()) ?></td>
                    <td class="is-number"><?= escapeHtml(formatPrice($product->getPrice())) ?></td>
                    <td class="is-number"><?= escapeHtml($product->getStockQuantity()) ?></td>
                    <td><?php ViewService::renderPartial('products/partials/stock_badge', ['stockStatus' => $product->getStockStatus()]); ?></td>
                    <td class="is-actions">
                        <?php foreach ($rowActions as $rowAction): ?>
                            <a class="button button--small <?= $actionLabels[$rowAction]['style'] ?>"
                               href="<?= escapeHtml(buildUrl($actionLabels[$rowAction]['route'], ['productId' => $product->getProductId()])) ?>"><?= $actionLabels[$rowAction]['label'] ?></a>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
                        </tbody>
            <tfoot>
            <tr>
                <td colspan="7" class="table-summary">
                    <?= count($products) ?> product(s) &middot;
                    <?= count(array_filter($products, static fn (Product $product) => $product->getStockStatus() === 'Low stock')) ?> low stock &middot;
                    <?= count(array_filter($products, static fn (Product $product) => !$product->checkStock())) ?> out of stock
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>
