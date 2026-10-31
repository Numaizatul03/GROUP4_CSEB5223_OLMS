<?php
/**
 * View:     categories/list.php
 * Purpose:  Manage Categories - search, results / "Not found", then all categories.
 * Data:     array $searchInput, array $searchErrors, bool $hasSearched,
 *           Category[] $searchResults, Category[] $categories, ?int $highlightCategoryId
 */
?>
<form class="card form search-form" method="get" action="index.php" novalidate>
    <input type="hidden" name="action" value="categories">
    <div class="form__grid form__grid--search-short">
        <div class="form__row">
            <label for="categoryId">Category ID</label>
            <input type="text" id="categoryId" name="categoryId" inputmode="numeric" value="<?= escapeHtml($searchInput['categoryId'] ?? '') ?>" placeholder="e.g. 2">
            <?= fieldErrorHtml($searchErrors, 'categoryId') ?>
        </div>
        <div class="form__row">
            <label for="keyword">Name, code or description contains</label>
            <input type="search" id="keyword" name="keyword" value="<?= escapeHtml($searchInput['keyword'] ?? '') ?>" placeholder="e.g. bakery or BAK">
        </div>
    </div>
    <div class="form__actions">
        <button type="submit" class="button button--primary">Search</button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('categories')) ?>">Clear</a>
        <a class="button button--ghost push-right" href="<?= escapeHtml(buildUrl('category_create')) ?>">+ Add Category</a>
    </div>
</form>

<?php if ($hasSearched): ?>
    <section class="card">
        <?php if ($searchResults === []): ?>
            <?php ViewService::renderPartial('shared/partials/not_found_box', ['notFoundMessage' => 'No category matches your search. Check the ID or spelling.']); ?>
        <?php else: ?>
            <div class="card__header"><h2>Search Results <span class="count-pill"><?= count($searchResults) ?> found</span></h2></div>
            <?php ViewService::renderPartial('categories/partials/category_table', ['categories' => $searchResults]); ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card card--muted">
    <div class="card__header"><h2>All Categories <span class="count-pill"><?= count($categories) ?> item(s)</span></h2></div>
    <?php ViewService::renderPartial('categories/partials/category_table', ['categories' => $categories, 'highlightCategoryId' => $highlightCategoryId]); ?>
</section>
