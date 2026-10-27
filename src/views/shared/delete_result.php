<?php
/**
 * View:     shared/delete_result.php
 * Purpose:  Display all data to validate a deletion. Used by categories, customers and orders.
 * Data:     string $entityLabel        - e.g. "Category"
 *           array  $deletedRecord      - id, rows (label => value), countBefore
 *           bool   $isDeletionVerified - true when the database no longer has the record
 *           int    $countAfter
 *           string $listTitle, string $listPartial, array $listData
 */
?>
<div class="alert <?= $isDeletionVerified ? 'alert--success' : 'alert--error' ?>" role="status">
    <?= escapeHtml($entityLabel) ?> #<?= escapeHtml($deletedRecord['id']) ?>
    <?= $isDeletionVerified ? 'was deleted.' : 'could not be deleted. Please try again.' ?>
</div>

<div class="stat-row">
    <div class="stat"><span class="stat__label"><?= escapeHtml($entityLabel) ?>s before</span><span class="stat__value"><?= (int) $deletedRecord['countBefore'] ?></span></div>
    <div class="stat"><span class="stat__label"><?= escapeHtml($entityLabel) ?>s after</span><span class="stat__value"><?= $countAfter ?></span></div>
    <div class="stat">
        <span class="stat__label">Database check</span>
        <span class="stat__value stat__value--text"><?= $isDeletionVerified ? 'ID no longer exists' : 'Record still exists' ?></span>
    </div>
</div>

<section class="card">
    <div class="card__header"><h2>Deleted Record</h2></div>
    <?php ViewService::renderPartial('shared/partials/detail_list', ['detailRows' => $deletedRecord['rows']]); ?>
</section>

<section class="card card--muted">
    <div class="card__header"><h2><?= escapeHtml($listTitle) ?></h2></div>
    <?php ViewService::renderPartial($listPartial, $listData); ?>
</section>
