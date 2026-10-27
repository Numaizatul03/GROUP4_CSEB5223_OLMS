<?php
/**
 * Partial:  auth/partials/customer_fields.php
 * Purpose:  Name, email, phone and address fields - shared by registration,
 *           the customer profile and the administrator's customer form.
 * Data:     array $formInput, array $validationErrors, bool $isCustomer (show phone + address)
 */
$isCustomer ??= true;
$fieldValue = static fn (string $fieldName): string => escapeHtml($formInput[$fieldName] ?? '');
?>
<div class="form__grid">
    <div class="form__row">
        <label for="name">Full name <span class="required">*</span></label>
        <input type="text" id="name" name="name" value="<?= $fieldValue('name') ?>" maxlength="<?= UserValidator::MAX_NAME_LENGTH ?>" required autocomplete="name">
        <?= fieldErrorHtml($validationErrors, 'name') ?>
    </div>
    <div class="form__row">
        <label for="email">Email <span class="required">*</span></label>
        <input type="email" id="email" name="email" value="<?= $fieldValue('email') ?>" maxlength="<?= UserValidator::MAX_EMAIL_LENGTH ?>" required autocomplete="email">
        <?= fieldErrorHtml($validationErrors, 'email') ?>
    </div>
</div>
<?php if ($isCustomer): ?>
    <div class="form__row">
        <label for="phoneNumber">Phone number <span class="required">*</span></label>
        <input type="tel" id="phoneNumber" name="phoneNumber" value="<?= $fieldValue('phoneNumber') ?>" placeholder="e.g. 012-3456789" required autocomplete="tel">
        <?= fieldErrorHtml($validationErrors, 'phoneNumber') ?>
    </div>
    <div class="form__row">
        <label for="shippingAddress">Shipping address <span class="required">*</span></label>
        <textarea id="shippingAddress" name="shippingAddress" rows="3" maxlength="<?= UserValidator::MAX_ADDRESS_LENGTH ?>" required
                  placeholder="House number, street, postcode, town" autocomplete="street-address"><?= $fieldValue('shippingAddress') ?></textarea>
        <?= fieldErrorHtml($validationErrors, 'shippingAddress') ?>
    </div>
<?php endif; ?>
