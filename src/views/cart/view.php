<?php
/**
 * View:     cart/view.php
 * Purpose:  Manage Shopping Cart - view, change quantities, remove, clear, go to checkout.
 * Data:     ShoppingCart $shoppingCart, CartItem[] $unavailableItems, string $csrfToken
 */
$unavailableProductIds = array_map(static fn (CartItem $cartItem) => $cartItem->getProductId(), $unavailableItems);
?>
<?php if ($shoppingCart->isEmpty()): ?>
    <section class="card card--narrow">
        <div class="empty-cart">
            <h2>Your cart is empty</h2>
            <p>Browse fresh produce, bakery and pantry items from local sellers.</p>
            <a class="button button--primary" href="<?= escapeHtml(buildUrl('shop')) ?>">Start shopping</a>
        </div>
    </section>
<?php else: ?>
    <?php if ($unavailableItems !== []): ?>
        <div class="alert alert--warning" role="alert">
            Some items have less stock than the quantity in your cart. Lower the quantity or remove them to check out.
        </div>
    <?php endif; ?>

    <section class="card">
        <div class="card__header">
            <h2>Items <span class="count-pill"><?= $shoppingCart->countItems() ?> unit(s)</span></h2>
            <form method="post" action="<?= escapeHtml(buildUrl('cart_clear')) ?>" class="inline-form" data-confirm="Remove every item from your cart?">
                <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                <button type="submit" class="button button--danger-ghost">Clear cart</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr><th>Product</th><th class="is-number">Price</th><th>Quantity</th><th class="is-number">Subtotal</th><th class="is-actions">Remove</th></tr>
                </thead>
                <tbody>
                <?php foreach ($shoppingCart->getCartItems() as $cartItem): ?>
                    <tr class="<?= in_array($cartItem->getProductId(), $unavailableProductIds, true) ? 'is-problem' : '' ?>">
                        <td>
                            <a href="<?= escapeHtml(buildUrl('shop_product', ['productId' => $cartItem->getProductId()])) ?>"><strong><?= escapeHtml($cartItem->getProductName()) ?></strong></a>
                            <span class="cell-note"><?= $cartItem->getStockQuantity() ?> in stock<?= $cartItem->isQuantityAvailable() ? '' : ' - not enough for your quantity' ?></span>
                        </td>
                        <td class="is-number"><?= escapeHtml(formatPrice($cartItem->getPrice())) ?></td>
                        <td>
                            <form method="post" action="<?= escapeHtml(buildUrl('cart_update')) ?>" class="quantity-form">
                                <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                                <input type="hidden" name="productId" value="<?= escapeHtml($cartItem->getProductId()) ?>">
                                <input type="number" name="quantity" value="<?= $cartItem->getQuantity() ?>" min="0" max="<?= CartService::MAX_QUANTITY_PER_ITEM ?>" step="1" aria-label="Quantity of <?= escapeHtml($cartItem->getProductName()) ?>">
                                <button type="submit" class="button button--small button--ghost">Update</button>
                            </form>
                        </td>
                        <td class="is-number"><?= escapeHtml(formatPrice($cartItem->calculateSubtotal())) ?></td>
                        <td class="is-actions">
                            <form method="post" action="<?= escapeHtml(buildUrl('cart_remove')) ?>" class="inline-form">
                                <input type="hidden" name="csrfToken" value="<?= escapeHtml($csrfToken) ?>">
                                <input type="hidden" name="productId" value="<?= escapeHtml($cartItem->getProductId()) ?>">
                                <button type="submit" class="button button--small button--danger-ghost" aria-label="Remove <?= escapeHtml($cartItem->getProductName()) ?>">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="total-row"><td colspan="3">Total</td><td class="is-number"><?= escapeHtml(formatPrice($shoppingCart->calculateTotal())) ?></td><td></td></tr>
                </tfoot>
            </table>
        </div>
        <div class="form__actions form__actions--spaced form__actions--end">
            <a class="button button--ghost" href="<?= escapeHtml(buildUrl('shop')) ?>">Continue shopping</a>
            <?php if ($unavailableItems === []): ?>
                <a class="button button--primary" href="<?= escapeHtml(buildUrl('checkout')) ?>">Proceed to checkout &rarr;</a>
            <?php else: ?>
                <span class="button button--primary is-disabled" aria-disabled="true">Proceed to checkout</span>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
