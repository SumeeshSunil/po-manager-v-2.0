/**
 * js/po_filters.js
 *
 * Manages the filter bar inputs and drives re-renders
 * whenever a filter changes.
 */

'use strict';

import { normalizeDate } from './po_helpers.js';
import {
    renderTable,
    updateCards,
    populateFactoryFilter,
    calculateDateAlertStats,
    updateDateAlertBar,
} from './po_render.js';

let _allRows      = [];
let _lockedFactory = null;

/** Call once on page load with initial data */
export function initFilters(allRows, lockedFactory) {
    _allRows       = allRows;
    _lockedFactory = lockedFactory;

    const searchInput  = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');
    const factoryFilter = document.getElementById('factory-filter');
    const dateFilter   = document.getElementById('date-filter');
    const clearBtn     = document.getElementById('clear-filters-btn');

    searchInput?.addEventListener('input',  applyFiltersAndRender);
    statusFilter?.addEventListener('change', applyFiltersAndRender);
    factoryFilter?.addEventListener('change', applyFiltersAndRender);
    dateFilter?.addEventListener('change',  applyFiltersAndRender);

    clearBtn?.addEventListener('click', () => {
        if (searchInput)  searchInput.value  = '';
        if (statusFilter) statusFilter.value = '';
        if (dateFilter)   dateFilter.value   = '';
        // Do NOT reset factory filter when locked
        if (factoryFilter && !_lockedFactory) factoryFilter.value = '';
        applyFiltersAndRender();
    });
}

/** Called by the poller when fresh rows arrive */
export function updateAllRows(rows) {
    _allRows = rows;
    applyFiltersAndRender();
}

/** Re-filter and re-render everything */
export function applyFiltersAndRender() {
    const filters      = getActiveFilters();
    const filteredRows = _allRows.filter(row => rowMatchesFilters(row, filters));

    renderTable(filteredRows);
    updateCards(filteredRows);

    updateDateAlertBar('expiry',   calculateDateAlertStats(filteredRows, 'expiry_date'),            'expiry date');
    updateDateAlertBar('expected', calculateDateAlertStats(filteredRows, 'expected_delivery_date'), 'expected delivery date');
    updateDateAlertBar('schedule', calculateDateAlertStats(filteredRows, 'delivery_schedule_date'), 'schedule date');
}

// ── Internals ──────────────────────────────────────────────────────────────

function getActiveFilters() {
    return {
        search:  (document.getElementById('search-input')?.value  || '').trim().toLowerCase(),
        status:  (document.getElementById('status-filter')?.value || '').trim().toLowerCase(),
        factory: (document.getElementById('factory-filter')?.value || '').trim(),
        date:    (document.getElementById('date-filter')?.value   || '').trim(),
    };
}

function rowMatchesFilters(row, filters) {
    // Factory lock is always enforced for non-admin users
    if (_lockedFactory && String(row.factory_name || '') !== _lockedFactory) return false;

    const searchText = [
        row.id, row.po_number, row.platform, row.factory_name,
        row.po_status, row.creator_name, row.release_date,
        row.expiry_date, row.expected_delivery_date,
        row.delivery_schedule_date, row.reschedule_date,
    ].join(' ').toLowerCase();

    if (filters.search  && !searchText.includes(filters.search)) return false;
    if (filters.status  && String(row.po_status    || '').toLowerCase() !== filters.status)  return false;
    if (filters.factory && String(row.factory_name || '')                !== filters.factory) return false;
    if (filters.date    && normalizeDate(row.release_date)               !== filters.date)    return false;

    return true;
}
