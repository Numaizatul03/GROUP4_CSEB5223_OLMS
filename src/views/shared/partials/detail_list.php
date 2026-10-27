<?php
/**
 * Partial:  shared/partials/detail_list.php
 * Purpose:  Show a record's attributes as a label/value list.
 * Data:     array $detailRows - label => value (plain text; escaped here)
 */
?>
<dl class="detail-list">
    <?php foreach ($detailRows as $rowLabel => $rowValue): ?>
        <dt><?= escapeHtml($rowLabel) ?></dt>
        <dd><?= nl2br(escapeHtml($rowValue)) ?></dd>
    <?php endforeach; ?>
</dl>
