<?php
/**
 * View: products/create.php
 * Purpose: Display the Add Product form.
 * Data:
 * - array $formInput
 * - array $validationErrors
 * - array $categories
 * - string $csrfToken
 */

$productName = (string) ($formInput['productName'] ?? '');
$categoryId = (string) ($formInput['categoryId'] ?? '');
$description = (string) ($formInput['description'] ?? '');
$price = (string) ($formInput['price'] ?? '');
$stockQuantity = (string) ($formInput['stockQuantity'] ?? '');
?>

<section class="card">
    <h1>Add Product</h1>

    <form method="post" action="<?= escapeHtml(buildUrl('store')) ?>">
        <input
            type="hidden"
            name="csrfToken"
            value="<?= escapeHtml($csrfToken) ?>"
        >

        <div class="form__group">
            <label for="productName">Product Name *</label>

            <input
                type="text"
                id="productName"
                name="productName"
                value="<?= escapeHtml($productName) ?>"
                maxlength="100"
                required
            >

            <?php if (isset($validationErrors['productName'])): ?>
                <p class="form__error">
                    <?= escapeHtml($validationErrors['productName']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label for="categoryId">Category *</label>

            <select id="categoryId" name="categoryId" required>
                <option value="">Select category</option>

                <?php foreach ($categories as $category): ?>
                    <?php
                    $currentCategoryId = (string) $category['categoryId'];
                    ?>

                    <option
                        value="<?= escapeHtml($currentCategoryId) ?>"
                        <?= $categoryId === $currentCategoryId ? 'selected' : '' ?>
                    >
                        <?= escapeHtml((string) $category['categoryName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (isset($validationErrors['categoryId'])): ?>
                <p class="form__error">
                    <?= escapeHtml($validationErrors['categoryId']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label for="description">Description *</label>

            <textarea
                id="description"
                name="description"
                maxlength="1000"
                required
            ><?= escapeHtml($description) ?></textarea>

            <?php if (isset($validationErrors['description'])): ?>
                <p class="form__error">
                    <?= escapeHtml($validationErrors['description']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label for="price">Price (RM) *</label>

            <input
                type="number"
                id="price"
                name="price"
                value="<?= escapeHtml($price) ?>"
                min="0.01"
                max="99999.99"
                step="0.01"
                required
            >

            <?php if (isset($validationErrors['price'])): ?>
                <p class="form__error">
                    <?= escapeHtml($validationErrors['price']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label for="stockQuantity">Stock Quantity *</label>

            <input
                type="number"
                id="stockQuantity"
                name="stockQuantity"
                value="<?= escapeHtml($stockQuantity) ?>"
                min="0"
                max="100000"
                step="1"
                required
            >

            <?php if (isset($validationErrors['stockQuantity'])): ?>
                <p class="form__error">
                    <?= escapeHtml($validationErrors['stockQuantity']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="form__actions">
            <button type="submit" class="button button--primary">
                Save Product
            </button>

            <a
                href="<?= escapeHtml(buildUrl('list')) ?>"
                class="button button--ghost"
            >
                Cancel
            </a>
        </div>
    </form>
</section>
