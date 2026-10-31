<?php
/**
 * Partial:  categories/partials/category_table.php
 * Purpose:  Table of categories with View/Edit/Delete actions.
 * Data:     Category[] $categories, ?int $highlightCategoryId
 */
$highlightCategoryId ??= null;
?>
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr><th class="is-number">ID</th><th>Category</th><th>Description</th><th class="is-number">Products</th><th class="is-actions">Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr class="<?= $category->getCategoryId() === $highlightCategoryId ? 'is-highlighted' : '' ?>">
                <td class="is-number"><?= $category->getCategoryId() ?></td>
                <td><strong><?= escapeHtml($category->getCategoryName()) ?></strong></td>
                <td><?= escapeHtml($category->getDescription()) ?></td>
                <td class="is-number"><?= $category->getProductCount() ?></td>
                <td class="is-actions">
                    <a class="button button--small button--ghost" href="<?= escapeHtml(buildUrl('shop', ['categoryId' => $category->getCategoryId()])) ?>">View in shop</a>
                    <a class="button button--small button--edit" href="<?= escapeHtml(buildUrl('category_edit', ['categoryId' => $category->getCategoryId()])) ?>">Edit</a>
                    <a class="button button--small button--danger-ghost" href="<?= escapeHtml(buildUrl('category_delete_confirm', ['categoryId' => $category->getCategoryId()])) ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
