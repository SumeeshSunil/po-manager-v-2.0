/**
 * js/po_helpers.js
 *
 * Pure utility functions shared across all PO JS modules.
 */

'use strict';

/** HTML-escape a value */
export function e(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/** Format a YYYY-MM-DD (or ISO) date as DD-MM-YYYY */
export function fmtDate(d) {
    if (!d) return '—';
    if (/^\d{4}-\d{2}-\d{2}$/.test(d)) {
        const [y, m, day] = d.split('-');
        return `${day}-${m}-${y}`;
    }
    const dt = new Date(d);
    return isNaN(dt.getTime()) ? d : dt.toLocaleDateString('en-GB');
}

/** Normalise any date value to YYYY-MM-DD (for filter comparison) */
export function normalizeDate(d) {
    if (!d) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(d)) return d;
    const dt = new Date(d);
    if (isNaN(dt.getTime())) return '';
    return `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
}

/** Map po_status to a CSS class name */
export function statusClass(s) {
    const map = {
        pending:                   'status-pending',
        in_progress:               'status-in_progress',
        sent_to_schedule_delivery: 'status-sent_to_schedule_delivery',
        delivery_date_scheduled:   'status-delivery_date_scheduled',
        done:                      'status-done',
        rejected:                  'status-rejected',
    };
    return map[(s || '').toLowerCase()] || 'status-other';
}

/** Map platform name to a CSS badge class */
export function platClass(p) {
    const map = {
        instamart: 'badge-instamart',
        blinkit:   'badge-blinkit',
        zepto:     'badge-zepto',
        flipkart:  'badge-flipkart',
    };
    return map[(p || '').toLowerCase()] || 'badge-default';
}

/** Format a po_status string for human display */
export function formatStatus(s) {
    return (s || 'N/A')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());
}
