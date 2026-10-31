<?php
/**
 * View:     products/create.php
 * Purpose:  "Add Product" page (Create function).
 * Data:     see products/partials/product_form.php
 */
?>
<p class="page__lead">Fill in the product details. Fields marked <span class="required">*</span> are required.
    A product code such as <span class="code-tag">FRP-004</span> is given automatically from the category.</p>
<?php ViewService::renderPartial('products/partials/product_form', [
    'formInput' => $formInput,
    'validationErrors' => $validationErrors,
    'categories' => $categories,
    'formAction' => 'store',
    'submitLabel' => 'Save Product',
    'csrfToken' => $csrfToken,
]); ?>
