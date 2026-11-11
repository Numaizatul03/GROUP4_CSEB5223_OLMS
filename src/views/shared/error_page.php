<?php
/**
 * View:     shared/error_page.php
 * Purpose:  Friendly page for errors stopped by the middleware (400, 405, 419, 429, 500, 503).
 * Data:     string $errorMessage, int $statusCode, ?string $referenceCode
 * Description: Never shows technical details. For 500 errors the reference code
 *              lets staff find the details in storage/logs/app.log.
 */
$errorIcons = [400 => '!', 405 => '!', 419 => '↻', 429 => '⏳', 500 => '!', 503 => '…'];
?>
<section class="card card--narrow">
    <div class="not-found" role="alert">
        <div class="not-found__icon" aria-hidden="true"><?= escapeHtml($errorIcons[$statusCode] ?? '!') ?></div>
        <div>
            <h2><?= escapeHtml($pageTitle) ?></h2>
            <p><?= escapeHtml($errorMessage) ?></p>
            <?php if ($referenceCode !== null): ?>
                <p class="field-hint">Error code: <span class="code-tag"><?= escapeHtml($referenceCode) ?></span></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="form__actions">
        <a class="button button--primary" href="index.php">Go to the home page</a>
        <?php if ($statusCode === 419 || $statusCode === 429): ?>
            <a class="button button--ghost" href="<?= escapeHtml(buildUrl('login')) ?>">Back to log in</a>
        <?php endif; ?>
    </div>
</section>
