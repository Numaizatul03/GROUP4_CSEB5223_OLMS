<?php
/**
 * View:     shop/catalog.php
 * Purpose:  Browse Products and Search Products for customers and visitors.
 * Data:     Category[] $categories, array $searchInput, array $searchErrors,
 *           bool $hasSearched, Product[] $products
 */
$selectedCategoryId = (string) ($searchInput['categoryId'] ?? '');
$categoryFilterUrl = static fn (string $categoryId): string =>
    buildUrl('shop', array_filter(['categoryId' => $categoryId, 'keyword' => $searchInput['keyword'] ?? '']));
?>
<form class="card shop-search" method="get" action="index.php" novalidate>
    <input type="hidden" name="action" value="shop">
    <?php if ($selectedCategoryId !== ''): ?><input type="hidden" name="categoryId" value="<?= escapeHtml($selectedCategoryId) ?>"><?php endif; ?>
    <div class="shop-search__row">
        <input type="search" name="keyword" value="<?= escapeHtml($searchInput['keyword'] ?? '') ?>" placeholder="Search eggs, rice, kopi..." aria-label="Search products">
        <input type="text" name="minPrice" value="<?= escapeHtml($searchInput['minPrice'] ?? '') ?>" inputmode="decimal" placeholder="Min RM" aria-label="Minimum price">
        <input type="text" name="maxPrice" value="<?= escapeHtml($searchInput['maxPrice'] ?? '') ?>" inputmode="decimal" placeholder="Max RM" aria-label="Maximum price">
        <button type="submit" class="button button--primary">Search</button>
        <?php if ($hasSearched): ?><a class="button button--ghost" href="<?= escapeHtml(buildUrl('shop')) ?>">Clear</a><?php endif; ?>
    </div>
    <?= fieldErrorHtml($searchErrors, 'minPrice') . fieldErrorHtml($searchErrors, 'maxPrice') ?>
    <div class="pill-row" aria-label="Categories">
        <a class="pill <?= $selectedCategoryId === '' ? 'is-active' : '' ?>" href="<?= escapeHtml($categoryFilterUrl('')) ?>">All</a>
        <?php foreach ($categories as $category): ?>
            <a class="pill <?= $selectedCategoryId === (string) $category->getCategoryId() ? 'is-active' : '' ?>"
               href="<?= escapeHtml($categoryFilterUrl((string) $category->getCategoryId())) ?>"><?= escapeHtml($category->getCategoryName()) ?></a>
        <?php endforeach; ?>
    </div>
</form>

<?php if ($hasSearched && $products === []): ?>
    <section class="card">
        <?php ViewService::renderPartial('shared/partials/not_found_box', [
            'notFoundMessage' => 'No product matches your search. Try another word, a different category or a wider price range.',
        ]); ?>
    </section>
<?php else: ?>
    <p class="result-count"><?= count($products) ?> product(s)<?= $hasSearched ? ' found' : '' ?></p>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <a class="product-card" href="<?= escapeHtml(buildUrl('shop_product', ['productId' => $product->getProductId()])) ?>">
                <span class="product-card__swatch product-card__swatch--<?= $product->getCategoryId() % 3 ?>" aria-hidden="true">
                    <?= escapeHtml(mb_strtoupper(mb_substr($product->getProductName(), 0, 1))) ?>
                </span>
                <span class="product-card__category"><?= escapeHtml($product->getCategoryName()) ?></span>
                <strong class="product-card__name"><?= escapeHtml($product->getProductName()) ?></strong>
                <span class="product-card__footer">
                    <span class="product-card__price"><?= escapeHtml(formatPrice($product->getPrice())) ?></span>
                    <?php ViewService::renderPartial('products/partials/stock_badge', ['stockStatus' => $product->getStockStatus()]); ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
