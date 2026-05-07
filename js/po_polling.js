/**
 * js/po_polling.js
 *
 * Auto-refreshes PO data every 10 s using get_po_status.php,
 * diffs the result to detect new / changed rows and fires
 * push notifications for each change.
 */

'use strict';

import { pushNotif } from './po_notifications.js';
import { formatStatus } from './po_helpers.js';
import { populateFactoryFilter } from './po_render.js';
import { updateAllRows } from './po_filters.js';

let _knownRows = {};
let _lockedFactory = null;
let _secs    = 10;
let _polling = false;

export function initPolling(initialRows, lockedFactory) {
    _lockedFactory = lockedFactory;
    _knownRows     = snapshotRows(initialRows);
    setInterval(tick, 1000);
}

// ── Refresh pill helpers ───────────────────────────────────────────────────

function setPillState(state) {
    const pill    = document.getElementById('refresh-pill');
    const pillLbl = document.getElementById('refresh-label');
    if (!pill) return;

    pill.classList.remove('is-refreshing', 'did-flash');

    if (state === 'loading') {
        pill.classList.add('is-refreshing');
        if (pillLbl) pillLbl.textContent = 'Refreshing…';
    } else if (state === 'done') {
        void pill.offsetWidth;
        pill.classList.add('did-flash');
        if (pillLbl) pillLbl.textContent = 'Refresh in 10s';
    } else {
        if (pillLbl) pillLbl.textContent = `Refresh in ${_secs}s`;
    }
}

// ── Tick / fetch ───────────────────────────────────────────────────────────

function tick() {
    if (_polling) return;
    _secs--;

    if (_secs <= 0) {
        _secs = 10;
        _polling = true;
        setPillState('loading');
        doFetch();
    } else {
        setPillState('idle');
    }
}

function doFetch() {
    fetch(`get_po_status.php?_=${Date.now()}`, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (data.success) {
            const rows = data.rows ?? [];
            detectChangesAndNotify(rows);
            _knownRows = snapshotRows(rows);
            populateFactoryFilter(rows, _lockedFactory);
            updateAllRows(rows);
            setPillState('done');
        } else {
            setPillState('idle');
        }
    })
    .catch(err => {
        console.error('PO poller fetch error:', err);
        setPillState('idle');
    })
    .finally(() => {
        _polling = false;
        _secs    = 10;
    });
}

// ── Change detection ───────────────────────────────────────────────────────

function detectChangesAndNotify(rows) {
    for (const row of rows) {
        const id     = String(row.id);
        const oldRow = _knownRows[id];
        const newSt  = row.po_status || '';

        if (!oldRow) {
            pushNotif('🆕 New Purchase Order',
                `PO ${row.po_number || ''} · ${row.platform || 'N/A'}`);
        } else if ((oldRow.po_status || '') !== newSt) {
            pushNotif('📋 PO Status Changed',
                `PO ${row.po_number || ''}: ${formatStatus(oldRow.po_status)} → ${formatStatus(newSt)}`);
        }
    }
}

function snapshotRows(rows) {
    const out = {};
    for (const row of rows) {
        out[String(row.id)] = { id: String(row.id), po_status: row.po_status || '' };
    }
    return out;
}
