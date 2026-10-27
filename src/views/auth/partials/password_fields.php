<?php
/**
 * Partial:  auth/partials/password_fields.php
 * Purpose:  New password + confirmation fields (registration, new customer, change password).
 * Data:     array $validationErrors
 */
?>
<div class="form__grid">
    <div class="form__row">
        <label for="password">Password <span class="required">*</span></label>
        <input type="password" id="password" name="password" minlength="<?= UserValidator::MIN_PASSWORD_LENGTH ?>" required autocomplete="new-password">
        <p class="field-hint">At least <?= UserValidator::MIN_PASSWORD_LENGTH ?> characters, with a letter and a number.</p>
        <?= fieldErrorHtml($validationErrors, 'password') ?>
    </div>
    <div class="form__row">
        <label for="passwordConfirmation">Confirm password <span class="required">*</span></label>
        <input type="password" id="passwordConfirmation" name="passwordConfirmation" required autocomplete="new-password">
        <?= fieldErrorHtml($validationErrors, 'passwordConfirmation') ?>
    </div>
</div>
