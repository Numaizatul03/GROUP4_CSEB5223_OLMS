<?php
/**
 * View:     products/edit_result.php
 * Purpose:  Display the edited data so the user can validate the changes (Edit, step 4).
 * Data:     ?array    $previousProductDetails - values before saving (null after a page refresh)
 *           array     $currentProductDetails  - values read back from the database
 *           Product[] $products, int $highlightProductId
 */
$comparedFields = [
    'productName' => 'Product Name',
    'categoryName' => 'Category',
    'description' => 'Description',
    'price' => 'Price',
    'stockQuantity' => 'Stock Quantity',
    'stockStatus' => 'Stock Status',
];
$formatFieldValue = static fn (string $fieldName, mixed $fieldValue): string =>
    $fieldName === 'price' ? formatPrice((float) $fieldValue) : (string) $fieldValue;
?>
<div class="alert alert--success" role="status">
    Product #<?= escapeHtml($currentProductDetails['productId']) ?> was updated at <?= escapeHtml($currentProductDetails['updatedAt']) ?>.
</div>

<section class="card">
    <div class="card__header">
        <h2>Changes Saved</h2>
    </div>
    <?php if ($previousProductDetails === null): ?>
        <?php ViewService::renderPartial('products/partials/product_details', ['productDetails' => $currentProductDetails]); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table compare-table">
                <thead>
                <tr><th>Attribute</th><th>Before</th><th>After (saved in database)</th></tr>
                </thead>
                <tbody>
                <?php foreach ($comparedFields as $fieldName => $fieldLabel): ?>
                    <?php $isChanged = $previousProductDetails[$fieldName] != $currentProductDetails[$fieldName]; ?>
                    <tr class="<?= $isChanged ? 'is-changed' : '' ?>">
                        <th scope="row"><?= escapeHtml($fieldLabel) ?></th>
                        <td><?= escapeHtml($formatFieldValue($fieldName, $previousProductDetails[$fieldName])) ?></td>
                        <td>
                            <?= escapeHtml($formatFieldValue($fieldName, $currentProductDetails[$fieldName])) ?>
                            <?php if ($isChanged): ?><span class="badge badge--info">changed</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <div class="form__actions">
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('edit', ['productId' => $currentProductDetails['productId']])) ?>">Edit again</a>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('edit_search')) ?>">Edit another product</a>
    </div>
</section>

<section class="card card--muted">
    <div class="card__header">
        <h2>All Products <span class="count-pill"><?= count($products) ?> item(s)</span></h2>
    </div>
    <?php ViewService::renderPartial('products/partials/product_table', [
        'products' => $products,
        'highlightProductId' => $highlightProductId,
    ]); ?>
</section>
