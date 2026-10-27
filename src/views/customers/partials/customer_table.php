<?php
/**
 * Partial:  customers/partials/customer_table.php
 * Purpose:  Table of customers with View/Edit/Delete actions.
 * Data:     Customer[] $customers, array $orderCounts - customer ID => number of orders, ?int $highlightCustomerId
 */
$highlightCustomerId ??= null;
?>
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr><th class="is-number">ID</th><th>Customer</th><th>Phone</th><th>Shipping Address</th><th class="is-number">Orders</th><th class="is-actions">Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $customer): ?>
            <tr class="<?= $customer->getUserId() === $highlightCustomerId ? 'is-highlighted' : '' ?>">
                <td class="is-number"><?= $customer->getUserId() ?></td>
                <td><strong><?= escapeHtml($customer->getName()) ?></strong><span class="cell-note"><?= escapeHtml($customer->getEmail()) ?></span></td>
                <td><?= escapeHtml($customer->getPhoneNumber()) ?></td>
                <td class="cell-note"><?= escapeHtml($customer->getShippingAddress()) ?></td>
                <td class="is-number"><?= $orderCounts[$customer->getUserId()] ?? 0 ?></td>
                <td class="is-actions">
                    <a class="button button--small button--ghost" href="<?= escapeHtml(buildUrl('customer', ['customerId' => $customer->getUserId()])) ?>">View</a>
                    <a class="button button--small button--ghost" href="<?= escapeHtml(buildUrl('customer_edit', ['customerId' => $customer->getUserId()])) ?>">Edit</a>
                    <a class="button button--small button--danger-ghost" href="<?= escapeHtml(buildUrl('customer_delete_confirm', ['customerId' => $customer->getUserId()])) ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
