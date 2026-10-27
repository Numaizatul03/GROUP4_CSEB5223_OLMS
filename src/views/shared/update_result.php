<?php
/**
 * View:     shared/update_result.php
 * Purpose:  Display edited data next to the old values so the user can validate the change.
 *           Used by profiles, customers, categories and order status updates.
 * Data:     string $successMessage
 *           array  $fieldLabels      - attribute name => label (rows to compare)
 *           ?array $previousDetails  - values before saving (null after a page refresh)
 *           array  $currentDetails   - values read back from the database
 *           array  $backLinks        - button label => URL
 *           string[] $moneyFields    - (optional) attributes shown as prices
 *           string $listTitle, string $listPartial, array $listData - (optional) full list underneath
 */
$moneyFields ??= [];
$formatValue = static fn (string $fieldName, mixed $fieldValue): string =>
    in_array($fieldName, $moneyFields, true) ? formatPrice((float) $fieldValue) : (string) $fieldValue;
?>
<div class="alert alert--success" role="status"><?= escapeHtml($successMessage) ?></div>

<section class="card">
    <div class="card__header"><h2>Changes Saved</h2></div>
    <div class="table-wrap">
        <table class="data-table compare-table">
            <thead>
            <tr><th>Attribute</th><th>Before</th><th>After (saved in database)</th></tr>
            </thead>
            <tbody>
            <?php foreach ($fieldLabels as $fieldName => $fieldLabel): ?>
                <?php
                $beforeValue = $previousDetails === null ? '-' : $formatValue($fieldName, $previousDetails[$fieldName] ?? '');
                $afterValue = $formatValue($fieldName, $currentDetails[$fieldName] ?? '');
                $isChanged = $previousDetails !== null && $beforeValue !== $afterValue;
                ?>
                <tr class="<?= $isChanged ? 'is-changed' : '' ?>">
                    <th scope="row"><?= escapeHtml($fieldLabel) ?></th>
                    <td><?= escapeHtml($beforeValue) ?></td>
                    <td><?= escapeHtml($afterValue) ?><?php if ($isChanged): ?> <span class="badge badge--info">changed</span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="form__actions form__actions--spaced">
        <?php foreach ($backLinks as $linkLabel => $linkUrl): ?>
            <a class="button button--ghost" href="<?= escapeHtml($linkUrl) ?>"><?= escapeHtml($linkLabel) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<?php if (isset($listPartial)): ?>
    <section class="card card--muted">
        <div class="card__header"><h2><?= escapeHtml($listTitle) ?></h2></div>
        <?php ViewService::renderPartial($listPartial, $listData); ?>
    </section>
<?php endif; ?>
