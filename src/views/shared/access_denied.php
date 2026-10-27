<?php
/**
 * View:     shared/access_denied.php
 * Purpose:  Shown (HTTP 403) when a logged-in user opens a page for the other role.
 * Data:     ?User $currentUser (layout data)
 */
?>
<section class="card card--narrow">
    <div class="not-found" role="alert">
        <div class="not-found__icon" aria-hidden="true">!</div>
        <div>
            <h2>This page is not for your account</h2>
            <p>You are logged in as <?= escapeHtml($currentUser?->getName() ?? 'a visitor') ?>.
                <?= $currentUser?->isAdministrator() ? 'Shopping pages are for customers.' : 'Management pages are for administrators.' ?></p>
        </div>
    </div>
    <div class="form__actions">
        <a class="button button--primary" href="<?= escapeHtml(buildUrl($currentUser?->isAdministrator() ? 'dashboard' : 'shop')) ?>">Go to my home page</a>
    </div>
</section>
