<?php
/**
 * Partial:  products/partials/product_form.php
 * Purpose:  The product input fields, shared by the Add and Edit pages.
 * Data:     array      $formInput        - values to show in the fields
 *           array      $validationErrors - field name => error message
 *           Category[] $categories
 *           string     $formAction       - route the form posts to ("store" or "update")
 *           string     $submitLabel
 *           string     $csrfToken
 */
$fieldValue = static fn (string $fieldName): string => escapeHtml($formInput[$fieldName] ?? '');
$fieldError = static fn (string $fieldName): ?string => $validationErrors[$fieldName] ?? null;
?>
<?php if ($validationErrors !== []): ?>
    <div class="alert alert--error" role="alert">
        <strong>Please fix <?= count($validationErrors) ?> problem(s) below.</strong>
    </div>
<?php endif; ?>

<form class="card form" method="post" action="<?= escapeHtml(buildUrl($formAction)) ?>" novalidate data-product-form>
    <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
    <?php if (isset($formInput['productId'])): ?>
        <input type="hidden" name="productId" value="<?= $fieldValue('productId') ?>">
    <?php endif; ?>

    <div class="form__row">
        <label for="productName">Product Name <span class="required">*</span></label>
        <input type="text" id="productName" name="productName" value="<?= $fieldValue('productName') ?>"
               maxlength="<?= ProductValidator::MAX_PRODUCT_NAME_LENGTH ?>" required
               placeholder="e.g. Kampung Eggs (Tray of 10)" data-char-counter>
        <?php if ($fieldError('productName')): ?><p class="field-error"><?= escapeHtml($fieldError('productName')) ?></p><?php endif; ?>
    </div>

    <div class="form__row">
        <label for="categoryId">Category <span class="required">*</span></label>
        <select id="categoryId" name="categoryId" required>
            <option value="">-- Choose a category --</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category->getCategoryId() ?>"
                    <?= (string) ($formInput['categoryId'] ?? '') === (string) $category->getCategoryId() ? 'selected' : '' ?>>
                    <?= escapeHtml($category->getCategoryName()) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($fieldError('categoryId')): ?><p class="field-error"><?= escapeHtml($fieldError('categoryId')) ?></p><?php endif; ?>
    </div>

    <div class="form__row">
        <label for="description">Description <span class="required">*</span></label>
        <textarea id="description" name="description" rows="4" required
                  maxlength="<?= ProductValidator::MAX_DESCRIPTION_LENGTH ?>"
                  placeholder="Size, origin, storage tips..." data-char-counter><?= $fieldValue('description') ?></textarea>
        <?php if ($fieldError('description')): ?><p class="field-error"><?= escapeHtml($fieldError('description')) ?></p><?php endif; ?>
    </div>

    <div class="form__grid">
        <div class="form__row">
            <label for="price">Price (<?= escapeHtml(CURRENCY_SYMBOL) ?>) <span class="required">*</span></label>
            <input type="number" id="price" name="price" value="<?= $fieldValue('price') ?>"
                   min="0.01" max="<?= ProductValidator::MAX_PRICE ?>" step="0.01" required placeholder="0.00">
            <?php if ($fieldError('price')): ?><p class="field-error"><?= escapeHtml($fieldError('price')) ?></p><?php endif; ?>
        </div>
        <div class="form__row">
            <label for="stockQuantity">Stock Quantity <span class="required">*</span></label>
            <input type="number" id="stockQuantity" name="stockQuantity" value="<?= $fieldValue('stockQuantity') ?>"
                   min="0" max="<?= ProductValidator::MAX_STOCK_QUANTITY ?>" step="1" required placeholder="0">
            <?php if ($fieldError('stockQuantity')): ?><p class="field-error"><?= escapeHtml($fieldError('stockQuantity')) ?></p><?php endif; ?>
        </div>
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary"><?= escapeHtml($submitLabel) ?></button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('list')) ?>">Cancel</a>
    </div>
</form>
