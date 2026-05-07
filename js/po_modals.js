/**
 * js/po_modals.js
 *
 * Handles three modals:
 *   1. Reschedule modal
 *   2. Rejection modal
 *   3. Rejection reason popover (singleton tooltip)
 *
 * Exposes globals: openRescheduleModal(), openRejectModal(), showRejectReason()
 */

'use strict';

// ── Rejection reason popover ───────────────────────────────────────────────

const reasonPopover = document.getElementById('reject-reason-popover');
const reasonPopText = document.getElementById('reject-reason-popover-text');
let activeBadge = null;

window.showRejectReason = function (badgeEl, reason) {
    if (activeBadge === badgeEl && reasonPopover?.classList.contains('visible')) {
        hideReasonPopover();
        return;
    }
    activeBadge = badgeEl;
    if (reasonPopText) reasonPopText.textContent = reason || 'No reason provided';
    reasonPopover?.classList.add('visible');
    positionPopover(badgeEl);
};

function positionPopover(anchor) {
    if (!reasonPopover) return;
    const rect  = anchor.getBoundingClientRect();
    const popW  = 280;
    let   left  = rect.left;
    if (left + popW > window.innerWidth - 12) left = window.innerWidth - popW - 12;
    if (left < 8) left = 8;
    reasonPopover.style.left = left + 'px';
    reasonPopover.style.top  = (rect.bottom + 10) + 'px';
    const arrowLeft = Math.min(Math.max(rect.left + rect.width / 2 - left - 6, 10), popW - 22);
    reasonPopover.style.setProperty('--arrow-left', arrowLeft + 'px');
}

function hideReasonPopover() {
    reasonPopover?.classList.remove('visible');
    activeBadge = null;
}

document.addEventListener('click', e => {
    if (!reasonPopover?.contains(e.target) && !e.target.closest('.status-rejected')) {
        hideReasonPopover();
    }
});
window.addEventListener('scroll', hideReasonPopover, true);
window.addEventListener('resize', () => { if (activeBadge) positionPopover(activeBadge); });

// ── Rejection modal ────────────────────────────────────────────────────────

const rejectOverlay  = document.getElementById('reject-modal-overlay');
const rejectForm     = document.getElementById('reject-modal-form');
const rejectPoIdIn   = document.getElementById('reject-modal-po-id');
const rejectPoNumEl  = document.getElementById('reject-modal-po-num');
const rejectTextarea = document.getElementById('reject-reason-textarea');
const rejectError    = document.getElementById('reject-modal-error');
const rejectCancel   = document.getElementById('reject-modal-cancel');

window.openRejectModal = function (poId, poNumber) {
    if (rejectPoIdIn)   rejectPoIdIn.value  = poId;
    if (rejectPoNumEl)  rejectPoNumEl.textContent = `PO #${poNumber}`;
    if (rejectTextarea) rejectTextarea.value = '';
    if (rejectError)    rejectError.style.display = 'none';
    rejectOverlay?.classList.add('active');
    setTimeout(() => rejectTextarea?.focus(), 80);
};

function closeRejectModal() {
    rejectOverlay?.classList.remove('active');
}

rejectCancel?.addEventListener('click', closeRejectModal);
rejectOverlay?.addEventListener('click', e => { if (e.target === rejectOverlay) closeRejectModal(); });
rejectForm?.addEventListener('submit', e => {
    if (!rejectTextarea?.value.trim()) {
        e.preventDefault();
        if (rejectError) rejectError.style.display = 'block';
        rejectTextarea?.focus();
    } else {
        if (rejectError) rejectError.style.display = 'none';
    }
});

// ── Reschedule modal ───────────────────────────────────────────────────────

const rescheduleOverlay  = document.getElementById('reschedule-modal-overlay');
const rescheduleForm     = document.getElementById('reschedule-modal-form');
const reschedulePoIdIn   = document.getElementById('reschedule-modal-po-id');
const reschedulePoNumEl  = document.getElementById('reschedule-modal-po-num');
const rescheduleDateIn   = document.getElementById('reschedule-date-input');
const rescheduleError    = document.getElementById('reschedule-modal-error');
const rescheduleCancel   = document.getElementById('reschedule-modal-cancel');

window.openRescheduleModal = function (poId, poNumber) {
    if (reschedulePoIdIn)  reschedulePoIdIn.value  = poId;
    if (reschedulePoNumEl) reschedulePoNumEl.textContent = `PO #${poNumber}`;
    if (rescheduleDateIn)  rescheduleDateIn.value  = '';
    if (rescheduleError)   rescheduleError.style.display = 'none';
    rescheduleOverlay?.classList.add('active');
    setTimeout(() => rescheduleDateIn?.focus(), 80);
};

function closeRescheduleModal() {
    rescheduleOverlay?.classList.remove('active');
}

rescheduleCancel?.addEventListener('click', closeRescheduleModal);
rescheduleOverlay?.addEventListener('click', e => { if (e.target === rescheduleOverlay) closeRescheduleModal(); });
rescheduleForm?.addEventListener('submit', e => {
    if (!rescheduleDateIn?.value) {
        e.preventDefault();
        if (rescheduleError) rescheduleError.style.display = 'block';
        rescheduleDateIn?.focus();
    } else {
        if (rescheduleError) rescheduleError.style.display = 'none';
    }
});

// ── ESC closes reject / reschedule modals (NOT the company modal) ──────────
document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    if (rejectOverlay?.classList.contains('active'))     closeRejectModal();
    if (rescheduleOverlay?.classList.contains('active')) closeRescheduleModal();
});
