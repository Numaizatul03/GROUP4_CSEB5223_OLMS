<?php
/**
 * View:     shared/not_found_page.php
 * Purpose:  Full "Not found" page for a record ID that does not exist.
 * Data:     string $notFoundMessage, array $notFoundLinks - button label => URL
 */
?>
<section class="card card--narrow">
    <?php ViewService::renderPartial('shared/partials/not_found_box', ['notFoundMessage' => $notFoundMessage]); ?>
    <div class="form__actions">
        <?php foreach ($notFoundLinks as $linkLabel => $linkUrl): ?>
            <a class="button button--primary" href="<?= escapeHtml($linkUrl) ?>"><?= escapeHtml($linkLabel) ?></a>
        <?php endforeach; ?>
    </div>
</section>
