<?php
/**
 * View:     products/edit.php
 * Purpose:  Edit form for the product that was found (Edit function, step 2).
 * Data:     Product $product + see products/partials/product_form.php
 */
?>
<div class="alert alert--info">
    Product found: <strong>#<?= escapeHtml($product->getProductId()) ?> <?= escapeHtml($product->getProductName()) ?></strong>
    (code <span class="code-tag"><?= escapeHtml($product->getProductCode()) ?></span>). Moving it to another category gives it a new code in that category.
    Change the fields below and press <em>Save Changes</em>.
</div>
<?php ViewService::renderPartial('products/partials/product_form', [
    'formInput' => $formInput + ['productId' => $product->getProductId()],
    'validationErrors' => $validationErrors,
    'categories' => $categories,
    'formAction' => 'update',
    'submitLabel' => 'Save Changes',
    'csrfToken' => $csrfToken,
]); ?>
