<?php
/**
 * View:     shared/delete_confirm.php
 * Purpose:  Show the complete record and ask the user to confirm deletion.
 *           Used by categories, customers and orders.
 * Data:     string  $entityLabel   - e.g. "category"
 *           array   $detailRows    - label => value
 *           ?string $blockedReason - when set, deletion is not allowed and the reason is shown
 *           string  $deleteAction  - route the form posts to
 *           array   $hiddenFields  - field name => value (the record ID)
 *           string  $cancelUrl, string $csrfToken
 */
?>
<?php if ($blockedReason === null): ?>
    <div class="alert alert--warning" role="alert">
        <strong>Are you sure you want to delete this <?= escapeHtml($entityLabel) ?>?</strong> This cannot be undone.
    </div>
<?php else: ?>
    <div class="alert alert--error" role="alert"><strong>This <?= escapeHtml($entityLabel) ?> cannot be deleted.</strong> <?= escapeHtml($blockedReason) ?></div>
<?php endif; ?>

<section class="card card--narrow card--danger">
    <?php ViewService::renderPartial('shared/partials/detail_list', ['detailRows' => $detailRows]); ?>

    <form method="post" action="<?= escapeHtml(buildUrl($deleteAction)) ?>" class="form__actions">
        <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
        <?php foreach ($hiddenFields as $fieldName => $fieldValue): ?>
            <input type="hidden" name="<?= escapeHtml($fieldName) ?>" value="<?= escapeHtml($fieldValue) ?>">
        <?php endforeach; ?>
        <?php if ($blockedReason === null): ?>
            <button type="submit" class="button button--danger">Yes, delete this <?= escapeHtml($entityLabel) ?></button>
        <?php endif; ?>
        <a class="button button--ghost" href="<?= escapeHtml($cancelUrl) ?>"><?= $blockedReason === null ? 'No, keep it' : 'Go back' ?></a>
    </form>
</section>
