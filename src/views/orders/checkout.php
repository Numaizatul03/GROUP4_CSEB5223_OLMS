<?php
/**
 * View:     orders/checkout.php
 * Purpose:  Checkout - review the cart and confirm the delivery address.
 * Data:     ShoppingCart $shoppingCart, array $formInput, array $validationErrors, string $csrfToken
 */
?>
<div class="two-column two-column--checkout">
    <section class="card">
        <div class="card__header"><h2>Delivery</h2></div>
        <form method="post" action="<?= escapeHtml(buildUrl('checkout_submit')) ?>" novalidate>
            <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
            <div class="form__row">
                <label for="shippingAddress">Deliver to <span class="required">*</span></label>
                <textarea id="shippingAddress" name="shippingAddress" rows="4" maxlength="<?= OrderValidator::MAX_ADDRESS_LENGTH ?>" required><?= escapeHtml($formInput['shippingAddress'] ?? '') ?></textarea>
                <p class="field-hint">Taken from your profile. Changing it here only affects this order.</p>
                <?= fieldErrorHtml($validationErrors, 'shippingAddress') ?>
            </div>
            <div class="form__actions">
                <button type="submit" class="button button--primary">Place order &middot; <?= escapeHtml(formatPrice($shoppingCart->calculateTotal())) ?></button>
                <a class="button button--ghost" href="<?= escapeHtml(buildUrl('cart')) ?>">Back to cart</a>
            </div>
        </form>
    </section>

    <section class="card card--muted">
        <div class="card__header"><h2>Order summary</h2></div>
        <ul class="summary-list">
            <?php foreach ($shoppingCart->getCartItems() as $cartItem): ?>
                <li><span><?= $cartItem->getQuantity() ?> &times; <?= escapeHtml($cartItem->getProductName()) ?></span>
                    <span><?= escapeHtml(formatPrice($cartItem->calculateSubtotal())) ?></span></li>
            <?php endforeach; ?>
            <li class="summary-list__total"><span>Total</span><span><?= escapeHtml(formatPrice($shoppingCart->calculateTotal())) ?></span></li>
        </ul>
        <p class="field-hint">You will choose how to pay on the next page. Stock is reserved as soon as you place the order.</p>
    </section>
</div>
