<?php
/**
 * View:     auth/profile.php
 * Purpose:  The logged-in user's details, the edit form and the change-password form.
 * Data:     User $currentUser, array $profileInput, array $profileErrors, array $passwordErrors, string $csrfToken
 */
$isCustomerAccount = $currentUser instanceof Customer;
?>
<div class="two-column">
    <section class="card">
        <div class="card__header">
            <h2>Account details</h2>
            <span class="count-pill"><?= $isCustomerAccount ? 'Customer' : 'Administrator' ?> #<?= escapeHtml($currentUser->getUserId()) ?></span>
        </div>
        <?php if ($profileErrors !== []): ?>
            <div class="alert alert--error" role="alert"><strong>Please fix <?= count($profileErrors) ?> problem(s) below.</strong></div>
        <?php endif; ?>
        <form method="post" action="<?= escapeHtml(buildUrl('profile_update')) ?>" novalidate>
            <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
            <?php ViewService::renderPartial('auth/partials/customer_fields', [
                'formInput' => $profileInput,
                'validationErrors' => $profileErrors,
                'isCustomer' => $isCustomerAccount,
            ]); ?>
            <p class="field-hint">Member since <?= escapeHtml(formatDateTime($currentUser->getCreatedAt())) ?> &middot;
                last updated <?= escapeHtml(formatDateTime($currentUser->getUpdatedAt())) ?></p>
            <div class="form__actions">
                <button type="submit" class="button button--primary">Save profile</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card__header"><h2>Change password</h2></div>
        <form method="post" action="<?= escapeHtml(buildUrl('password_update')) ?>" novalidate>
            <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
            <div class="form__row">
                <label for="currentPassword">Current password <span class="required">*</span></label>
                <input type="password" id="currentPassword" name="currentPassword" required autocomplete="current-password">
                <?= fieldErrorHtml($passwordErrors, 'currentPassword') ?>
            </div>
            <?php ViewService::renderPartial('auth/partials/password_fields', ['validationErrors' => $passwordErrors]); ?>
            <div class="form__actions">
                <button type="submit" class="button button--ghost">Change password</button>
            </div>
        </form>
    </section>
</div>
