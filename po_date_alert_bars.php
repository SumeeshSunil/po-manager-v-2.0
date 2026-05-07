<?php
if (!isset($rows) || !is_array($rows)) {
    $rows = [];
}

function calcDateStats(array $rows, string $field): array
{
    $today = strtotime(date('Y-m-d'));
    $total = 0;
    $safe = 0;
    $near = 0;
    $reached = 0;

    foreach ($rows as $r) {
        $value = trim($r[$field] ?? '');
        if ($value === '' || $value === '0000-00-00') continue;

        $status = strtolower(trim($r['po_status'] ?? ''));

        // Do not count DONE items in schedule date bar
        if ($field === 'delivery_schedule_date' && $status === 'done') {
            continue;
        }

        $ts = strtotime($value);
        if (!$ts) continue;

        $total++;
        $diffDays = floor(($ts - $today) / 86400);

        if ($diffDays < 0) {
            $reached++;
        } elseif ($diffDays <= 3) {
            $near++;
        } else {
            $safe++;
        }
    }

    return [
        'total' => $total,
        'safe' => $safe,
        'near' => $near,
        'reached' => $reached,
        'safe_pct' => $total > 0 ? round(($safe / $total) * 100, 1) : 0,
        'near_pct' => $total > 0 ? round(($near / $total) * 100, 1) : 0,
        'reached_pct' => $total > 0 ? round(($reached / $total) * 100, 1) : 0,
    ];
}
$expiryStats   = calcDateStats($rows, 'expiry_date');
$expectedStats = calcDateStats($rows, 'expected_delivery_date');
$scheduleStats = calcDateStats($rows, 'delivery_schedule_date');
?>

<style>
    .date-alert-card {
        background: #fff;
        border: 1px solid #e8eaed;
        border-radius: 16px;
        padding: 18px 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    }

    .date-alert-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }

    .date-alert-title {
        font-size: 13px;
        font-weight: 600;
        color: #1a1a2e;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .date-alert-sub {
        font-size: 12px;
        color: #888;
        margin-bottom: 16px;
    }

    .date-alert-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
    }

    .date-alert-box {
        border: 1px solid #eef1f4;
        border-radius: 14px;
        padding: 14px;
        background: #fcfcfd;
    }

    .date-alert-box h4 {
        font-size: 14px;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 8px;
    }

    .date-alert-meta {
        font-size: 12px;
        color: #777;
        margin-bottom: 10px;
    }

    .date-alert-track {
        width: 100%;
        height: 14px;
        border-radius: 999px;
        overflow: hidden;
        background: #edf1f5;
        display: flex;
    }

    .date-seg-safe {
        background: #43a047;
        height: 100%;
        transition: width 0.4s ease;
    }

    .date-seg-near {
        background: #fb8c00;
        height: 100%;
        transition: width 0.4s ease;
    }

    .date-seg-reached {
        background: #e53935;
        height: 100%;
        transition: width 0.4s ease;
    }

    .date-alert-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 14px;
        margin-top: 10px;
        font-size: 12px;
        color: #666;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .legend-safe {
        background: #43a047;
    }

    .legend-near {
        background: #fb8c00;
    }

    .legend-reached {
        background: #e53935;
    }

    .date-alert-note {
        margin-top: 14px;
        font-size: 12px;
        color: #777;
    }
</style>

<div class="date-alert-card">
    <div class="date-alert-header">
        <div class="date-alert-title">Date Alert Status</div>
    </div>
    <div class="date-alert-sub">
        Green = safe, Orange = within 3 days, Red = action date reached/passed
    </div>

    <div class="date-alert-grid">

        <div class="date-alert-box">
            <h4>Expiry Date</h4>
            <div class="date-alert-meta" id="expiry-meta">
                <?= $expiryStats['total'] ?> items with expiry date
            </div>
            <div class="date-alert-track">
                <div class="date-seg-safe" id="expiry-safe" style="width: <?= $expiryStats['safe_pct'] ?>%"></div>
                <div class="date-seg-near" id="expiry-near" style="width: <?= $expiryStats['near_pct'] ?>%"></div>
                <div class="date-seg-reached" id="expiry-reached" style="width: <?= $expiryStats['reached_pct'] ?>%"></div>
            </div>
            <div class="date-alert-legend" id="expiry-legend">
                <span class="legend-item"><span class="legend-dot legend-safe"></span> Safe: <?= $expiryStats['safe'] ?></span>
                <span class="legend-item"><span class="legend-dot legend-near"></span> Near: <?= $expiryStats['near'] ?></span>
                <span class="legend-item"><span class="legend-dot legend-reached"></span> Reached: <?= $expiryStats['reached'] ?></span>
            </div>
        </div>

        <div class="date-alert-box">
            <h4>Expected Delivery Date</h4>
            <div class="date-alert-meta" id="expected-meta">
                <?= $expectedStats['total'] ?> items with expected delivery date
            </div>
            <div class="date-alert-track">
                <div class="date-seg-safe" id="expected-safe" style="width: <?= $expectedStats['safe_pct'] ?>%"></div>
                <div class="date-seg-near" id="expected-near" style="width: <?= $expectedStats['near_pct'] ?>%"></div>
                <div class="date-seg-reached" id="expected-reached" style="width: <?= $expectedStats['reached_pct'] ?>%"></div>
            </div>
            <div class="date-alert-legend" id="expected-legend">
                <span class="legend-item"><span class="legend-dot legend-safe"></span> Safe: <?= $expectedStats['safe'] ?></span>
                <span class="legend-item"><span class="legend-dot legend-near"></span> Near: <?= $expectedStats['near'] ?></span>
                <span class="legend-item"><span class="legend-dot legend-reached"></span> Reached: <?= $expectedStats['reached'] ?></span>
            </div>
        </div>

        <div class="date-alert-box">
            <h4>Schedule Date</h4>
            <div class="date-alert-meta" id="schedule-meta">
                <?= $scheduleStats['total'] ?> items with schedule date
            </div>
            <div class="date-alert-track">
                <div class="date-seg-safe" id="schedule-safe" style="width: <?= $scheduleStats['safe_pct'] ?>%"></div>
                <div class="date-seg-near" id="schedule-near" style="width: <?= $scheduleStats['near_pct'] ?>%"></div>
                <div class="date-seg-reached" id="schedule-reached" style="width: <?= $scheduleStats['reached_pct'] ?>%"></div>
            </div>
            <div class="date-alert-legend" id="schedule-legend">
                <span class="legend-item"><span class="legend-dot legend-safe"></span> Safe: <?= $scheduleStats['safe'] ?></span>
                <span class="legend-item"><span class="legend-dot legend-near"></span> Near: <?= $scheduleStats['near'] ?></span>
                <span class="legend-item"><span class="legend-dot legend-reached"></span> Reached: <?= $scheduleStats['reached'] ?></span>
            </div>
        </div>

    </div>

    <div class="date-alert-note">
        “Reached” means the date is today or already passed, so action is needed.
    </div>
</div>