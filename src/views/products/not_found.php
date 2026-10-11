<?php
/**
 * View:     products/not_found.php
 * Purpose:  "Not found" page when a product ID does not exist (view, edit, delete).
 * Data:     string $requestedProductId, string $searchMode - "search", "edit" or "delete"
 */
$searchRoute = $searchMode === 'search' ? 'search' : $searchMode . '_search';
?>
<section class="card card--narrow">
    <div class="not-found" role="status">
        <div class="not-found__icon" aria-hidden="true">?</div>
        <div>
            <h2>Not found</h2>
            <p>
                <?php if ($requestedProductId === ''): ?>
                    No product was selected.
                <?php else: ?>
                    There is no product with ID <strong><?= escapeHtml($requestedProductId) ?></strong>.
                    It may have been deleted already.
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="form__actions">
        <a class="button button--primary" href="<?= escapeHtml(buildUrl($searchRoute)) ?>">Search again</a>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('list')) ?>">View all products</a>
    </div>
</section>
