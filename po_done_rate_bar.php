<?php
if (!isset($total))    $total    = 0;
if (!isset($done))     $done     = 0;
if (!isset($rows))     $rows     = [];

$rejected  = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'rejected'));
$open      = $total - $done - $rejected;
$open      = max($open, 0);

$doneRate     = ($total > 0) ? round(($done     / $total) * 100, 1) : 0;
$rejectedRate = ($total > 0) ? round(($rejected / $total) * 100, 1) : 0;
$openRate     = ($total > 0) ? round(($open     / $total) * 100, 1) : 0;
?>

<style>
    .po-rate-card {
        background: #fff;
        border: 1px solid #e8eaed;
        border-radius: 16px;
        padding: 18px 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    }

    .po-rate-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .po-rate-title {
        font-size: 13px;
        font-weight: 600;
        color: #1a1a2e;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .po-rate-badges {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .po-rate-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        font-family: 'DM Mono', monospace;
    }

    .po-rate-badge-done {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .po-rate-badge-rejected {
        background: #fce4ec;
        color: #c62828;
    }

    .po-rate-badge-open {
        background: #e3f2fd;
        color: #1565c0;
    }

    .po-rate-badge .badge-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .po-rate-badge-done .badge-dot {
        background: #43a047;
    }

    .po-rate-badge-rejected .badge-dot {
        background: #e53935;
    }

    .po-rate-badge-open .badge-dot {
        background: #1e88e5;
    }

    .po-rate-meta {
        font-size: 12px;
        color: #888;
        margin-bottom: 12px;
    }

    .po-rate-track {
        width: 100%;
        height: 14px;
        background: #edf1f5;
        border-radius: 999px;
        overflow: hidden;
        display: flex;
    }

    .po-rate-fill {
        height: 100%;
        background: linear-gradient(90deg, #43a047 0%, #66bb6a 100%);
        transition: width 0.4s ease;
        width: <?= $doneRate ?>%;
        border-radius: <?= ($doneRate >= 100) ? '999px' : '999px 0 0 999px' ?>;
        flex-shrink: 0;
    }

    .po-rate-fill-rejected {
        height: 100%;
        background: linear-gradient(90deg, #e53935 0%, #ef9a9a 100%);
        transition: width 0.4s ease;
        width: <?= $rejectedRate ?>%;
        flex-shrink: 0;
    }

    .po-rate-fill-open {
        height: 100%;
        background: linear-gradient(90deg, #1e88e5 0%, #64b5f6 100%);
        transition: width 0.4s ease;
        width: <?= $openRate ?>%;
        flex-shrink: 0;
    }

    .po-rate-legend {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .po-rate-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #555;
        font-weight: 500;
    }

    .po-rate-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .legend-dot-done {
        background: #43a047;
    }

    .legend-dot-rejected {
        background: #e53935;
    }

    .legend-dot-open {
        background: #1e88e5;
    }
</style>

<div class="po-rate-card">
    <div class="po-rate-top">
        <div class="po-rate-title">PO Completion Rate</div>
        <div class="po-rate-badges">
            <span class="po-rate-badge po-rate-badge-done">
                <span class="badge-dot"></span>
                <span id="po-done-rate-text"><?= $doneRate ?>%</span> Done
            </span>
            <span class="po-rate-badge po-rate-badge-rejected">
                <span class="badge-dot"></span>
                <span id="po-rejected-rate-text"><?= $rejectedRate ?>%</span> Rejected
            </span>
            <span class="po-rate-badge po-rate-badge-open">
                <span class="badge-dot"></span>
                <span id="po-open-rate-text"><?= $openRate ?>%</span> Open
            </span>
        </div>
    </div>

    <div class="po-rate-meta">
        <span id="po-done-rate-meta"><?= $done ?> done · <?= $rejected ?> rejected · <?= $total ?> total</span>
    </div>

    <div class="po-rate-track">
        <div class="po-rate-fill" id="po-done-rate-fill"></div>
        <div class="po-rate-fill-rejected" id="po-rejected-rate-fill"></div>
        <div class="po-rate-fill-open" id="po-open-rate-fill"></div>
    </div>

    <div class="po-rate-legend">
        <div class="po-rate-legend-item">
            <span class="po-rate-legend-dot legend-dot-done"></span>
            Done: <strong><?= $done ?></strong>
        </div>
        <div class="po-rate-legend-item">
            <span class="po-rate-legend-dot legend-dot-rejected"></span>
            Rejected: <strong><?= $rejected ?></strong>
        </div>
        <div class="po-rate-legend-item">
            <span class="po-rate-legend-dot legend-dot-open"></span>
            Open: <strong><?= $open ?></strong>
        </div>
    </div>
</div>