<?php
/**
 * View:     customers/form.php
 * Purpose:  Add Customer and Edit Customer form (administrator).
 * Data:     array $formInput, array $validationErrors, ?Customer $customer (null when adding), string $csrfToken
 */
$isEditing = $customer !== null;
?>
<?php if ($isEditing): ?>
    <div class="alert alert--info">Customer found: <strong>#<?= $customer->getUserId() ?> <?= escapeHtml($customer->getName()) ?></strong>.
        Change the fields below and press <em>Save Changes</em>. The customer's password is not changed here.</div>
<?php endif; ?>
<?php if ($validationErrors !== []): ?>
    <div class="alert alert--error" role="alert"><strong>Please fix <?= count($validationErrors) ?> problem(s) below.</strong></div>
<?php endif; ?>

<form class="card form" method="post" action="<?= escapeHtml(buildUrl($isEditing ? 'customer_update' : 'customer_store')) ?>" novalidate>
    <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
    <?php if ($isEditing): ?><input type="hidden" name="customerId" value="<?= $customer->getUserId() ?>"><?php endif; ?>
    <?php ViewService::renderPartial('auth/partials/customer_fields', ['formInput' => $formInput, 'validationErrors' => $validationErrors]); ?>
    <?php if (!$isEditing): ?>
        <?php ViewService::renderPartial('auth/partials/password_fields', ['validationErrors' => $validationErrors]); ?>
    <?php endif; ?>
    <div class="form__actions">
        <button type="submit" class="button button--primary"><?= $isEditing ? 'Save Changes' : 'Save Customer' ?></button>
        <a class="button button--ghost" href="<?= escapeHtml(buildUrl('customers')) ?>">Cancel</a>
    </div>
</form>
