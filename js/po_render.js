/**
 * js/po_render.js
 *
 * Builds and renders the PO table rows and updates stat cards /
 * progress bars after any data change.
 */

'use strict';

import { e, fmtDate, statusClass, platClass, formatStatus } from './po_helpers.js';

const IS_ADMIN = window.PO_CONFIG?.isAdmin ?? false;

// ── Row HTML builder ───────────────────────────────────────────────────────

export function buildRowHtml(row, rowClass = '') {
    const st = (row.po_status || '').toLowerCase();

    const showExp        = !!row.expected_delivery_date;
    const showSch        = !!row.delivery_schedule_date;
    const showReschedule = !!row.reschedule_date;
    const showBuyerExp   = !!row.buyer_expected_date;

    const canDone       = IS_ADMIN && st === 'delivery_date_scheduled';
    const canReschedule = IS_ADMIN && ['delivery_date_scheduled', 'rejected'].includes(st);

    const calIcon = `<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`;

    const expectedHtml = showExp
        ? `<span class="schedule-pill">${calIcon}${e(fmtDate(row.expected_delivery_date))}</span>`
        : `<span style="color:#ccc;font-size:12px">—</span>`;

    const scheduleHtml = showSch
        ? `<span class="schedule-pill">${calIcon}${e(fmtDate(row.delivery_schedule_date))}</span>`
        : `<span style="color:#ccc;font-size:12px">—</span>`;

    const rescheduleHtml = showReschedule
        ? `<span class="schedule-pill" style="background:#e8eaf6;color:#283593;">
               <svg viewBox="0 0 24 24" style="stroke:#283593">${calIcon.match(/<svg[^>]*>(.*)<\/svg>/s)?.[1] ?? ''}</svg>
               ${e(fmtDate(row.reschedule_date))}</span>`
        : `<span style="color:#ccc;font-size:12px">—</span>`;

    const buyerExpHtml = showBuyerExp
        ? `<span class="schedule-pill" style="background:#e3f2fd;color:#1565c0;">
               <svg viewBox="0 0 24 24" style="stroke:#1565c0">${calIcon.match(/<svg[^>]*>(.*)<\/svg>/s)?.[1] ?? ''}</svg>
               ${e(fmtDate(row.buyer_expected_date))}</span>`
        : `<span style="color:#ccc;font-size:12px">—</span>`;

    const pdfHtml = row.pdf_file_path
        ? `<a href="${e(row.pdf_file_path)}" target="_blank" class="action-link action-pdf">
               <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
               <polyline points="14 2 14 8 20 8"/></svg>PDF</a>`
        : `<span class="no-pdf">No PDF</span>`;

    const rescheduleBtn = canReschedule
        ? `<button type="button" class="action-btn action-reschedule"
               onclick="openRescheduleModal(${e(row.id)}, '${e(row.po_number || '').replace(/'/g, "\\'")}')">
               <svg viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
               <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
               Reschedule</button>`
        : '';

    const doneRejectHtml = canDone
        ? `<form method="POST" action="mark_po_done.php"
                onsubmit="return confirm('Mark this PO as done?');" style="display:inline;">
               <input type="hidden" name="po_id" value="${e(row.id)}">
               <button type="submit" class="action-btn action-done">
                   <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M5 13l4 4L19 7"/></svg>Mark Done</button></form>
           <button type="button" class="action-btn action-reject"
               onclick="openRejectModal(${e(row.id)}, '${e(row.po_number || '').replace(/'/g, "\\'")}')">
               <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/>
               <line x1="6" y1="6" x2="18" y2="18"/></svg>Reject</button>`
        : '';

    let statusHtml;
    if (st === 'rejected') {
        const safeReason = e(row.rejection_reason || 'No reason provided').replace(/'/g, '&#039;');
        statusHtml = `<span class="status-badge status-rejected"
            onclick="showRejectReason(this, '${safeReason}')">
            <span class="dot"></span>Rejected
            <svg class="info-icon" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="8"/>
                <line x1="12" y1="12" x2="12" y2="16"/>
            </svg></span>`;
    } else {
        statusHtml = `<span class="status-badge ${statusClass(row.po_status)}">
            <span class="dot"></span>${e(formatStatus(row.po_status))}</span>`;
    }

    return `<tr class="${e(rowClass)}" data-po-id="${e(row.id)}" data-po-status="${e(row.po_status || '')}">
        <td style="color:#bbb;font-size:12px;font-family:'DM Mono',monospace">${e(row.id)}</td>
        <td><span class="po-num">${e(row.po_number || '')}</span></td>
        <td><span class="badge ${platClass(row.platform)}">${e(row.platform || '—')}</span></td>
        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
            title="${e(row.factory_name || '')}">${e(row.factory_name || '—')}</td>
        <td style="font-size:12px;color:#666">${e(fmtDate(row.release_date))}</td>
        <td style="font-size:12px;color:#666">${e(fmtDate(row.expiry_date))}</td>
        <td>${buyerExpHtml}</td>
        <td>${statusHtml}</td>
        <td>${expectedHtml}</td>
        <td>${scheduleHtml}</td>
        <td>${rescheduleHtml}</td>
        <td style="font-size:12px;color:#666">${e(row.creator_name || '—')}</td>
        <td><div class="action-group">
            <a href="po_view.php?id=${e(row.id)}" class="action-link action-view">
                <svg viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/></svg>View</a>
            ${pdfHtml}${rescheduleBtn}${doneRejectHtml}
            <a href="po_workflow_history.php?po_id=${e(row.id)}" class="action-link action-view">
                <svg viewBox="0 0 24 24">
                    <path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>History</a>
        </div></td></tr>`;
}

// ── Table renderer ─────────────────────────────────────────────────────────

export function renderTable(rows) {
    const tbody = document.getElementById('po-tbody');
    if (!rows.length) {
        tbody.innerHTML = `<tr id="empty-row"><td colspan="13">
            <div class="empty-state">
                <svg viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <div>No purchase orders found for selected filters</div>
            </div></td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map(row => buildRowHtml(row, '')).join('');
}

// ── Stat cards updater ─────────────────────────────────────────────────────

export function updateCards(rows) {
    const stats = { total: rows.length, done: 0, rejected: 0, scheduled: 0, needs_schedule: 0 };
    for (const r of rows) {
        const st = String(r.po_status || '').toLowerCase();
        if (st === 'done') stats.done++;
        if (st === 'rejected') stats.rejected++;
        if (st === 'delivery_date_scheduled') stats.scheduled++;
        if (st === 'sent_to_schedule_delivery') stats.needs_schedule++;
    }
    stats.open = stats.total - stats.done - stats.rejected;
    processStats(stats);
}

export function processStats(stats) {
    if (!stats) return;
    const pairs = {
        'stat-total':    stats.total,
        'stat-open':     stats.open,
        'stat-needs':    stats.needs_schedule,
        'stat-scheduled': stats.scheduled,
        'stat-done':     stats.done,
        'stat-rejected': stats.rejected ?? 0,
    };
    for (const [id, val] of Object.entries(pairs)) {
        const el = document.getElementById(id);
        if (!el) continue;
        const valStr = String(val ?? 0);
        if (el.textContent.trim() !== valStr) {
            el.textContent = valStr;
            el.classList.remove('stat-bump');
            void el.offsetWidth; // trigger reflow for animation restart
            el.classList.add('stat-bump');
            setTimeout(() => el.classList.remove('stat-bump'), 400);
        } else {
            el.textContent = valStr;
        }
    }

    // Progress bars
    const total        = stats.total;
    const doneRate     = total > 0 ? (stats.done / total) * 100 : 0;
    const rejectedRate = total > 0 ? ((stats.rejected ?? 0) / total) * 100 : 0;
    const openCount    = Math.max(total - stats.done - (stats.rejected ?? 0), 0);
    const openRate     = total > 0 ? (openCount / total) * 100 : 0;

    const rRate = Math.round(doneRate * 10) / 10;
    const rRej  = Math.round(rejectedRate * 10) / 10;
    const rOpen = Math.round(openRate * 10) / 10;

    const setEl = (id, val) => {
        const el = document.getElementById(id);
        if (el) el[id.endsWith('fill') ? 'style' : 'textContent'] = id.endsWith('fill') ? `width:${val}%` : val;
    };

    const rf = document.getElementById('po-done-rate-fill');
    const rt = document.getElementById('po-done-rate-text');
    const rm = document.getElementById('po-done-rate-meta');
    const ej = document.getElementById('po-rejected-rate-fill');
    const et = document.getElementById('po-rejected-rate-text');
    const of = document.getElementById('po-open-rate-fill');
    const ot = document.getElementById('po-open-rate-text');

    if (rf) rf.style.width = rRate + '%';
    if (rt) rt.textContent = rRate + '%';
    if (ej) ej.style.width = rRej + '%';
    if (et) et.textContent = rRej + '%';
    if (of) of.style.width = rOpen + '%';
    if (ot) ot.textContent = rOpen + '%';
    if (rm) rm.textContent = `${stats.done} done · ${stats.rejected ?? 0} rejected · ${total} total`;
}

// ── Re-populate factory dropdown ───────────────────────────────────────────

export function populateFactoryFilter(rows, lockedFactory) {
    const factoryFilter = document.getElementById('factory-filter');
    if (!factoryFilter || lockedFactory) return; // skip if locked

    const currentValue = factoryFilter.value;
    const factories = {};
    for (const row of rows) {
        const f = String(row.factory_name || '').trim();
        if (f) factories[f] = true;
    }
    const sorted = Object.keys(factories).sort((a, b) => a.localeCompare(b));

    factoryFilter.innerHTML = '<option value="">All Factory</option>'
        + sorted.map(f => `<option value="${e(f)}">${e(f)}</option>`).join('');

    if (currentValue && factories[currentValue]) {
        factoryFilter.value = currentValue;
    }
}

// ── Date alert bar helpers ─────────────────────────────────────────────────

export function calculateDateAlertStats(rows, fieldName) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const stats = { total: 0, safe: 0, near: 0, reached: 0,
                    safe_pct: 0, near_pct: 0, reached_pct: 0 };

    for (const row of rows) {
        const raw = row[fieldName];
        if (!raw || raw === '0000-00-00') continue;
        if (fieldName === 'delivery_schedule_date' &&
            String(row.po_status || '').toLowerCase() === 'done') continue;

        const dt = new Date(raw);
        if (isNaN(dt.getTime())) continue;
        dt.setHours(0, 0, 0, 0);

        const diffDays = Math.floor((dt - today) / 86400000);
        stats.total++;
        if      (diffDays < 0)  stats.reached++;
        else if (diffDays <= 3) stats.near++;
        else                    stats.safe++;
    }

    if (stats.total > 0) {
        stats.safe_pct    = (stats.safe    / stats.total) * 100;
        stats.near_pct    = (stats.near    / stats.total) * 100;
        stats.reached_pct = (stats.reached / stats.total) * 100;
    }
    return stats;
}

export function updateDateAlertBar(prefix, stats, labelText) {
    const safeEl    = document.getElementById(`${prefix}-safe`);
    const nearEl    = document.getElementById(`${prefix}-near`);
    const reachedEl = document.getElementById(`${prefix}-reached`);
    const metaEl    = document.getElementById(`${prefix}-meta`);
    const legendEl  = document.getElementById(`${prefix}-legend`);

    if (safeEl)    safeEl.style.width    = stats.safe_pct.toFixed(1) + '%';
    if (nearEl)    nearEl.style.width    = stats.near_pct.toFixed(1) + '%';
    if (reachedEl) reachedEl.style.width = stats.reached_pct.toFixed(1) + '%';
    if (metaEl)    metaEl.textContent    = `${stats.total} items with ${labelText}`;
    if (legendEl) {
        legendEl.innerHTML =
            `<span class="legend-item"><span class="legend-dot legend-safe"></span> Safe: ${stats.safe}</span>` +
            `<span class="legend-item"><span class="legend-dot legend-near"></span> Near: ${stats.near}</span>` +
            `<span class="legend-item"><span class="legend-dot legend-reached"></span> Reached: ${stats.reached}</span>`;
    }
}
