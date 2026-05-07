<?php
/**
 * includes/po_modals.php
 *
 * Renders:
 *   1. Rejection reason popover (singleton tooltip)
 *   2. Reschedule modal
 *   3. Rejection modal
 *   4. Company / factory selection modal (non-admin users)
 *
 * Requires: $factoryList (array), $requiresFactoryLock (bool)
 */
?>

<!-- ── Rejection reason popover (singleton) ──────────────────────────────── -->
<div id="reject-reason-popover">
    <div class="pop-label">Rejection Reason</div>
    <div class="pop-text" id="reject-reason-popover-text"></div>
</div>

<!-- ── Reschedule Modal ───────────────────────────────────────────────────── -->
<div class="reschedule-modal-overlay" id="reschedule-modal-overlay">
    <div class="reschedule-modal" role="dialog" aria-modal="true"
         aria-labelledby="reschedule-modal-title">

        <div class="reschedule-modal-header">
            <div class="reschedule-modal-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M23 4v6h-6"/>
                    <path d="M1 20v-6h6"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
            </div>
            <div>
                <div class="reschedule-modal-title" id="reschedule-modal-title">Reschedule Delivery</div>
                <div class="reschedule-modal-subtitle" id="reschedule-modal-po-num"></div>
            </div>
        </div>

        <form method="POST" action="reschedule_po.php" id="reschedule-modal-form">
            <input type="hidden" name="po_id" id="reschedule-modal-po-id">
            <label for="reschedule-date-input">
                New Delivery Date <span style="color:#3949ab">*</span>
            </label>
            <input type="date" id="reschedule-date-input" name="reschedule_date"
                   min="<?= date('Y-m-d') ?>">
            <div class="reschedule-modal-note">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                After saving, the PO status will move to
                <strong>Delivery Date Scheduled</strong>,
                enabling Mark Done or Reject actions.
            </div>
            <div class="reschedule-modal-error" id="reschedule-modal-error">
                Please select a reschedule date before confirming.
            </div>
            <div class="reschedule-modal-actions">
                <button type="button" class="reschedule-modal-cancel"
                        id="reschedule-modal-cancel">Cancel</button>
                <button type="submit" class="reschedule-modal-confirm">Save Reschedule</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Rejection Modal ────────────────────────────────────────────────────── -->
<div class="reject-modal-overlay" id="reject-modal-overlay">
    <div class="reject-modal" role="dialog" aria-modal="true"
         aria-labelledby="reject-modal-title">

        <div class="reject-modal-header">
            <div class="reject-modal-icon">
                <svg viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </div>
            <div>
                <div class="reject-modal-title" id="reject-modal-title">Reject Purchase Order</div>
                <div class="reject-modal-subtitle" id="reject-modal-po-num"></div>
            </div>
        </div>

        <form method="POST" action="mark_po_rejected.php" id="reject-modal-form">
            <input type="hidden" name="po_id" id="reject-modal-po-id">
            <label for="reject-reason-textarea">
                Reason for Rejection <span style="color:#c62828">*</span>
            </label>
            <textarea id="reject-reason-textarea" name="rejection_reason"
                      placeholder="Describe why this PO is being rejected…"
                      maxlength="1000"></textarea>
            <div class="reject-modal-error" id="reject-modal-error">
                Please enter a rejection reason before confirming.
            </div>
            <div class="reject-modal-actions">
                <button type="button" class="reject-modal-cancel"
                        id="reject-modal-cancel">Cancel</button>
                <button type="submit" class="reject-modal-confirm">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Company / Factory Selection Modal (non-admin users only) ───────────── -->
<div class="company-modal-overlay" id="company-modal-overlay">
    <div class="company-modal" role="dialog" aria-modal="true"
         aria-labelledby="company-modal-title">

        <div class="company-modal-top">
            <div class="company-modal-icon-wrap">
                <svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <div class="company-modal-title" id="company-modal-title">Select Your Factory</div>
            <div class="company-modal-sub">
                Choose your factory.<br>
                <strong>This choice is permanent</strong> — you will only see orders from this factory.
            </div>
        </div>

        <div class="company-option-grid" id="company-option-grid">
            <?php foreach ($factoryList as $factory): ?>
                <div class="company-option"
                     data-value="<?= htmlspecialchars($factory) ?>"
                     data-emoji="🏭">
                    <span class="company-option-emoji">🏭</span>
                    <?= htmlspecialchars($factory) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="company-modal-confirm"
                id="company-modal-confirm" disabled>
            Confirm &amp; Continue →
        </button>
        <div class="company-modal-error-msg" id="company-modal-error-msg">
            Please select a factory to continue.
        </div>
    </div>
</div>
