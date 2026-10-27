<?php
/**
 * View:     auth/register.php
 * Purpose:  Customer registration form (Register use case).
 * Data:     array $formInput, array $validationErrors, string $csrfToken
 */
?>
<div class="auth-layout">
    <section class="card auth-card auth-card--wide">
        <h2 class="auth-card__title">Create your account</h2>
        <p class="page__lead">Register once to order from local sellers and track your deliveries.</p>

        <?php if ($validationErrors !== []): ?>
            <div class="alert alert--error" role="alert"><strong>Please fix <?= count($validationErrors) ?> problem(s) below.</strong></div>
        <?php endif; ?>

        <form method="post" action="<?= escapeHtml(buildUrl('register_submit')) ?>" novalidate>
            <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
            <?php ViewService::renderPartial('auth/partials/customer_fields', ['formInput' => $formInput, 'validationErrors' => $validationErrors]); ?>
            <?php ViewService::renderPartial('auth/partials/password_fields', ['validationErrors' => $validationErrors]); ?>
            <div class="form__actions">
                <button type="submit" class="button button--primary button--wide">Create account</button>
            </div>
        </form>
        <p class="auth-card__switch">Already registered? <a href="<?= escapeHtml(buildUrl('login')) ?>">Log in &rarr;</a></p>
    </section>
</div>
