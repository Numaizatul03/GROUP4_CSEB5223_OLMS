<?php
/**
 * View:     products/search.php
 * Purpose:  Shared search page for Search, Edit (step 1) and Delete (step 1).
 * Data:     string     $searchMode    - "search", "edit" or "delete"
 *           array      $searchInput   - criteria typed by the user ($_GET)
 *           array      $searchErrors  - field name => error message
 *           Category[] $categories
 *           bool       $hasSearched   - true when valid criteria were submitted
 *           Product[]  $searchResults
 *           Product[]  $products      - full list shown underneath (data display)
 */
$searchIntro = [
    'search' => 'Enter one or more criteria. Leave a field empty to ignore it.',
    'edit' => 'Find the product you want to edit by its ID or name, then press Edit.',
    'delete' => 'Find the product you want to delete by its ID or name, then press Delete.',
];
$resultActions = [
    'search' => ['view', 'edit', 'delete'],
    'edit' => ['edit'],
    'delete' => ['delete'],
];
$searchRoute = $searchMode === 'search' ? 'search' : $searchMode . '_search';
$inputValue = static fn (string $fieldName): string => escapeHtml($searchInput[$fieldName] ?? '');
$searchError = static fn (string $fieldName): ?string => $searchErrors[$fieldName] ?? null;
?>
<p class="page__lead"><?= escapeHtml($searchIntro[$searchMode]) ?></p>

<form class="card form search-form" method="get" action="index.php" novalidate>
    <input type="hidden" name="action" value="<?= escapeHtml($searchRoute) ?>">
    <div class="form__grid form__grid--search">
        <div class="form__row">
            <label for="productId">Product ID</label>
            <input type="text" id="productId" name="productId" inputmode="numeric" value="<?= $inputValue('productId') ?>" placeholder="e.g. 3">
            <?php if ($searchError('productId')): ?><p class="field-error"><?= escapeHtml($searchError('productId')) ?></p><?php endif; ?>
        </div>
        <div class="form__row form__row--wide">
            <label for="keyword">Name or description contains</label>
            <input type="search" id="keyword" name="keyword" value="<?= $inputValue('keyword') ?>" placeholder="e.g. eggs">
        </div>
        <?php if ($searchMode === 'search'): ?>
            <div class="form__row">
                <label for="categoryId">Category</label>
                <select id="categoryId" name="categoryId">
                    <option value="">Any category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category->getCategoryId() ?>"
                            <?= (string) ($searchInput['categoryId'] ?? '') === (string) $category->getCategoryId() ? 'selected' : '' ?>>
                            <?= escapeHtml($category->getCategoryName()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__row">
                <label for="minPrice">Min price (<?= escapeHtml(CURRENCY_SYMBOL) ?>)</label>
                <input type="text" id="minPrice" name="minPrice" inputmode="decimal" value="<?= $inputValue('minPrice') ?>" placeholder="0.00">
                <?php if ($searchError('minPrice')): ?><p class="field-error"><?= escapeHtml($searchError('minPrice')) ?></p><?php endif; ?>
            </div>
            <div class="form__row">
                <label for="maxPrice">Max price (<?= escapeHtml(CURRENCY_SYMBOL) ?>)</label>
                <input type="text" id="maxPrice" name="maxPrice" inputmode="decimal" value="<?= $inputValue('maxPrice') ?>" placeholder="99.00">
                <?php if ($searchError('maxPrice')): ?><p class="field-error"><?= escapeHtml($searchError('maxPrice')) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="form__actions">
        <button type="submit" class="button button--primary">Search</button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl($searchRoute)) ?>">Clear</a>
    </div>
</form>

<?php if ($hasSearched): ?>
    <section class="card">
        <?php if ($searchResults === []): ?>
            <div class="not-found" role="status">
                <div class="not-found__icon" aria-hidden="true">?</div>
                <div>
                    <h2>Not found</h2>
                    <p>No product matches your search. Check the spelling or the product ID, or try fewer criteria.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card__header">
                <h2>Search Results <span class="count-pill"><?= count($searchResults) ?> found</span></h2>
            </div>
            <?php ViewService::renderPartial('products/partials/product_table', [
                'products' => $searchResults,
                'rowActions' => $resultActions[$searchMode],
            ]); ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card card--muted">
    <div class="card__header">
        <h2>All Products <span class="count-pill"><?= count($products) ?> item(s)</span></h2>
    </div>
    <?php ViewService::renderPartial('products/partials/product_table', [
        'products' => $products,
        'rowActions' => $resultActions[$searchMode],
    ]); ?>
</section>
