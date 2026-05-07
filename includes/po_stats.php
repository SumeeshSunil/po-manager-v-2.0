<?php
/**
 * includes/po_stats.php
 *
 * Renders the six summary stat cards.
 * Requires: $total, $open, $needsSched, $scheduled, $done, $rejected
 */
?>
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Total POs</div>
        <div class="stat-value" id="stat-total"><?= $total ?></div>
        <div class="stat-sub">Filtered result</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Open</div>
        <div class="stat-value" id="stat-open" style="color:#1565c0"><?= $open ?></div>
        <div class="stat-sub">Active items</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Needs Schedule</div>
        <div class="stat-value" id="stat-needs" style="color:#ef6c00"><?= $needsSched ?></div>
        <div class="stat-sub">Waiting for date</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Scheduled</div>
        <div class="stat-value" id="stat-scheduled" style="color:#8e24aa"><?= $scheduled ?></div>
        <div class="stat-sub">Delivery date set</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Done</div>
        <div class="stat-value" id="stat-done" style="color:#2e7d32"><?= $done ?></div>
        <div class="stat-sub">Completed</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Rejected</div>
        <div class="stat-value" id="stat-rejected" style="color:#c62828"><?= $rejected ?></div>
        <div class="stat-sub">Rejected orders</div>
    </div>
</div>
