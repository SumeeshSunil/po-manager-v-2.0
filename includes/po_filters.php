<?php
/**
 * includes/po_filters.php
 *
 * Renders the filter bar (search, status, factory, date, clear button)
 * and the locked-company pill for non-admin users.
 *
 * Requires: $factoryList (array), $requiresFactoryLock (bool)
 */
?>
<div class="filters-wrap">

    <!-- Locked factory pill — shown after a non-admin user has selected their factory -->
    <div id="locked-company-wrap" style="display:none">
        <div class="locked-company-pill">
            <svg viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <span class="lcp-emoji" id="locked-company-emoji"></span>
            <span class="lcp-name"  id="locked-company-name"></span>
            <span class="lcp-lock">locked</span>
        </div>
    </div>

    <div class="search-box">
        <svg viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="search-input" placeholder="Search POs…">
    </div>

    <div class="filter-box">
        <svg viewBox="0 0 24 24">
            <path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/>
        </svg>
        <select id="status-filter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="sent_to_schedule_delivery">Sent To Schedule Delivery</option>
            <option value="delivery_date_scheduled">Delivery Date Scheduled</option>
            <option value="done">Done</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <!-- Factory filter — locked for 'user' role after factory selection -->
    <div class="filter-box" id="factory-filter-box">
        <svg viewBox="0 0 24 24">
            <path d="M3 21h18"/>
            <path d="M5 21V7l7-4 7 4v14"/>
            <path d="M9 9h.01"/><path d="M15 9h.01"/>
            <path d="M9 13h.01"/><path d="M15 13h.01"/>
        </svg>
        <select id="factory-filter">
            <option value="">All Factory</option>
            <?php foreach ($factoryList as $factory): ?>
                <option value="<?= htmlspecialchars($factory) ?>"><?= htmlspecialchars($factory) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-box">
        <svg viewBox="0 0 24 24">
            <path d="M8 2v4"/><path d="M16 2v4"/>
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <path d="M3 10h18"/>
        </svg>
        <input type="date" id="date-filter" title="Filter by release date">
    </div>

    <button type="button" class="clear-filters-btn" id="clear-filters-btn">Clear Filters</button>
</div>
