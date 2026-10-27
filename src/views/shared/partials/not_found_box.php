<?php
/**
 * Partial:  shared/partials/not_found_box.php
 * Purpose:  The "Not found" message used by every search in the system.
 * Data:     string $notFoundMessage
 */
?>
<div class="not-found" role="status">
    <div class="not-found__icon" aria-hidden="true">?</div>
    <div>
        <h2>Not found</h2>
        <p><?= escapeHtml($notFoundMessage) ?></p>
    </div>
</div>
