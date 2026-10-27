<?php
/**
 * View:     auth/login.php
 * Purpose:  Log in form for customers and administrators.
 * Data:     array $formInput, array $validationErrors, string $returnUrl, string $csrfToken
 */
?>
<div class="auth-layout">
    <section class="card auth-card">
        <h2 class="auth-card__title">Welcome back</h2>
        <p class="page__lead">Log in to shop, check your orders or manage the store.</p>

        <?php if (isset($validationErrors['login'])): ?>
            <div class="alert alert--error" role="alert"><?= escapeHtml($validationErrors['login']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= escapeHtml(buildUrl('login_submit')) ?>" novalidate>
            <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
            <input type="hidden" name="returnUrl" value="<?= escapeHtml($returnUrl) ?>">
            <div class="form__row">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= escapeHtml($formInput['email'] ?? '') ?>" autocomplete="username" required>
                <?= fieldErrorHtml($validationErrors, 'email') ?>
            </div>
            <div class="form__row">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <?= fieldErrorHtml($validationErrors, 'password') ?>
            </div>
            <div class="form__actions">
                <button type="submit" class="button button--primary button--wide">Log in</button>
            </div>
        </form>
        <p class="auth-card__switch">New here? <a href="<?= escapeHtml(buildUrl('register')) ?>">Create an account &rarr;</a></p>
    </section>
</div>
