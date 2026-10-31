<?php
/**
 * View:     categories/form.php
 * Purpose:  Add Category and Edit Category form.
 * Data:     array $formInput, array $validationErrors, ?Category $category (null when adding), string $csrfToken
 */
$isEditing = $category !== null;
?>
<?php if ($isEditing): ?>
    <div class="alert alert--info">Category found: <strong>#<?= $category->getCategoryId() ?> <?= escapeHtml($category->getCategoryName()) ?></strong>
        (code <span class="code-tag"><?= escapeHtml($category->getCategoryCode()) ?></span>, <?= $category->getProductCount() ?> product(s)).
        Change the fields below and press <em>Save Changes</em>. The code stays the same if you rename the category.</div>
<?php endif; ?>
<?php if (!$isEditing): ?>
    <p class="page__lead">A category code (e.g. <span class="code-tag">FRF</span> for Frozen Food) is made automatically from the name.</p>
<?php endif; ?>
<?php if ($validationErrors !== []): ?>
    <div class="alert alert--error" role="alert"><strong>Please fix <?= count($validationErrors) ?> problem(s) below.</strong></div>
<?php endif; ?>

<form class="card form" method="post" action="<?= escapeHtml(buildUrl($isEditing ? 'category_update' : 'category_store')) ?>" novalidate>
    <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
    <?php if ($isEditing): ?><input type="hidden" name="categoryId" value="<?= $category->getCategoryId() ?>"><?php endif; ?>
    <div class="form__row">
        <label for="categoryName">Category name <span class="required">*</span></label>
        <input type="text" id="categoryName" name="categoryName" value="<?= escapeHtml($formInput['categoryName'] ?? '') ?>"
               maxlength="<?= CategoryValidator::MAX_CATEGORY_NAME_LENGTH ?>" required placeholder="e.g. Frozen Food" data-char-counter>
        <?= fieldErrorHtml($validationErrors, 'categoryName') ?>
    </div>
    <div class="form__row">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3" maxlength="<?= CategoryValidator::MAX_DESCRIPTION_LENGTH ?>"
                  placeholder="What kind of products belong here?" data-char-counter><?= escapeHtml($formInput['description'] ?? '') ?></textarea>
        <?= fieldErrorHtml($validationErrors, 'description') ?>
    </div>
    <div class="form__actions">
        <button type="submit" class="button button--primary"><?= $isEditing ? 'Save Changes' : 'Save Category' ?></button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('categories')) ?>">Cancel</a>
    </div>
</form>
