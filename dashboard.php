<?php
include 'partials/header.php';
checkLogin();

$sql = "SELECT po.*, u.name AS creator_name
        FROM purchase_orders po
        LEFT JOIN users u ON po.created_by = u.id
        ORDER BY po.id DESC";
$result = $conn->query($sql);

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$total      = count($rows);
$done       = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'done'));
$scheduled  = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'delivery_date_scheduled'));
$needsSched = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'sent_to_schedule_delivery'));
$rejected   = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'rejected'));
$open       = $total - $done - $rejected;

$factoryList = [];
foreach ($rows as $r) {
    $factory = trim($r['factory_name'] ?? '');
    if ($factory !== '') $factoryList[$factory] = $factory;
}
ksort($factoryList);

$factorySelectionList = [];
foreach ($rows as $r) {
    $factory = trim($r['factory_name'] ?? '');
    if ($factory !== '') $factorySelectionList[$factory] = $factory;
}
ksort($factorySelectionList);

// Current user info
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

$isAdmin      = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isSuper      = isset($_SESSION['role']) && $_SESSION['role'] === 'super';
$isViewer     = isset($_SESSION['role']) && $_SESSION['role'] === 'viewer';
$isUser       = isset($_SESSION['role']) && $_SESSION['role'] === 'user';
$isDispatch   = isset($_SESSION['role']) && $_SESSION['role'] === 'dispatch_supervisor';

$requiresFactoryLock = $isUser || $isDispatch;

$sessionCompany = $_SESSION['user_company'] ?? '';
?>

<?php
$sessionCompany = '';
if (!$isAdmin) {
    $stmt = $conn->prepare("SELECT company FROM users WHERE id = ?");
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $stmt->bind_result($dbCompany);
    $stmt->fetch();
    $stmt->close();

    if ($dbCompany) {
        $sessionCompany = $dbCompany;
        $_SESSION['user_company'] = $dbCompany;
    } else {
        $sessionCompany = $_SESSION['user_company'] ?? '';
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    .dash-page {
        min-height: 100vh;
        background: #f0f2f5;
        padding: 32px 24px 60px;
        font-family: 'DM Sans', sans-serif;
    }

    .dash-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .dash-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .dash-header-icon {
        width: 44px;
        height: 44px;
        background: #1a1a2e;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .dash-header-icon svg {
        width: 22px;
        height: 22px;
        stroke: #fff;
        fill: none;
        stroke-width: 1.8;
    }

    .dash-header h2 {
        font-size: 22px;
        font-weight: 600;
        color: #1a1a2e;
        letter-spacing: -0.3px;
    }

    .dash-header p {
        font-size: 13px;
        color: #888;
        margin-top: 2px;
    }

    .btn-new {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 20px;
        background: #1a1a2e;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        text-decoration: none;
        transition: background .15s;
    }

    .btn-new:hover {
        background: #2d2d4e;
    }

    .btn-new svg {
        width: 15px;
        height: 15px;
        stroke: #fff;
        fill: none;
        stroke-width: 2.5;
    }

    /* Stats */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #e8eaed;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
    }

    .stat-card .stat-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #999;
        margin-bottom: 6px;
    }

    .stat-card .stat-value {
        font-size: 26px;
        font-weight: 700;
        color: #1a1a2e;
        font-family: 'DM Mono', monospace;
        line-height: 1;
        display: inline-block;
    }

    .stat-card .stat-sub {
        font-size: 12px;
        color: #aaa;
        margin-top: 4px;
    }

    /* Table card */
    .table-card {
        background: #fff;
        border: 1px solid #e8eaed;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
    }

    .table-card-header {
        padding: 14px 20px;
        background: #fafafa;
        border-bottom: 1px solid #e8eaed;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .table-card-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-card-header-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .section-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #1a1a2e;
        flex-shrink: 0;
    }

    .table-card-header .hdr-title {
        font-size: 13px;
        font-weight: 600;
        color: #1a1a2e;
        text-transform: uppercase;
        letter-spacing: .6px;
    }

    /* Refresh pill */
    #refresh-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 5px 13px;
        background: #f0f2f5;
        border: 1.5px solid #e0e3e8;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        font-family: 'DM Sans', sans-serif;
        white-space: nowrap;
        transition: background .25s, color .25s, border-color .25s;
        user-select: none;
    }

    #refresh-pill.is-refreshing {
        background: #e3f2fd;
        color: #1565c0;
        border-color: #90caf9;
    }

    #refresh-pill.did-flash {
        animation: pillFlash .9s ease forwards;
    }

    #refresh-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #43a047;
        flex-shrink: 0;
        animation: dotPulse 1.6s ease-in-out infinite;
    }

    #refresh-pill.is-refreshing #refresh-dot {
        background: #1e88e5;
        animation: dotPulse .5s ease-in-out infinite;
    }

    /* Notification banner */
    #notif-banner {
        display: none;
        align-items: center;
        gap: 12px;
        background: #fff8e1;
        border: 1.5px solid #ffe082;
        border-radius: 12px;
        padding: 12px 18px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #5d4037;
        font-weight: 500;
    }

    #notif-banner svg {
        width: 18px;
        height: 18px;
        stroke: #f57f17;
        fill: none;
        stroke-width: 2;
        flex-shrink: 0;
    }

    #notif-banner .nb-actions {
        display: flex;
        gap: 8px;
        margin-left: auto;
        flex-shrink: 0;
    }

    .nb-btn {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        border: none;
    }

    .nb-btn-allow {
        background: #1a1a2e;
        color: #fff;
    }

    .nb-btn-allow:hover {
        background: #2d2d4e;
    }

    .nb-btn-dismiss {
        background: #ede9e0;
        color: #888;
    }

    .nb-btn-dismiss:hover {
        background: #e0dbd0;
    }

    /* Filters */
    .filters-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .search-box,
    .filter-box {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f0f2f5;
        border: 1.5px solid #e0e3e8;
        border-radius: 8px;
        padding: 6px 12px;
        min-height: 40px;
    }

    .filter-box.is-locked {
        background: #f5f5f5;
        border-color: #d0d0d0;
        opacity: .7;
        cursor: not-allowed;
    }

    .filter-box.is-locked select,
    .filter-box.is-locked input {
        pointer-events: none;
        cursor: not-allowed;
        color: #999;
    }

    .search-box svg,
    .filter-box svg {
        width: 14px;
        height: 14px;
        stroke: #aaa;
        fill: none;
        stroke-width: 2;
        flex-shrink: 0;
    }

    .search-box input,
    .filter-box input,
    .filter-box select {
        border: none;
        background: transparent;
        outline: none;
        font-size: 13px;
        font-family: 'DM Sans', sans-serif;
        color: #1a1a2e;
    }

    .search-box input {
        width: 180px;
    }

    .search-box input::placeholder {
        color: #bbb;
    }

    .filter-box select {
        min-width: 150px;
        cursor: pointer;
    }

    .filter-box input[type="date"] {
        min-width: 145px;
    }

    .clear-filters-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 8px;
        border: none;
        background: #1a1a2e;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'DM Sans', sans-serif;
    }

    .clear-filters-btn:hover {
        background: #2d2d4e;
    }

    /* Table */
    .po-table {
        width: 100%;
        border-collapse: collapse;
    }

    .po-table th {
        padding: 11px 16px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #888;
        background: #fafafa;
        border-bottom: 1px solid #e8eaed;
        text-align: left;
        white-space: nowrap;
    }

    .po-table td {
        padding: 13px 16px;
        font-size: 13px;
        color: #333;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
    }

    .po-table tr:last-child td {
        border-bottom: none;
    }

    .po-table tbody tr {
        transition: background .12s;
    }

    .po-table tbody tr:hover {
        background: #fafbfc;
    }

    .po-num {
        font-family: 'DM Mono', monospace;
        font-size: 12px;
        font-weight: 500;
        color: #1a1a2e;
        background: #f0f2f5;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-block;
    }

    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .3px;
    }

    .badge-instamart {
        background: #fff3e0;
        color: #e65100;
    }

    .badge-blinkit {
        background: #f9fbe7;
        color: #827717;
    }

    .badge-zepto {
        background: #fce4ec;
        color: #880e4f;
    }

    .badge-flipkart {
        background: #e3f2fd;
        color: #0d47a1;
    }

    .badge-default {
        background: #f0f2f5;
        color: #555;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        transition: background .3s, color .3s;
    }

    .status-badge .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .status-pending {
        background: #fff8e1;
        color: #f57f17;
    }

    .status-pending .dot {
        background: #ffb300;
    }

    .status-in_progress {
        background: #e3f2fd;
        color: #1565c0;
    }

    .status-in_progress .dot {
        background: #1e88e5;
    }

    .status-sent_to_schedule_delivery {
        background: #fff3e0;
        color: #ef6c00;
    }

    .status-sent_to_schedule_delivery .dot {
        background: #fb8c00;
    }

    .status-delivery_date_scheduled {
        background: #ede7f6;
        color: #6a1b9a;
    }

    .status-delivery_date_scheduled .dot {
        background: #8e24aa;
    }

    .status-done {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-done .dot {
        background: #43a047;
    }

    .status-rejected {
        background: #fce4ec;
        color: #b71c1c;
        cursor: pointer;
        user-select: none;
    }

    .status-rejected .dot {
        background: #e53935;
    }

    .status-rejected .info-icon {
        width: 12px;
        height: 12px;
        stroke: #b71c1c;
        fill: none;
        stroke-width: 2.2;
        margin-left: 3px;
        flex-shrink: 0;
    }

    .status-rescheduled {
        background: #e8eaf6;
        color: #283593;
    }

    .status-rescheduled .dot {
        background: #3949ab;
    }

    .status-other {
        background: #f0f2f5;
        color: #666;
    }

    .status-other .dot {
        background: #bbb;
    }

    /* Dispatch-specific badge colours */
    .handover-success {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        background: #e8f5e9;
        color: #1b5e20;
    }

    .handover-success .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #43a047;
    }

    .handover-rejected {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        background: #fce4ec;
        color: #b71c1c;
        cursor: pointer;
    }

    .handover-rejected .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #e53935;
    }

    .handover-pending {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: #fff3e0;
        color: #e65100;
    }

    .handover-pending .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #fb8c00;
    }

    /* Rejection reason popover */
    #reject-reason-popover {
        display: none;
        position: fixed;
        z-index: 9998;
        max-width: 280px;
        background: #1a1a2e;
        color: #fff;
        border-radius: 10px;
        padding: 12px 14px;
        box-shadow: 0 6px 24px rgba(0, 0, 0, .22);
        font-family: 'DM Sans', sans-serif;
        font-size: 12.5px;
        line-height: 1.5;
        pointer-events: none;
    }

    #reject-reason-popover.visible {
        display: block;
        pointer-events: auto;
    }

    #reject-reason-popover .pop-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: #e57373;
        margin-bottom: 5px;
    }

    #reject-reason-popover .pop-text {
        color: #f0f0f0;
        word-break: break-word;
    }

    #reject-reason-popover::after {
        content: '';
        position: absolute;
        top: -6px;
        left: var(--arrow-left, 18px);
        width: 12px;
        height: 12px;
        background: #1a1a2e;
        transform: rotate(45deg);
        border-radius: 2px;
    }

    .schedule-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #e8f5e9;
        color: #2e7d32;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        font-family: 'DM Mono', monospace;
    }

    .schedule-pill svg {
        width: 11px;
        height: 11px;
        stroke: #2e7d32;
        fill: none;
        stroke-width: 2.5;
    }

    .needs-schedule {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fff3e0;
        color: #e65100;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .needs-schedule svg {
        width: 11px;
        height: 11px;
        stroke: #e65100;
        fill: none;
        stroke-width: 2.5;
    }

    .action-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .action-link,
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-family: 'DM Sans', sans-serif;
        transition: background .15s;
    }

    .action-link svg,
    .action-btn svg {
        width: 13px;
        height: 13px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
    }

    .action-view {
        color: #1a1a2e;
        background: #f0f2f5;
    }

    .action-view:hover {
        background: #e4e7ec;
    }

    .action-pdf {
        color: #c62828;
        background: #ffebee;
    }

    .action-pdf:hover {
        background: #ffcdd2;
    }

    .action-done {
        color: #fff;
        background: #2e7d32;
    }

    .action-done:hover {
        background: #1b5e20;
    }

    .action-reject {
        color: #fff;
        background: #c62828;
    }

    .action-reject:hover {
        background: #b71c1c;
    }

    .action-reschedule {
        color: #fff;
        background: #3949ab;
    }

    .action-reschedule:hover {
        background: #283593;
    }

    .action-dispatch {
        color: #fff;
        background: #00796b;
    }

    .action-dispatch:hover {
        background: #004d40;
    }

    .action-arrival {
        color: #fff;
        background: #6a1b9a;
    }

    .action-arrival:hover {
        background: #4a148c;
    }

    .action-handover-success {
        color: #fff;
        background: #2e7d32;
    }

    .action-handover-success:hover {
        background: #1b5e20;
    }

    .action-handover-reject {
        color: #fff;
        background: #c62828;
    }

    .action-handover-reject:hover {
        background: #b71c1c;
    }

    /* ── Modal base ─────────────────────────────────────────────────────── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-box {
        background: #fff;
        border-radius: 16px;
        padding: 28px 28px 24px;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
        font-family: 'DM Sans', sans-serif;
        max-height: 92vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .modal-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .modal-icon svg {
        width: 20px;
        height: 20px;
        fill: none;
        stroke-width: 2;
    }

    .modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #1a1a2e;
    }

    .modal-subtitle {
        font-size: 12px;
        color: #888;
        margin-top: 2px;
    }

    .modal-field {
        margin-bottom: 14px;
    }

    .modal-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .modal-field input[type="text"],
    .modal-field input[type="datetime-local"],
    .modal-field input[type="date"],
    .modal-field input[type="file"],
    .modal-field textarea,
    .modal-field select {
        width: 100%;
        border: 1.5px solid #e0e3e8;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 14px;
        font-family: 'DM Sans', sans-serif;
        color: #1a1a2e;
        outline: none;
        transition: border-color .15s;
        box-sizing: border-box;
    }

    .modal-field input:focus,
    .modal-field textarea:focus,
    .modal-field select:focus {
        border-color: #1a1a2e;
    }

    .modal-field textarea {
        min-height: 90px;
        resize: vertical;
    }

    .modal-error {
        display: none;
        font-size: 12px;
        color: #c62828;
        margin-top: 4px;
        font-weight: 500;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .modal-cancel {
        padding: 9px 18px;
        border-radius: 9px;
        border: 1.5px solid #e0e3e8;
        background: #f0f2f5;
        color: #555;
        font-size: 13px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
    }

    .modal-cancel:hover {
        background: #e4e7ec;
    }

    .modal-confirm {
        padding: 9px 20px;
        border-radius: 9px;
        border: none;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        transition: background .15s;
    }

    .modal-note {
        margin-top: 10px;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .modal-note svg {
        width: 14px;
        height: 14px;
        fill: none;
        stroke-width: 2;
        flex-shrink: 0;
        margin-top: 1px;
    }

    /* Reschedule modal */
    .reschedule-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .reschedule-modal-overlay.active {
        display: flex;
    }

    .reschedule-modal {
        background: #fff;
        border-radius: 16px;
        padding: 28px 28px 24px;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
        font-family: 'DM Sans', sans-serif;
    }

    .reschedule-modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .reschedule-modal-icon {
        width: 40px;
        height: 40px;
        background: #e8eaf6;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .reschedule-modal-icon svg {
        width: 20px;
        height: 20px;
        stroke: #3949ab;
        fill: none;
        stroke-width: 2;
    }

    .reschedule-modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #1a1a2e;
    }

    .reschedule-modal-subtitle {
        font-size: 12px;
        color: #888;
        margin-top: 2px;
    }

    .reschedule-modal label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        margin-bottom: 7px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .reschedule-modal input[type="date"] {
        width: 100%;
        border: 1.5px solid #e0e3e8;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 14px;
        font-family: 'DM Sans', sans-serif;
        color: #1a1a2e;
        outline: none;
        transition: border-color .15s;
        box-sizing: border-box;
    }

    .reschedule-modal input[type="date"]:focus {
        border-color: #3949ab;
    }

    .reschedule-modal-note {
        margin-top: 10px;
        padding: 10px 14px;
        background: #e8eaf6;
        border-radius: 8px;
        font-size: 12px;
        color: #3949ab;
        font-weight: 500;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .reschedule-modal-note svg {
        width: 14px;
        height: 14px;
        stroke: #3949ab;
        fill: none;
        stroke-width: 2;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .reschedule-modal-error {
        display: none;
        font-size: 12px;
        color: #c62828;
        margin-top: 5px;
        font-weight: 500;
    }

    .reschedule-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .reschedule-modal-cancel {
        padding: 9px 18px;
        border-radius: 9px;
        border: 1.5px solid #e0e3e8;
        background: #f0f2f5;
        color: #555;
        font-size: 13px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
    }

    .reschedule-modal-cancel:hover {
        background: #e4e7ec;
    }

    .reschedule-modal-confirm {
        padding: 9px 20px;
        border-radius: 9px;
        border: none;
        background: #3949ab;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        transition: background .15s;
    }

    .reschedule-modal-confirm:hover {
        background: #283593;
    }

    /* Reject modal */
    .reject-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .reject-modal-overlay.active {
        display: flex;
    }

    .reject-modal {
        background: #fff;
        border-radius: 16px;
        padding: 28px 28px 24px;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
        font-family: 'DM Sans', sans-serif;
    }

    .reject-modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .reject-modal-icon {
        width: 40px;
        height: 40px;
        background: #fce4ec;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .reject-modal-icon svg {
        width: 20px;
        height: 20px;
        stroke: #c62828;
        fill: none;
        stroke-width: 2;
    }

    .reject-modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #1a1a2e;
    }

    .reject-modal-subtitle {
        font-size: 12px;
        color: #888;
        margin-top: 2px;
    }

    .reject-modal label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        margin-bottom: 7px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .reject-modal textarea {
        width: 100%;
        min-height: 100px;
        border: 1.5px solid #e0e3e8;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 13px;
        font-family: 'DM Sans', sans-serif;
        color: #1a1a2e;
        resize: vertical;
        outline: none;
        transition: border-color .15s;
        box-sizing: border-box;
    }

    .reject-modal textarea:focus {
        border-color: #c62828;
    }

    .reject-modal-error {
        display: none;
        font-size: 12px;
        color: #c62828;
        margin-top: 5px;
        font-weight: 500;
    }

    .reject-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .reject-modal-cancel {
        padding: 9px 18px;
        border-radius: 9px;
        border: 1.5px solid #e0e3e8;
        background: #f0f2f5;
        color: #555;
        font-size: 13px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
    }

    .reject-modal-cancel:hover {
        background: #e4e7ec;
    }

    .reject-modal-confirm {
        padding: 9px 20px;
        border-radius: 9px;
        border: none;
        background: #c62828;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        transition: background .15s;
    }

    .reject-modal-confirm:hover {
        background: #b71c1c;
    }

    .no-pdf {
        font-size: 12px;
        color: #bbb;
    }

    .empty-state {
        text-align: center;
        padding: 60px 24px;
        color: #aaa;
        font-size: 14px;
    }

    .empty-state svg {
        width: 40px;
        height: 40px;
        stroke: #ddd;
        fill: none;
        stroke-width: 1.5;
        margin-bottom: 12px;
    }

    @keyframes dotPulse {

        0%,
        100% {
            opacity: 1;
            transform: scale(1)
        }

        50% {
            opacity: .35;
            transform: scale(.65)
        }
    }

    @keyframes pillFlash {
        0% {
            background: #e8f5e9;
            color: #2e7d32;
            border-color: #a5d6a7
        }

        100% {
            background: #f0f2f5;
            color: #555;
            border-color: #e0e3e8
        }
    }

    @keyframes rowHighlight {
        0% {
            background: #fffde7
        }

        80% {
            background: #fffde7
        }

        100% {
            background: transparent
        }
    }

    @keyframes rowNew {
        0% {
            background: #e8f5e9;
            opacity: 0;
            transform: translateY(-6px)
        }

        20% {
            opacity: 1;
            transform: translateY(0);
            background: #e8f5e9
        }

        80% {
            background: #e8f5e9
        }

        100% {
            background: transparent
        }
    }

    @keyframes statBump {
        0% {
            transform: scale(1.3)
        }

        100% {
            transform: scale(1)
        }
    }

    .row-changed {
        animation: rowHighlight 2.5s ease forwards;
    }

    .row-new {
        animation: rowNew 2.5s ease forwards;
    }

    .stat-bump {
        animation: statBump .35s ease both;
    }

    @media (max-width:768px) {

        .table-card-header-right,
        .filters-wrap {
            width: 100%;
        }

        .search-box,
        .filter-box {
            width: 100%;
        }

        .search-box input,
        .filter-box select,
        .filter-box input[type="date"] {
            width: 100%;
            min-width: 0;
        }

        .clear-filters-btn {
            width: 100%;
            justify-content: center;
        }
    }

    .progress-toggle-wrap {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 16px;
    }

    .progress-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border: none;
        border-radius: 10px;
        background: #1a1a2e;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        transition: background .2s;
    }

    .progress-toggle-btn:hover {
        background: #2d2d4e;
    }

    .table-scroll-drag {
        cursor: grab;
        user-select: none;
    }

    .table-scroll-drag.dragging {
        cursor: grabbing;
    }

    /* Company selection modal */
    .company-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 15, 30, .78);
        z-index: 99999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }

    .company-modal-overlay.active {
        display: flex;
    }

    .company-modal {
        background: #fff;
        border-radius: 22px;
        padding: 40px 32px 30px;
        width: 100%;
        max-width: 460px;
        box-shadow: 0 20px 70px rgba(0, 0, 0, .28);
        font-family: 'DM Sans', sans-serif;
        animation: companyModalIn .3s cubic-bezier(.34, 1.56, .64, 1) both;
    }

    @keyframes companyModalIn {
        from {
            opacity: 0;
            transform: scale(.88) translateY(20px)
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0)
        }
    }

    .company-modal-top {
        text-align: center;
        margin-bottom: 28px;
    }

    .company-modal-icon-wrap {
        width: 60px;
        height: 60px;
        background: #1a1a2e;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }

    .company-modal-icon-wrap svg {
        width: 28px;
        height: 28px;
        stroke: #fff;
        fill: none;
        stroke-width: 1.8;
    }

    .company-modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 7px;
    }

    .company-modal-sub {
        font-size: 13px;
        color: #888;
        line-height: 1.6;
    }

    .company-modal-sub strong {
        color: #555;
    }

    .company-option-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 10px;
        margin-bottom: 22px;
    }

    .company-option {
        border: 2px solid #e0e3e8;
        border-radius: 14px;
        padding: 16px 10px 12px;
        text-align: center;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        background: #fafbfc;
        transition: border-color .15s, background .15s, color .15s, transform .12s;
        user-select: none;
    }

    .company-option:hover {
        border-color: #1a1a2e;
        background: #f4f5f8;
        color: #1a1a2e;
        transform: translateY(-2px);
    }

    .company-option.selected {
        border-color: #1a1a2e;
        background: #1a1a2e;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(26, 26, 46, .22);
    }

    .company-option-emoji {
        font-size: 26px;
        line-height: 1;
        display: block;
        margin-bottom: 8px;
    }

    .company-modal-confirm {
        width: 100%;
        padding: 13px;
        border-radius: 12px;
        border: none;
        background: #1a1a2e;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        transition: background .15s, opacity .15s;
        letter-spacing: .2px;
    }

    .company-modal-confirm:hover:not(:disabled) {
        background: #2d2d4e;
    }

    .company-modal-confirm:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .company-modal-error-msg {
        display: none;
        font-size: 12px;
        color: #c62828;
        text-align: center;
        margin-top: 10px;
        font-weight: 500;
    }

    .locked-company-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #1a1a2e;
        color: #fff;
        border-radius: 10px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        white-space: nowrap;
        user-select: none;
        min-height: 40px;
    }

    .locked-company-pill .lcp-emoji {
        font-size: 16px;
        line-height: 1;
    }

    .locked-company-pill .lcp-name {
        color: #fff;
    }

    .locked-company-pill .lcp-lock {
        font-size: 10px;
        color: #8899bb;
        background: rgba(255, 255, 255, .08);
        border-radius: 5px;
        padding: 2px 6px;
        margin-left: 2px;
        letter-spacing: .3px;
    }

    .locked-company-pill svg {
        width: 13px;
        height: 13px;
        stroke: #8899bb;
        fill: none;
        stroke-width: 2.2;
    }

    /* Toast notification */
    #toast-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .toast {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .15);
        animation: toastIn .3s ease;
        min-width: 260px;
        max-width: 360px;
    }

    .toast-success {
        background: #1b5e20;
        color: #fff;
    }

    .toast-error {
        background: #b71c1c;
        color: #fff;
    }

    @keyframes toastIn {
        from {
            opacity: 0;
            transform: translateX(40px)
        }

        to {
            opacity: 1;
            transform: translateX(0)
        }
    }

    /* Dispatch info chips in table */
    .disp-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #e0f2f1;
        color: #004d40;
        border-radius: 6px;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 600;
        font-family: 'DM Mono', monospace;
        white-space: nowrap;
    }

    .disp-chip svg {
        width: 10px;
        height: 10px;
        stroke: #004d40;
        fill: none;
        stroke-width: 2;
    }

    .arr-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ede7f6;
        color: #4a148c;
        border-radius: 6px;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 600;
        font-family: 'DM Mono', monospace;
        white-space: nowrap;
    }

    .arr-chip svg {
        width: 10px;
        height: 10px;
        stroke: #4a148c;
        fill: none;
        stroke-width: 2;
    }
</style>

<div class="dash-page">

    <div id="notif-banner">
        <svg viewBox="0 0 24 24">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>
        <span>Enable browser notifications to get instant alerts when PO status changes.</span>
        <div class="nb-actions">
            <button class="nb-btn nb-btn-allow" id="nb-allow">Enable Notifications</button>
            <button class="nb-btn nb-btn-dismiss" id="nb-dismiss">Dismiss</button>
        </div>
    </div>

    <div class="dash-header">
        <div class="dash-header-left">
            <div class="dash-header-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                </svg>
            </div>
            <div>
                <h2>Purchase Orders</h2>
                <p>Manage and track all incoming POs</p>
            </div>
        </div>
        <?php if (!$isDispatch && !$isViewer): ?>
            <a href="create_po.php" class="btn-new">
                <svg viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                New PO
            </a>
        <?php endif; ?>
    </div>

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

    <div class="progress-toggle-wrap">
        <button type="button" class="progress-toggle-btn" id="toggle-progress-bars">Hide Progress Bars</button>
    </div>

    <div id="progress-bars-section">
        <?php include 'po_done_rate_bar.php'; ?>
        <?php include 'po_date_alert_bars.php'; ?>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-header-left">
                <div class="section-dot"></div>
                <span class="hdr-title">All Purchase Orders</span>
            </div>
            <div class="table-card-header-right">
                <div id="refresh-pill">
                    <span id="refresh-dot"></span>
                    <span id="refresh-label">Refresh in 10s</span>
                </div>
                <div class="filters-wrap">
                    <div id="locked-company-wrap" style="display:none">
                        <div class="locked-company-pill">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <span class="lcp-emoji" id="locked-company-emoji"></span>
                            <span class="lcp-name" id="locked-company-name"></span>
                            <span class="lcp-lock">locked</span>
                        </div>
                    </div>
                    <div class="search-box">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input type="text" id="search-input" placeholder="Search POs…">
                    </div>
                    <div class="filter-box">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 6h16" />
                            <path d="M7 12h10" />
                            <path d="M10 18h4" />
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
                    <div class="filter-box" id="factory-filter-box">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 21h18" />
                            <path d="M5 21V7l7-4 7 4v14" />
                            <path d="M9 9h.01" />
                            <path d="M15 9h.01" />
                            <path d="M9 13h.01" />
                            <path d="M15 13h.01" />
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
                            <path d="M8 2v4" />
                            <path d="M16 2v4" />
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M3 10h18" />
                        </svg>
                        <input type="date" id="date-filter" title="Filter by release date">
                    </div>
                    <button type="button" class="clear-filters-btn" id="clear-filters-btn">Clear Filters</button>
                </div>
            </div>
        </div>

        <div style="overflow-x:auto" class="table-scroll-drag" id="table-scroll-drag">
            <table class="po-table" id="po-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>PO Number</th>
                        <th>Platform</th>
                        <th>Factory</th>
                        <th>Release Date</th>
                        <th>Expiry Date</th>
                        <th>Buyer Expected</th>
                        <th>Status</th>
                        <th>Expected Delivery</th>
                        <th>Schedule Date</th>
                        <th>Reschedule Date</th>
                        <th>Dispatch Info</th>
                        <th>Arrival Info</th>
                        <th>Handover</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="po-tbody">
                    <?php if (empty($rows)): ?>
                        <tr id="empty-row">
                            <td colspan="16">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                    </svg>
                                    <div>No purchase orders yet</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row):
                            $plt    = strtolower($row['platform'] ?? '');
                            $pClass = match ($plt) {
                                'instamart' => 'badge-instamart',
                                'blinkit'   => 'badge-blinkit',
                                'zepto'     => 'badge-zepto',
                                'flipkart'  => 'badge-flipkart',
                                default     => 'badge-default',
                            };
                            $st     = strtolower($row['po_status'] ?? '');
                            $sClass = match ($st) {
                                'pending'                   => 'status-pending',
                                'in_progress'               => 'status-in_progress',
                                'sent_to_schedule_delivery' => 'status-sent_to_schedule_delivery',
                                'delivery_date_scheduled'   => 'status-delivery_date_scheduled',
                                'done'                      => 'status-done',
                                'rejected'                  => 'status-rejected',
                                default                     => 'status-other',
                            };

                            // Role-based action permissions
                            $isAdminRow    = $isAdmin || $isSuper;
                            $isDispatchRow = $isDispatch;

                            // Admin: Mark Done only after dispatch supervisor reports success
                            $handoverStatus = strtolower($row['handover_status'] ?? 'pending');
                            $canDone        = $isAdminRow && $st === 'delivery_date_scheduled' && $handoverStatus === 'success';
                            $canAdminReject = false; // Removed — dispatch supervisor handles rejection now
                            $canReschedule  = $isAdminRow && in_array($st, ['delivery_date_scheduled', 'rejected']);

                            // Dispatch supervisor actions — each step only visible once per cycle
                            // reschedule_po.php clears all fields, so filled = already done this cycle
                            $dispatchFilled     = !empty($row['dispatch_vehicle_number']);
                            $arrivalFilled      = !empty($row['arrival_time']);
                            $canDispatch        = $isDispatchRow && $st === 'delivery_date_scheduled' && !$dispatchFilled;
                            $canArrival         = $isDispatchRow && $st === 'delivery_date_scheduled' && $dispatchFilled && !$arrivalFilled;
                            $canHandover        = $isDispatchRow && $st === 'delivery_date_scheduled' && $arrivalFilled && $handoverStatus === 'pending';

                            $showExp        = !empty($row['expected_delivery_date']);
                            $showSch        = !empty($row['delivery_schedule_date']);
                            $showReschedule = !empty($row['reschedule_date']);
                            $showBuyerExp   = !empty($row['buyer_expected_date']);
                        ?>
                            <tr data-po-id="<?= (int)$row['id'] ?>" data-po-status="<?= htmlspecialchars($row['po_status'] ?? '') ?>">
                                <td style="color:#bbb;font-size:12px;font-family:'DM Mono',monospace"><?= $row['id'] ?></td>
                                <td><span class="po-num"><?= htmlspecialchars($row['po_number']) ?></span></td>
                                <td><span class="badge <?= $pClass ?>"><?= htmlspecialchars($row['platform'] ?? '—') ?></span></td>
                                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($row['factory_name'] ?? '') ?>"><?= htmlspecialchars($row['factory_name'] ?? '—') ?></td>
                                <td style="font-size:12px;color:#666"><?= !empty($row['release_date']) ? date('d-m-Y', strtotime($row['release_date'])) : '—' ?></td>
                                <td style="font-size:12px;color:#666"><?= !empty($row['expiry_date']) ? date('d-m-Y', strtotime($row['expiry_date'])) : '—' ?></td>
                                <td>
                                    <?php if ($showBuyerExp): ?>
                                        <span class="schedule-pill" style="background:#e3f2fd;color:#1565c0;">
                                            <svg viewBox="0 0 24 24" style="stroke:#1565c0">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <?= date('d-m-Y', strtotime($row['buyer_expected_date'])) ?>
                                        </span>
                                    <?php else: ?><span style="color:#ccc;font-size:12px">—</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($st === 'rejected'): ?>
                                        <span class="status-badge status-rejected" onclick="showRejectReason(this, <?= json_encode($row['rejection_reason'] ?? '') ?>)">
                                            <span class="dot"></span>Rejected
                                            <svg class="info-icon" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" />
                                                <line x1="12" y1="8" x2="12" y2="8" />
                                                <line x1="12" y1="12" x2="12" y2="16" />
                                            </svg>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge <?= $sClass ?>">
                                            <span class="dot"></span>
                                            <?= ucfirst(str_replace('_', ' ', $row['po_status'] ?? 'N/A')) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php if ($showExp): ?>
                                        <span class="schedule-pill"><svg viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg><?= date('d-m-Y', strtotime($row['expected_delivery_date'])) ?></span>
                                    <?php else: ?><span style="color:#ccc;font-size:12px">—</span><?php endif; ?>
                                </td>
                                <td><?php if ($showSch): ?>
                                        <span class="schedule-pill"><svg viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg><?= date('d-m-Y', strtotime($row['delivery_schedule_date'])) ?></span>
                                    <?php else: ?><span style="color:#ccc;font-size:12px">—</span><?php endif; ?>
                                </td>
                                <td><?php if ($showReschedule): ?>
                                        <span class="schedule-pill" style="background:#e8eaf6;color:#283593;"><svg viewBox="0 0 24 24" style="stroke:#283593">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg><?= date('d-m-Y', strtotime($row['reschedule_date'])) ?></span>
                                    <?php else: ?><span style="color:#ccc;font-size:12px">—</span><?php endif; ?>
                                </td>

                                <!-- Dispatch Info Column -->
                                <td>
                                    <?php if ($dispatchFilled): ?>
                                        <div style="display:flex;flex-direction:column;gap:4px">
                                            <span class="disp-chip">
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z" />
                                                    <circle cx="5.5" cy="18.5" r="2.5" />
                                                    <circle cx="18.5" cy="18.5" r="2.5" />
                                                </svg>
                                                <?= htmlspecialchars($row['dispatch_vehicle_number']) ?>
                                            </span>
                                            <?php if (!empty($row['dispatch_temperature'])): ?>
                                                <span class="disp-chip">🌡️ <?= htmlspecialchars($row['dispatch_temperature']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($row['dispatch_time'])): ?>
                                                <span class="disp-chip"><?= date('d-m-Y H:i', strtotime($row['dispatch_time'])) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($row['dispatch_temp_photo'])): ?>
                                                <a href="<?= htmlspecialchars($row['dispatch_temp_photo']) ?>" target="_blank" style="font-size:10px;color:#00796b;font-weight:600">📷 Temp Photo</a>
                                            <?php endif; ?>
                                            <?php if (!empty($row['dispatch_bill_copy'])): ?>
                                                <a href="<?= htmlspecialchars($row['dispatch_bill_copy']) ?>" target="_blank" style="font-size:10px;color:#c62828;font-weight:600">📄 Bill Copy</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#ccc;font-size:12px">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Arrival Info Column -->
                                <td>
                                    <?php if ($arrivalFilled): ?>
                                        <div style="display:flex;flex-direction:column;gap:4px">
                                            <span class="arr-chip">🕐 <?= date('d-m-Y H:i', strtotime($row['arrival_time'])) ?></span>
                                            <?php if (!empty($row['arrival_temperature'])): ?>
                                                <span class="arr-chip">🌡️ <?= htmlspecialchars($row['arrival_temperature']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($row['arrival_temp_photo'])): ?>
                                                <a href="<?= htmlspecialchars($row['arrival_temp_photo']) ?>" target="_blank" style="font-size:10px;color:#6a1b9a;font-weight:600">📷 Arrival Photo</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#ccc;font-size:12px">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Handover Status Column -->
                                <td>
                                    <?php if ($handoverStatus === 'success'): ?>
                                        <span class="handover-success"><span class="dot"></span>Success</span>
                                    <?php elseif ($handoverStatus === 'rejected'): ?>
                                        <span class="handover-rejected" onclick="showHandoverRejectReason(this, <?= json_encode($row['handover_rejection_reason'] ?? '') ?>)">
                                            <span class="dot"></span>Rejected ℹ
                                        </span>
                                    <?php elseif ($st === 'delivery_date_scheduled'): ?>
                                        <span class="handover-pending"><span class="dot"></span>Pending</span>
                                    <?php else: ?>
                                        <span style="color:#ccc;font-size:12px">—</span>
                                    <?php endif; ?>
                                </td>

                                <td style="font-size:12px;color:#666"><?= htmlspecialchars($row['creator_name'] ?? '—') ?></td>

                                <td>
                                    <div class="action-group">
                                        <a href="po_view.php?id=<?= $row['id'] ?>" class="action-link action-view">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>View
                                        </a>
                                        <?php if (!empty($row['pdf_file_path'])): ?>
                                            <a href="<?= htmlspecialchars($row['pdf_file_path']) ?>" target="_blank" class="action-link action-pdf">
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                    <polyline points="14 2 14 8 20 8" />
                                                </svg>PDF
                                            </a>
                                        <?php else: ?>
                                            <span class="no-pdf">No PDF</span>
                                        <?php endif; ?>

                                        <?php if ($canReschedule): ?>
                                            <button type="button" class="action-btn action-reschedule"
                                                onclick="openRescheduleModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['po_number']), ENT_QUOTES) ?>')">
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M23 4v6h-6" />
                                                    <path d="M1 20v-6h6" />
                                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                                                </svg>Reschedule
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($canDone): ?>
                                            <form method="POST" action="mark_po_done.php" onsubmit="return confirm('Mark this PO as done?');" style="display:inline;">
                                                <input type="hidden" name="po_id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" class="action-btn action-done">
                                                    <svg viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>Mark Done
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Dispatch Supervisor Actions — shown only once per cycle -->
                                        <?php if ($canDispatch): ?>
                                            <button type="button" class="action-btn action-dispatch"
                                                onclick="openDispatchModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['po_number']), ENT_QUOTES) ?>')">
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z" />
                                                    <circle cx="5.5" cy="18.5" r="2.5" />
                                                    <circle cx="18.5" cy="18.5" r="2.5" />
                                                </svg>
                                                Add Dispatch
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($canArrival): ?>
                                            <button type="button" class="action-btn action-arrival"
                                                onclick="openArrivalModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['po_number']), ENT_QUOTES) ?>')">
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                                    <polyline points="9 22 9 12 15 12 15 22" />
                                                </svg>
                                                Add Arrival
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($canHandover): ?>
                                            <button type="button" class="action-btn action-handover-success"
                                                onclick="openHandoverModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['po_number']), ENT_QUOTES) ?>', 'success')">
                                                <svg viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>Success
                                            </button>
                                            <button type="button" class="action-btn action-handover-reject"
                                                onclick="openHandoverModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['po_number']), ENT_QUOTES) ?>', 'reject')">
                                                <svg viewBox="0 0 24 24">
                                                    <line x1="18" y1="6" x2="6" y2="18" />
                                                    <line x1="6" y1="6" x2="18" y2="18" />
                                                </svg>Reject
                                            </button>
                                        <?php endif; ?>

                                        <a href="po_workflow_history.php?po_id=<?= $row['id'] ?>" class="action-link action-view">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M12 8v4l3 3" />
                                                <circle cx="12" cy="12" r="9" />
                                            </svg>History
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- Rejection reason popover -->
<div id="reject-reason-popover">
    <div class="pop-label">Rejection Reason</div>
    <div class="pop-text" id="reject-reason-popover-text"></div>
</div>

<!-- ── Reschedule Modal ── -->
<div class="reschedule-modal-overlay" id="reschedule-modal-overlay">
    <div class="reschedule-modal" role="dialog" aria-modal="true">
        <div class="reschedule-modal-header">
            <div class="reschedule-modal-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M23 4v6h-6" />
                    <path d="M1 20v-6h6" />
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                </svg>
            </div>
            <div>
                <div class="reschedule-modal-title">Reschedule Delivery</div>
                <div class="reschedule-modal-subtitle" id="reschedule-modal-po-num"></div>
            </div>
        </div>
        <form method="POST" action="reschedule_po.php" id="reschedule-modal-form">
            <input type="hidden" name="po_id" id="reschedule-modal-po-id">
            <label for="reschedule-date-input">New Delivery Date <span style="color:#3949ab">*</span></label>
            <input type="date" id="reschedule-date-input" name="reschedule_date" min="<?= date('Y-m-d') ?>">
            <div class="reschedule-modal-note">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                After saving, the PO status will move to <strong>Delivery Date Scheduled</strong>.
            </div>
            <div class="reschedule-modal-error" id="reschedule-modal-error">Please select a reschedule date.</div>
            <div class="reschedule-modal-actions">
                <button type="button" class="reschedule-modal-cancel" id="reschedule-modal-cancel">Cancel</button>
                <button type="submit" class="reschedule-modal-confirm">Save Reschedule</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Reject Modal (kept for backward compat but no longer shown in table) ── -->
<div class="reject-modal-overlay" id="reject-modal-overlay">
    <div class="reject-modal" role="dialog" aria-modal="true">
        <div class="reject-modal-header">
            <div class="reject-modal-icon">
                <svg viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </div>
            <div>
                <div class="reject-modal-title">Reject Purchase Order</div>
                <div class="reject-modal-subtitle" id="reject-modal-po-num"></div>
            </div>
        </div>
        <form method="POST" action="mark_po_rejected.php" id="reject-modal-form">
            <input type="hidden" name="po_id" id="reject-modal-po-id">
            <label for="reject-reason-textarea">Reason for Rejection <span style="color:#c62828">*</span></label>
            <textarea id="reject-reason-textarea" name="rejection_reason" placeholder="Describe why this PO is being rejected…" maxlength="1000"></textarea>
            <div class="reject-modal-error" id="reject-modal-error">Please enter a rejection reason.</div>
            <div class="reject-modal-actions">
                <button type="button" class="reject-modal-cancel" id="reject-modal-cancel">Cancel</button>
                <button type="submit" class="reject-modal-confirm">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Dispatch Details Modal ── -->
<div class="modal-overlay" id="dispatch-modal-overlay">
    <div class="modal-box" role="dialog" aria-modal="true">
        <div class="modal-header">
            <div class="modal-icon" style="background:#e0f2f1">
                <svg viewBox="0 0 24 24" style="stroke:#00796b">
                    <path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z" />
                    <circle cx="5.5" cy="18.5" r="2.5" />
                    <circle cx="18.5" cy="18.5" r="2.5" />
                </svg>
            </div>
            <div>
                <div class="modal-title">Dispatch Details</div>
                <div class="modal-subtitle" id="dispatch-modal-po-num"></div>
            </div>
        </div>
        <div class="modal-field">
            <label>Vehicle Number <span style="color:#c62828">*</span></label>
            <input type="text" id="dispatch-vehicle" placeholder="e.g. KL 07 AB 1234" maxlength="100">
        </div>
        <div class="modal-field">
            <label>Dispatch Date &amp; Time <span style="color:#c62828">*</span></label>
            <input type="datetime-local" id="dispatch-time">
        </div>
        <div class="modal-field">
            <label>Temperature at Dispatch <span style="color:#c62828">*</span></label>
            <input type="text" id="dispatch-temp" placeholder="e.g. 4°C" maxlength="50">
        </div>
        <div class="modal-field">
            <label>Temperature Photo <small style="color:#aaa;font-weight:400;text-transform:none">(JPG/PNG, max 10MB)</small></label>
            <input type="file" id="dispatch-temp-photo" accept="image/*,.pdf">
        </div>
        <div class="modal-field">
            <label>Bill Copy <small style="color:#aaa;font-weight:400;text-transform:none">(JPG/PNG/PDF, max 10MB)</small></label>
            <input type="file" id="dispatch-bill" accept="image/*,.pdf">
        </div>
        <div class="modal-error" id="dispatch-modal-error"></div>
        <div class="modal-actions">
            <button type="button" class="modal-cancel" id="dispatch-modal-cancel">Cancel</button>
            <button type="button" class="modal-confirm" id="dispatch-modal-confirm" style="background:#00796b" onclick="submitDispatch()">Save Dispatch</button>
        </div>
    </div>
</div>

<!-- ── Arrival Details Modal ── -->
<div class="modal-overlay" id="arrival-modal-overlay">
    <div class="modal-box" role="dialog" aria-modal="true">
        <div class="modal-header">
            <div class="modal-icon" style="background:#ede7f6">
                <svg viewBox="0 0 24 24" style="stroke:#6a1b9a">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
            </div>
            <div>
                <div class="modal-title">Arrival Details</div>
                <div class="modal-subtitle" id="arrival-modal-po-num"></div>
            </div>
        </div>
        <div class="modal-field">
            <label>Arrival Date &amp; Time <span style="color:#c62828">*</span></label>
            <input type="datetime-local" id="arrival-time">
        </div>
        <div class="modal-field">
            <label>Temperature at Arrival <span style="color:#c62828">*</span></label>
            <input type="text" id="arrival-temp" placeholder="e.g. 5°C" maxlength="50">
        </div>
        <div class="modal-field">
            <label>Arrival Temperature Photo <small style="color:#aaa;font-weight:400;text-transform:none">(JPG/PNG, max 10MB)</small></label>
            <input type="file" id="arrival-temp-photo" accept="image/*">
        </div>
        <div class="modal-error" id="arrival-modal-error"></div>
        <div class="modal-actions">
            <button type="button" class="modal-cancel" id="arrival-modal-cancel">Cancel</button>
            <button type="button" class="modal-confirm" style="background:#6a1b9a" onclick="submitArrival()">Save Arrival</button>
        </div>
    </div>
</div>

<!-- ── Handover Modal (Success / Reject) ── -->
<div class="modal-overlay" id="handover-modal-overlay">
    <div class="modal-box" role="dialog" aria-modal="true" style="max-width:420px">
        <div class="modal-header">
            <div class="modal-icon" id="handover-modal-icon"></div>
            <div>
                <div class="modal-title" id="handover-modal-title"></div>
                <div class="modal-subtitle" id="handover-modal-po-num"></div>
            </div>
        </div>
        <div id="handover-reject-field" class="modal-field" style="display:none">
            <label>Rejection Reason <span style="color:#c62828">*</span></label>
            <textarea id="handover-reject-reason" placeholder="Describe what went wrong at delivery…" maxlength="1000"></textarea>
        </div>
        <div id="handover-success-note" class="modal-note" style="background:#e8f5e9;color:#1b5e20;display:none">
            <svg viewBox="0 0 24 24" style="stroke:#1b5e20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            Marking as <strong>Success</strong> will notify the admin to finalize this PO as Done.
        </div>
        <div class="modal-error" id="handover-modal-error"></div>
        <div class="modal-actions">
            <button type="button" class="modal-cancel" id="handover-modal-cancel">Cancel</button>
            <button type="button" class="modal-confirm" id="handover-modal-confirm" onclick="submitHandover()">Confirm</button>
        </div>
    </div>
</div>

<!-- ── Company / Factory Selection Modal ── -->
<div class="company-modal-overlay" id="company-modal-overlay">
    <div class="company-modal" role="dialog" aria-modal="true">
        <div class="company-modal-top">
            <div class="company-modal-icon-wrap">
                <svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
            </div>
            <div class="company-modal-title">Select Your Factory</div>
            <div class="company-modal-sub">
                Choose your factory.<br>
                <strong>This choice is permanent</strong> — you will only see orders from this factory.
            </div>
        </div>
        <div class="company-option-grid" id="company-option-grid">
            <?php foreach ($factorySelectionList as $factory): ?>
                <div class="company-option" data-value="<?= htmlspecialchars($factory) ?>" data-emoji="🏭">
                    <span class="company-option-emoji">🏭</span>
                    <?= htmlspecialchars($factory) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="company-modal-confirm" id="company-modal-confirm" disabled>Confirm &amp; Continue →</button>
        <div class="company-modal-error-msg" id="company-modal-error-msg">Please select a factory to continue.</div>
    </div>
</div>

<script>
    var progressSection = document.getElementById('progress-bars-section');
    var toggleProgressBtn = document.getElementById('toggle-progress-bars');

    (function() {
        'use strict';

        var allRows = <?= json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var knownRows = {};

        var IS_ADMIN = <?= json_encode($isAdmin || $isSuper) ?>;
        var IS_DISPATCH = <?= json_encode($isDispatch) ?>;
        var REQUIRES_FACTORY_LOCK = <?= json_encode($requiresFactoryLock) ?>;
        var CURRENT_UID = <?= json_encode($currentUserId) ?>;
        var LS_COMPANY_KEY = 'po_locked_factory_uid_' + CURRENT_UID;
        var lockedFactory = null;

        var searchInput = document.getElementById('search-input');
        var statusFilter = document.getElementById('status-filter');
        var factoryFilter = document.getElementById('factory-filter');
        var factoryBox = document.getElementById('factory-filter-box');
        var dateFilter = document.getElementById('date-filter');
        var clearBtn = document.getElementById('clear-filters-btn');
        var pill = document.getElementById('refresh-pill');
        var pillLbl = document.getElementById('refresh-label');
        var secs = 10,
            polling = false;

        /* ── Notifications ── */
        function canNotify() {
            return ('Notification' in window) && Notification.permission === 'granted';
        }

        function pushNotif(title, body) {
            if (!canNotify()) return;
            try {
                new Notification(title, {
                    body: body,
                    icon: '/favicon.ico'
                });
            } catch (_) {}
        }

        var banner = document.getElementById('notif-banner');
        var nbAllow = document.getElementById('nb-allow');
        var nbDismis = document.getElementById('nb-dismiss');

        function checkBanner() {
            if (!('Notification' in window)) return;
            if (Notification.permission === 'default' && !sessionStorage.getItem('nb-gone'))
                banner.style.display = 'flex';
        }
        if (nbAllow) nbAllow.addEventListener('click', function() {
            Notification.requestPermission().then(function(p) {
                banner.style.display = 'none';
                if (p === 'granted') pushNotif('✅ Notifications enabled', 'You\'ll get alerts for PO status changes.');
            });
        });
        if (nbDismis) nbDismis.addEventListener('click', function() {
            banner.style.display = 'none';
            sessionStorage.setItem('nb-gone', '1');
        });
        checkBanner();

        var justCreated = sessionStorage.getItem('po_created');
        if (justCreated) {
            sessionStorage.removeItem('po_created');
            setTimeout(function() {
                pushNotif('✅ PO Created', 'PO ' + justCreated + ' saved.');
            }, 600);
        }

        /* ── Polling ── */
        function snapshotRows(rows) {
            var out = {};
            rows.forEach(function(r) {
                out[String(r.id)] = {
                    id: String(r.id),
                    po_status: r.po_status || '',
                    dispatch_vehicle_number: r.dispatch_vehicle_number || '',
                    arrival_time: r.arrival_time || '',
                    handover_status: r.handover_status || '',
                    reschedule_date: r.reschedule_date || ''
                };
            });
            return out;
        }
        knownRows = snapshotRows(allRows);

        function tick() {
            if (polling) return;
            secs--;
            if (secs <= 0) {
                secs = 10;
                polling = true;
                setPillState('loading');
                doFetch();
            } else {
                pillLbl.textContent = 'Refresh in ' + secs + 's';
            }
        }

        function setPillState(state) {
            pill.classList.remove('is-refreshing', 'did-flash');
            if (state === 'loading') {
                pill.classList.add('is-refreshing');
                pillLbl.textContent = 'Refreshing…';
            } else if (state === 'done') {
                void pill.offsetWidth;
                pill.classList.add('did-flash');
                pillLbl.textContent = 'Refresh in 10s';
            } else {
                pillLbl.textContent = 'Refresh in ' + secs + 's';
            }
        }
        setInterval(tick, 1000);

        function doFetch() {
            fetch('get_po_status.php?_=' + Date.now(), {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(function(text) {
                    var data = JSON.parse(text);
                    if (data.success) {
                        detectChangesAndNotify(data.rows || []);
                        allRows = data.rows || [];
                        knownRows = snapshotRows(allRows);
                        populateFactoryFilter(allRows);
                        applyFiltersAndRender();
                        setPillState('done');
                    } else {
                        setPillState('idle');
                    }
                })
                .catch(function(err) {
                    console.error('Poller error:', err);
                    setPillState('idle');
                })
                .finally(function() {
                    polling = false;
                    secs = 10;
                });
        }

        function detectChangesAndNotify(rows) {
            rows.forEach(function(row) {
                var id = String(row.id);
                var old = knownRows[id];
                var pn = row.po_number || '';

                if (!old) {
                    pushNotif('🆕 New PO', 'PO ' + pn + ' · ' + (row.platform || 'N/A'));
                    return;
                }

                // Status change
                var newSt = row.po_status || '';
                if ((old.po_status || '') !== newSt)
                    pushNotif('📋 PO Status Changed',
                        'PO ' + pn + ': ' + formatStatus(old.po_status) + ' → ' + formatStatus(newSt));

                // Dispatch recorded (vehicle number just appeared)
                if (!old.dispatch_vehicle_number && (row.dispatch_vehicle_number || ''))
                    pushNotif('🚚 Dispatch Recorded',
                        'PO ' + pn + ' · Vehicle: ' + row.dispatch_vehicle_number);

                // Arrival recorded (arrival_time just appeared)
                if (!old.arrival_time && (row.arrival_time || '')) {
                    var arrTime = row.arrival_time ?
                        new Date(row.arrival_time).toLocaleTimeString('en-GB', {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) :
                        '';
                    pushNotif('🏠 Vehicle Arrived',
                        'PO ' + pn + ' arrived at ' + arrTime);
                }

                // Handover status changed (pending → success or rejected)
                var newHO = (row.handover_status || '').toLowerCase();
                var oldHO = (old.handover_status || '').toLowerCase();
                if (oldHO !== newHO && newHO !== 'pending' && newHO !== '') {
                    if (newHO === 'success')
                        pushNotif('✅ Handover Successful',
                            'PO ' + pn + ' delivered successfully — ready to mark Done.');
                    else if (newHO === 'rejected')
                        pushNotif('❌ Handover Rejected',
                            'PO ' + pn + ' delivery was rejected by dispatch supervisor.');
                }

                // Reschedule date just set or changed
                var newRD = row.reschedule_date || '';
                var oldRD = old.reschedule_date || '';
                if (newRD && newRD !== oldRD) {
                    var fmtRD = newRD.substring(0, 10).split('-').reverse().join('-'); // dd-mm-yyyy
                    pushNotif('🔄 Delivery Rescheduled',
                        'PO ' + pn + ' rescheduled to ' + fmtRD);
                }
            });
        }
        /* ── Filters ── */
        function getActiveFilters() {
            return {
                search: (searchInput.value || '').trim().toLowerCase(),
                status: (statusFilter.value || '').trim().toLowerCase(),
                factory: (factoryFilter.value || '').trim(),
                date: (dateFilter.value || '').trim()
            };
        }

        function rowMatchesFilters(row, filters) {
            if (lockedFactory && String(row.factory_name || '') !== lockedFactory) return false;
            var searchText = [row.id, row.po_number, row.platform, row.factory_name, row.po_status, row.creator_name,
                row.release_date, row.expiry_date, row.expected_delivery_date, row.delivery_schedule_date,
                row.reschedule_date, row.dispatch_vehicle_number, row.dispatch_temperature,
                row.arrival_temperature, row.handover_status
            ].join(' ').toLowerCase();
            if (filters.search && !searchText.includes(filters.search)) return false;
            if (filters.status && String(row.po_status || '').toLowerCase() !== filters.status) return false;
            if (filters.factory && String(row.factory_name || '') !== filters.factory) return false;
            if (filters.date && normalizeDate(row.release_date) !== filters.date) return false;
            return true;
        }

        function applyFiltersAndRender() {
            var filters = getActiveFilters();
            var filtered = allRows.filter(function(r) {
                return rowMatchesFilters(r, filters);
            });
            renderTable(filtered);
            updateCards(filtered);
            updateDateAlertBar('expiry', calculateDateAlertStats(filtered, 'expiry_date'), 'expiry date');
            updateDateAlertBar('expected', calculateDateAlertStats(filtered, 'expected_delivery_date'), 'expected delivery date');
            updateDateAlertBar('schedule', calculateDateAlertStats(filtered, 'delivery_schedule_date'), 'schedule date');
        }

        /* ── Render table ── */
        function renderTable(rows) {
            var tbody = document.getElementById('po-tbody');
            if (!rows.length) {
                tbody.innerHTML = '<tr id="empty-row"><td colspan="16"><div class="empty-state"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><div>No purchase orders found</div></div></td></tr>';
                return;
            }
            var html = '';
            rows.forEach(function(row) {
                html += buildRowHtml(row, '');
            });
            tbody.innerHTML = html;
        }

        /* ── Stats ── */
        function updateCards(rows) {
            var stats = {
                total: rows.length,
                done: 0,
                rejected: 0,
                scheduled: 0,
                needs_schedule: 0
            };
            rows.forEach(function(r) {
                var st = String(r.po_status || '').toLowerCase();
                if (st === 'done') stats.done++;
                if (st === 'rejected') stats.rejected++;
                if (st === 'delivery_date_scheduled') stats.scheduled++;
                if (st === 'sent_to_schedule_delivery') stats.needs_schedule++;
            });
            stats.open = stats.total - stats.done - stats.rejected;
            processStats(stats);
        }

        function processStats(stats) {
            if (!stats) return;
            var pairs = {
                'stat-total': stats.total,
                'stat-open': stats.open,
                'stat-needs': stats.needs_schedule,
                'stat-scheduled': stats.scheduled,
                'stat-done': stats.done,
                'stat-rejected': stats.rejected || 0
            };
            Object.keys(pairs).forEach(function(id) {
                var el = document.getElementById(id);
                var val = String(pairs[id] ?? 0);
                if (el && el.textContent.trim() !== val) {
                    el.textContent = val;
                    el.classList.remove('stat-bump');
                    void el.offsetWidth;
                    el.classList.add('stat-bump');
                    setTimeout(function() {
                        el.classList.remove('stat-bump');
                    }, 400);
                } else if (el) el.textContent = val;
            });
            var doneRate = stats.total > 0 ? ((stats.done / stats.total) * 100) : 0;
            var rejectedRate = stats.total > 0 ? (((stats.rejected || 0) / stats.total) * 100) : 0;
            var openCount = Math.max(stats.total - stats.done - (stats.rejected || 0), 0);
            var openRate = stats.total > 0 ? ((openCount / stats.total) * 100) : 0;
            var rRate = Math.round(doneRate * 10) / 10,
                rRej = Math.round(rejectedRate * 10) / 10,
                rOpen = Math.round(openRate * 10) / 10;
            var rf = document.getElementById('po-done-rate-fill'),
                rt = document.getElementById('po-done-rate-text'),
                rm = document.getElementById('po-done-rate-meta');
            var ej = document.getElementById('po-rejected-rate-fill'),
                et = document.getElementById('po-rejected-rate-text');
            var of = document.getElementById('po-open-rate-fill'), ot = document.getElementById('po-open-rate-text');
            if (rf) rf.style.width = rRate + '%';
            if (rt) rt.textContent = rRate + '%';
            if (ej) ej.style.width = rRej + '%';
            if (et) et.textContent = rRej + '%';
            if (of) of.style.width = rOpen + '%';
            if (ot) ot.textContent = rOpen + '%';
            if (rm) rm.textContent = stats.done + ' done · ' + (stats.rejected || 0) + ' rejected · ' + stats.total + ' total';
        }

        function populateFactoryFilter(rows) {
            if (lockedFactory) return;
            var cur = factoryFilter.value;
            var factories = {};
            rows.forEach(function(r) {
                var f = String(r.factory_name || '').trim();
                if (f) factories[f] = true;
            });
            var sorted = Object.keys(factories).sort(function(a, b) {
                return a.localeCompare(b);
            });
            var html = '<option value="">All Factory</option>';
            sorted.forEach(function(f) {
                html += '<option value="' + e(f) + '">' + e(f) + '</option>';
            });
            factoryFilter.innerHTML = html;
            if (cur && factories[cur]) factoryFilter.value = cur;
        }

        if (searchInput) searchInput.addEventListener('input', applyFiltersAndRender);
        if (statusFilter) statusFilter.addEventListener('change', applyFiltersAndRender);
        if (factoryFilter) factoryFilter.addEventListener('change', applyFiltersAndRender);
        if (dateFilter) dateFilter.addEventListener('change', applyFiltersAndRender);
        if (clearBtn) clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            statusFilter.value = '';
            if (!lockedFactory) factoryFilter.value = '';
            dateFilter.value = '';
            applyFiltersAndRender();
        });

        /* ── Build row HTML ── */
        function buildRowHtml(row, rowClass) {
            var st = (row.po_status || '').toLowerCase();
            var handoverStatus = (row.handover_status || 'pending').toLowerCase();
            var dispatchFilled = !!row.dispatch_vehicle_number;
            var arrivalFilled = !!row.arrival_time;

            var showExp = !!row.expected_delivery_date;
            var showSch = !!row.delivery_schedule_date;
            var showReschedule = !!row.reschedule_date;
            var showBuyerExp = !!row.buyer_expected_date;

            var canDone = IS_ADMIN && st === 'delivery_date_scheduled' && handoverStatus === 'success';
            var canReschedule = IS_ADMIN && ['delivery_date_scheduled', 'rejected'].includes(st);
            // Each step only shown if NOT yet filled this cycle (reschedule clears all fields)
            var canDispatch = IS_DISPATCH && st === 'delivery_date_scheduled' && !dispatchFilled;
            var canArrival = IS_DISPATCH && st === 'delivery_date_scheduled' && dispatchFilled && !arrivalFilled;
            var canHandover = IS_DISPATCH && st === 'delivery_date_scheduled' && arrivalFilled && handoverStatus === 'pending';

            var TKCAL = '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';

            var expectedHtml = showExp ? '<span class="schedule-pill">' + TKCAL + e(fmtDate(row.expected_delivery_date)) + '</span>' : '<span style="color:#ccc;font-size:12px">—</span>';
            var scheduleHtml = showSch ? '<span class="schedule-pill">' + TKCAL + e(fmtDate(row.delivery_schedule_date)) + '</span>' : '<span style="color:#ccc;font-size:12px">—</span>';
            var rescheduleHtml = showReschedule ? '<span class="schedule-pill" style="background:#e8eaf6;color:#283593;"><svg viewBox="0 0 24 24" style="stroke:#283593">' + TKCAL.replace('<svg', '').replace('viewBox', 'viewBox') + '' + e(fmtDate(row.reschedule_date)) + '</span>' : '<span style="color:#ccc;font-size:12px">—</span>';
            rescheduleHtml = showReschedule ? '<span class="schedule-pill" style="background:#e8eaf6;color:#283593;">' + TKCAL + e(fmtDate(row.reschedule_date)) + '</span>' : '<span style="color:#ccc;font-size:12px">—</span>';
            var buyerExpHtml = showBuyerExp ? '<span class="schedule-pill" style="background:#e3f2fd;color:#1565c0;">' + TKCAL + e(fmtDate(row.buyer_expected_date)) + '</span>' : '<span style="color:#ccc;font-size:12px">—</span>';

            var pdfHtml = row.pdf_file_path ?
                '<a href="' + e(row.pdf_file_path) + '" target="_blank" class="action-link action-pdf"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>PDF</a>' :
                '<span class="no-pdf">No PDF</span>';

            // Dispatch info cell
            var dispatchCell = '<span style="color:#ccc;font-size:12px">—</span>';
            if (dispatchFilled) {
                dispatchCell = '<div style="display:flex;flex-direction:column;gap:4px">';
                dispatchCell += '<span class="disp-chip">🚚 ' + e(row.dispatch_vehicle_number) + '</span>';
                if (row.dispatch_temperature) dispatchCell += '<span class="disp-chip">🌡️ ' + e(row.dispatch_temperature) + '</span>';
                if (row.dispatch_time) dispatchCell += '<span class="disp-chip">🕐 ' + e(fmtDateTime(row.dispatch_time)) + '</span>';
                if (row.dispatch_temp_photo) dispatchCell += '<a href="' + e(row.dispatch_temp_photo) + '" target="_blank" style="font-size:10px;color:#00796b;font-weight:600">📷 Temp Photo</a>';
                if (row.dispatch_bill_copy) dispatchCell += '<a href="' + e(row.dispatch_bill_copy) + '"  target="_blank" style="font-size:10px;color:#c62828;font-weight:600">📄 Bill Copy</a>';
                dispatchCell += '</div>';
            }

            // Arrival info cell
            var arrivalCell = '<span style="color:#ccc;font-size:12px">—</span>';
            if (arrivalFilled) {
                arrivalCell = '<div style="display:flex;flex-direction:column;gap:4px">';
                arrivalCell += '<span class="arr-chip">🕐 ' + e(fmtDateTime(row.arrival_time)) + '</span>';
                if (row.arrival_temperature) arrivalCell += '<span class="arr-chip">🌡️ ' + e(row.arrival_temperature) + '</span>';
                if (row.arrival_temp_photo) arrivalCell += '<a href="' + e(row.arrival_temp_photo) + '" target="_blank" style="font-size:10px;color:#6a1b9a;font-weight:600">📷 Arrival Photo</a>';
                arrivalCell += '</div>';
            }

            // Handover cell
            var handoverCell;
            if (handoverStatus === 'success') {
                handoverCell = '<span class="handover-success"><span class="dot"></span>Success</span>';
            } else if (handoverStatus === 'rejected') {
                var safeHReason = e(row.handover_rejection_reason || 'No reason provided');
                handoverCell = '<span class="handover-rejected" onclick="showHandoverRejectReason(this,\'' + safeHReason.replace(/'/g, "&#039;") + '\')"><span class="dot"></span>Rejected ℹ</span>';
            } else if (st === 'delivery_date_scheduled') {
                handoverCell = '<span class="handover-pending"><span class="dot"></span>Pending</span>';
            } else {
                handoverCell = '<span style="color:#ccc;font-size:12px">—</span>';
            }

            // Status cell
            var statusHtml;
            if (st === 'rejected') {
                var safeReason = e(row.rejection_reason || 'No reason provided');
                statusHtml = '<span class="status-badge status-rejected" onclick="showRejectReason(this,\'' + safeReason.replace(/'/g, "&#039;") + '\')"><span class="dot"></span>Rejected<svg class="info-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="8"/><line x1="12" y1="12" x2="12" y2="16"/></svg></span>';
            } else {
                statusHtml = '<span class="status-badge ' + statusClass(row.po_status) + '"><span class="dot"></span>' + e(formatStatus(row.po_status)) + '</span>';
            }

            // Action buttons
            var actions = '<a href="po_view.php?id=' + e(row.id) + '" class="action-link action-view"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>View</a>';
            actions += pdfHtml;

            if (canReschedule) {
                actions += '<button type="button" class="action-btn action-reschedule" onclick="openRescheduleModal(' + e(row.id) + ',\'' + e(row.po_number || '').replace(/'/g, "\\'") + '\')">' +
                    '<svg viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>Reschedule</button>';
            }
            if (canDone) {
                actions += '<form method="POST" action="mark_po_done.php" onsubmit="return confirm(\'Mark this PO as done?\');" style="display:inline;">' +
                    '<input type="hidden" name="po_id" value="' + e(row.id) + '">' +
                    '<button type="submit" class="action-btn action-done"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Mark Done</button></form>';
            }
            if (canDispatch) {
                actions += '<button type="button" class="action-btn action-dispatch" onclick="openDispatchModal(' + e(row.id) + ',\'' + e(row.po_number || '').replace(/'/g, "\\'") + '\')">' +
                    '<svg viewBox="0 0 24 24"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>' +
                    'Add Dispatch</button>';
            }
            if (canArrival) {
                actions += '<button type="button" class="action-btn action-arrival" onclick="openArrivalModal(' + e(row.id) + ',\'' + e(row.po_number || '').replace(/'/g, "\\'") + '\')">' +
                    '<svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>' +
                    'Add Arrival</button>';
            }
            if (canHandover) {
                actions += '<button type="button" class="action-btn action-handover-success" onclick="openHandoverModal(' + e(row.id) + ',\'' + e(row.po_number || '').replace(/'/g, "\\'") + "','success')\">" +
                    '<svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Success</button>';
                actions += '<button type="button" class="action-btn action-handover-reject" onclick="openHandoverModal(' + e(row.id) + ',\'' + e(row.po_number || '').replace(/'/g, "\\'") + "','reject')\">" +
                    '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Reject</button>';
            }
            actions += '<a href="po_workflow_history.php?po_id=' + e(row.id) + '" class="action-link action-view"><svg viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>History</a>';

            return '<tr class="' + e(rowClass) + '" data-po-id="' + e(row.id) + '" data-po-status="' + e(row.po_status || '') + '">' +
                '<td style="color:#bbb;font-size:12px;font-family:\'DM Mono\',monospace">' + e(row.id) + '</td>' +
                '<td><span class="po-num">' + e(row.po_number || '') + '</span></td>' +
                '<td><span class="badge ' + platClass(row.platform) + '">' + e(row.platform || '—') + '</span></td>' +
                '<td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + e(row.factory_name || '') + '">' + e(row.factory_name || '—') + '</td>' +
                '<td style="font-size:12px;color:#666">' + e(fmtDate(row.release_date)) + '</td>' +
                '<td style="font-size:12px;color:#666">' + e(fmtDate(row.expiry_date)) + '</td>' +
                '<td>' + buyerExpHtml + '</td>' +
                '<td>' + statusHtml + '</td>' +
                '<td>' + expectedHtml + '</td>' +
                '<td>' + scheduleHtml + '</td>' +
                '<td>' + rescheduleHtml + '</td>' +
                '<td>' + dispatchCell + '</td>' +
                '<td>' + arrivalCell + '</td>' +
                '<td>' + handoverCell + '</td>' +
                '<td style="font-size:12px;color:#666">' + e(row.creator_name || '—') + '</td>' +
                '<td><div class="action-group">' + actions + '</div></td>' +
                '</tr>';
        }

        /* ── Helpers ── */
        function statusClass(s) {
            var m = {
                'pending': 'status-pending',
                'in_progress': 'status-in_progress',
                'sent_to_schedule_delivery': 'status-sent_to_schedule_delivery',
                'delivery_date_scheduled': 'status-delivery_date_scheduled',
                'done': 'status-done',
                'rejected': 'status-rejected'
            };
            return m[(s || '').toLowerCase()] || 'status-other';
        }

        function platClass(p) {
            var m = {
                'instamart': 'badge-instamart',
                'blinkit': 'badge-blinkit',
                'zepto': 'badge-zepto',
                'flipkart': 'badge-flipkart'
            };
            return m[(p || '').toLowerCase()] || 'badge-default';
        }

        function formatStatus(s) {
            return (s || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, function(c) {
                return c.toUpperCase();
            });
        }

        function fmtDate(d) {
            if (!d) return '—';
            if (/^\d{4}-\d{2}-\d{2}/.test(d)) {
                var p = d.substring(0, 10).split('-');
                return p[2] + '-' + p[1] + '-' + p[0];
            }
            var dt = new Date(d);
            return isNaN(dt.getTime()) ? d : dt.toLocaleDateString('en-GB');
        }

        function fmtDateTime(d) {
            if (!d) return '—';
            var dt = new Date(d);
            if (isNaN(dt.getTime())) return d;
            return dt.toLocaleDateString('en-GB') + ' ' + dt.toLocaleTimeString('en-GB', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function normalizeDate(d) {
            if (!d) return '';
            if (/^\d{4}-\d{2}-\d{2}$/.test(d)) return d;
            var dt = new Date(d);
            if (isNaN(dt.getTime())) return '';
            return dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0');
        }

        function e(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        /* ── Progress bars ── */
        function applyProgressBarVisibility(hidden) {
            if (!progressSection || !toggleProgressBtn) return;
            progressSection.style.display = hidden ? 'none' : '';
            toggleProgressBtn.textContent = hidden ? 'Show Progress Bars' : 'Hide Progress Bars';
        }
        if (toggleProgressBtn) {
            var savedHidden = localStorage.getItem('po_progress_bars_hidden') === '1';
            applyProgressBarVisibility(savedHidden);
            toggleProgressBtn.addEventListener('click', function() {
                var nextHidden = progressSection.style.display !== 'none';
                applyProgressBarVisibility(nextHidden);
                localStorage.setItem('po_progress_bars_hidden', nextHidden ? '1' : '0');
            });
        }

        /* ── Factory lock ── */
        function saveFactoryChoice(factory) {
            try {
                localStorage.setItem(LS_COMPANY_KEY, factory);
            } catch (_) {}
            fetch('save_user_company.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'company=' + encodeURIComponent(factory)
            }).catch(function() {});
        }

        function loadSavedFactory() {
            var phpVal = <?= json_encode($sessionCompany) ?>;
            if (phpVal) {
                try {
                    localStorage.setItem(LS_COMPANY_KEY, phpVal);
                } catch (_) {}
                return phpVal;
            }
            try {
                return localStorage.getItem(LS_COMPANY_KEY);
            } catch (_) {
                return null;
            }
        }

        function applyLockedFactory(factory) {
            lockedFactory = factory;
            var wrap = document.getElementById('locked-company-wrap');
            var nameEl = document.getElementById('locked-company-name');
            var emojiEl = document.getElementById('locked-company-emoji');
            if (wrap) wrap.style.display = '';
            if (nameEl) nameEl.textContent = factory;
            if (emojiEl) emojiEl.textContent = '🏭';
            if (factoryFilter) {
                var exists = Array.from(factoryFilter.options).some(function(o) {
                    return o.value === factory;
                });
                if (!exists) {
                    var opt = document.createElement('option');
                    opt.value = factory;
                    opt.textContent = factory;
                    factoryFilter.appendChild(opt);
                }
                factoryFilter.value = factory;
                factoryFilter.disabled = true;
            }
            if (factoryBox) factoryBox.classList.add('is-locked');
        }

        var companyOverlay = document.getElementById('company-modal-overlay');
        var companyGrid = document.getElementById('company-option-grid');
        var companyConfirm = document.getElementById('company-modal-confirm');
        var companyErrorMsg = document.getElementById('company-modal-error-msg');
        var selectedFactory = null;
        if (companyGrid) {
            companyGrid.addEventListener('click', function(ev) {
                var opt = ev.target.closest('.company-option');
                if (!opt) return;
                companyGrid.querySelectorAll('.company-option').forEach(function(el) {
                    el.classList.remove('selected');
                });
                opt.classList.add('selected');
                selectedFactory = opt.dataset.value;
                companyConfirm.disabled = false;
                companyErrorMsg.style.display = 'none';
            });
        }
        if (companyConfirm) {
            companyConfirm.addEventListener('click', function() {
                if (!selectedFactory) {
                    companyErrorMsg.style.display = 'block';
                    return;
                }
                saveFactoryChoice(selectedFactory);
                companyOverlay.classList.remove('active');
                applyLockedFactory(selectedFactory);
                applyFiltersAndRender();
            });
        }

        if (REQUIRES_FACTORY_LOCK) {
            var saved = loadSavedFactory();
            if (saved) applyLockedFactory(saved);
            else companyOverlay.classList.add('active');
        }

        populateFactoryFilter(allRows);
        applyFiltersAndRender();

    })();

    /* ── Date alert stats (global) ── */
    function calculateDateAlertStats(rows, fieldName) {
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var stats = {
            total: 0,
            safe: 0,
            near: 0,
            reached: 0,
            safe_pct: 0,
            near_pct: 0,
            reached_pct: 0
        };
        rows.forEach(function(row) {
            var raw = row[fieldName];
            if (!raw || raw === '0000-00-00') return;
            var status = String(row.po_status || '').toLowerCase();
            if (fieldName === 'delivery_schedule_date' && status === 'done') return;
            var dt = new Date(raw);
            if (isNaN(dt.getTime())) return;
            dt.setHours(0, 0, 0, 0);
            var diffDays = Math.floor((dt.getTime() - today.getTime()) / 86400000);
            stats.total++;
            if (diffDays < 0) stats.reached++;
            else if (diffDays <= 3) stats.near++;
            else stats.safe++;
        });
        if (stats.total > 0) {
            stats.safe_pct = (stats.safe / stats.total) * 100;
            stats.near_pct = (stats.near / stats.total) * 100;
            stats.reached_pct = (stats.reached / stats.total) * 100;
        }
        return stats;
    }

    function updateDateAlertBar(prefix, stats, labelText) {
        var safeEl = document.getElementById(prefix + '-safe'),
            nearEl = document.getElementById(prefix + '-near'),
            reachedEl = document.getElementById(prefix + '-reached');
        var metaEl = document.getElementById(prefix + '-meta'),
            legendEl = document.getElementById(prefix + '-legend');
        if (safeEl) safeEl.style.width = stats.safe_pct.toFixed(1) + '%';
        if (nearEl) nearEl.style.width = stats.near_pct.toFixed(1) + '%';
        if (reachedEl) reachedEl.style.width = stats.reached_pct.toFixed(1) + '%';
        if (metaEl) metaEl.textContent = stats.total + ' items with ' + labelText;
        if (legendEl) legendEl.innerHTML = '<span class="legend-item"><span class="legend-dot legend-safe"></span> Safe: ' + stats.safe + '</span><span class="legend-item"><span class="legend-dot legend-near"></span> Near: ' + stats.near + '</span><span class="legend-item"><span class="legend-dot legend-reached"></span> Reached: ' + stats.reached + '</span>';
    }

    /* ── Rejection reason popovers ── */
    var reasonPopover = document.getElementById('reject-reason-popover');
    var reasonPopText = document.getElementById('reject-reason-popover-text');
    var activeBadge = null;

    window.showRejectReason = function(badgeEl, reason) {
        if (activeBadge === badgeEl && reasonPopover.classList.contains('visible')) {
            hideReasonPopover();
            return;
        }
        activeBadge = badgeEl;
        reasonPopText.textContent = reason || 'No reason provided';
        reasonPopover.classList.add('visible');
        positionPopover(badgeEl);
    };
    window.showHandoverRejectReason = function(badgeEl, reason) {
        window.showRejectReason(badgeEl, reason);
    };

    function positionPopover(anchor) {
        var rect = anchor.getBoundingClientRect();
        var popW = 280;
        var left = rect.left;
        if (left + popW > window.innerWidth - 12) left = window.innerWidth - popW - 12;
        if (left < 8) left = 8;
        reasonPopover.style.left = left + 'px';
        reasonPopover.style.top = (rect.bottom + 10) + 'px';
        var arrowLeft = Math.min(Math.max(rect.left + rect.width / 2 - left - 6, 10), popW - 22);
        reasonPopover.style.setProperty('--arrow-left', arrowLeft + 'px');
    }

    function hideReasonPopover() {
        reasonPopover.classList.remove('visible');
        activeBadge = null;
    }
    document.addEventListener('click', function(e) {
        if (!reasonPopover.contains(e.target) && !e.target.closest('.status-rejected') && !e.target.closest('.handover-rejected')) hideReasonPopover();
    });
    window.addEventListener('scroll', hideReasonPopover, true);
    window.addEventListener('resize', function() {
        if (activeBadge) positionPopover(activeBadge);
    });

    /* ── Toast ── */
    function showToast(msg, type) {
        var cont = document.getElementById('toast-container');
        var d = document.createElement('div');
        d.className = 'toast toast-' + (type || 'success');
        d.textContent = msg;
        cont.appendChild(d);
        setTimeout(function() {
            if (d.parentNode) d.parentNode.removeChild(d);
        }, 4000);
    }

    /* ── Dispatch Modal ── */
    var _currentDispatchPoId = null;
    window.openDispatchModal = function(poId, poNum) {
        _currentDispatchPoId = poId;
        document.getElementById('dispatch-modal-po-num').textContent = 'PO #' + poNum;
        document.getElementById('dispatch-vehicle').value = '';
        document.getElementById('dispatch-time').value = '';
        document.getElementById('dispatch-temp').value = '';
        document.getElementById('dispatch-temp-photo').value = '';
        document.getElementById('dispatch-bill').value = '';
        document.getElementById('dispatch-modal-error').style.display = 'none';
        document.getElementById('dispatch-modal-overlay').classList.add('active');
        setTimeout(function() {
            document.getElementById('dispatch-vehicle').focus();
        }, 80);
    };
    document.getElementById('dispatch-modal-cancel').addEventListener('click', function() {
        document.getElementById('dispatch-modal-overlay').classList.remove('active');
    });
    document.getElementById('dispatch-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });

    window.submitDispatch = function() {
        var vehicle = document.getElementById('dispatch-vehicle').value.trim();
        var time = document.getElementById('dispatch-time').value;
        var temp = document.getElementById('dispatch-temp').value.trim();
        var errEl = document.getElementById('dispatch-modal-error');
        if (!vehicle || !time || !temp) {
            errEl.textContent = 'Vehicle number, dispatch time and temperature are required.';
            errEl.style.display = 'block';
            return;
        }
        errEl.style.display = 'none';

        var fd = new FormData();
        fd.append('action', 'save_dispatch');
        fd.append('po_id', _currentDispatchPoId);
        fd.append('vehicle_number', vehicle);
        fd.append('dispatch_time', time);
        fd.append('dispatch_temperature', temp);
        var tpFile = document.getElementById('dispatch-temp-photo').files[0];
        var billFile = document.getElementById('dispatch-bill').files[0];
        if (tpFile) fd.append('dispatch_temp_photo', tpFile);
        if (billFile) fd.append('dispatch_bill_copy', billFile);

        var btn = document.getElementById('dispatch-modal-confirm');
        btn.disabled = true;
        btn.textContent = 'Saving…';
        fetch('dispatch_action.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('dispatch-modal-overlay').classList.remove('active');
                    showToast('✅ Dispatch details saved!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1200);
                } else {
                    errEl.textContent = data.error || 'Failed to save.';
                    errEl.style.display = 'block';
                }
            })
            .catch(function() {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'block';
            })
            .finally(function() {
                btn.disabled = false;
                btn.textContent = 'Save Dispatch';
            });
    };

    /* ── Arrival Modal ── */
    var _currentArrivalPoId = null;
    window.openArrivalModal = function(poId, poNum) {
        _currentArrivalPoId = poId;
        document.getElementById('arrival-modal-po-num').textContent = 'PO #' + poNum;
        document.getElementById('arrival-time').value = '';
        document.getElementById('arrival-temp').value = '';
        document.getElementById('arrival-temp-photo').value = '';
        document.getElementById('arrival-modal-error').style.display = 'none';
        document.getElementById('arrival-modal-overlay').classList.add('active');
        setTimeout(function() {
            document.getElementById('arrival-time').focus();
        }, 80);
    };
    document.getElementById('arrival-modal-cancel').addEventListener('click', function() {
        document.getElementById('arrival-modal-overlay').classList.remove('active');
    });
    document.getElementById('arrival-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });

    window.submitArrival = function() {
        var time = document.getElementById('arrival-time').value;
        var temp = document.getElementById('arrival-temp').value.trim();
        var errEl = document.getElementById('arrival-modal-error');
        if (!time || !temp) {
            errEl.textContent = 'Arrival time and temperature are required.';
            errEl.style.display = 'block';
            return;
        }
        errEl.style.display = 'none';

        var fd = new FormData();
        fd.append('action', 'save_arrival');
        fd.append('po_id', _currentArrivalPoId);
        fd.append('arrival_time', time);
        fd.append('arrival_temperature', temp);
        var tpFile = document.getElementById('arrival-temp-photo').files[0];
        if (tpFile) fd.append('arrival_temp_photo', tpFile);

        fetch('dispatch_action.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('arrival-modal-overlay').classList.remove('active');
                    showToast('✅ Arrival details saved!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1200);
                } else {
                    errEl.textContent = data.error || 'Failed to save.';
                    errEl.style.display = 'block';
                }
            })
            .catch(function() {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'block';
            });
    };

    /* ── Handover Modal ── */
    var _currentHandoverPoId = null;
    var _currentHandoverType = null;
    window.openHandoverModal = function(poId, poNum, type) {
        _currentHandoverPoId = poId;
        _currentHandoverType = type;
        document.getElementById('handover-modal-po-num').textContent = 'PO #' + poNum;
        document.getElementById('handover-reject-reason').value = '';
        document.getElementById('handover-modal-error').style.display = 'none';
        var icon = document.getElementById('handover-modal-icon');
        var title = document.getElementById('handover-modal-title');
        var btn = document.getElementById('handover-modal-confirm');
        var rejectField = document.getElementById('handover-reject-field');
        var successNote = document.getElementById('handover-success-note');
        if (type === 'success') {
            icon.style.background = '#e8f5e9';
            icon.innerHTML = '<svg viewBox="0 0 24 24" style="width:20px;height:20px;stroke:#1b5e20;fill:none;stroke-width:2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
            title.textContent = 'Mark Delivery as Success';
            btn.style.background = '#2e7d32';
            btn.textContent = 'Confirm Success';
            rejectField.style.display = 'none';
            successNote.style.display = 'flex';
        } else {
            icon.style.background = '#fce4ec';
            icon.innerHTML = '<svg viewBox="0 0 24 24" style="width:20px;height:20px;stroke:#c62828;fill:none;stroke-width:2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            title.textContent = 'Mark Delivery as Rejected';
            btn.style.background = '#c62828';
            btn.textContent = 'Confirm Rejection';
            rejectField.style.display = 'block';
            successNote.style.display = 'none';
        }
        document.getElementById('handover-modal-overlay').classList.add('active');
    };
    document.getElementById('handover-modal-cancel').addEventListener('click', function() {
        document.getElementById('handover-modal-overlay').classList.remove('active');
    });
    document.getElementById('handover-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });

    window.submitHandover = function() {
        var errEl = document.getElementById('handover-modal-error');
        var fd = new FormData();
        fd.append('po_id', _currentHandoverPoId);

        if (_currentHandoverType === 'success') {
            fd.append('action', 'handover_success');
        } else {
            var reason = document.getElementById('handover-reject-reason').value.trim();
            if (!reason) {
                errEl.textContent = 'Please enter a rejection reason.';
                errEl.style.display = 'block';
                return;
            }
            fd.append('action', 'handover_reject');
            fd.append('handover_rejection_reason', reason);
        }
        errEl.style.display = 'none';
        var btn = document.getElementById('handover-modal-confirm');
        btn.disabled = true;
        btn.textContent = 'Saving…';
        fetch('dispatch_action.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('handover-modal-overlay').classList.remove('active');
                    showToast(_currentHandoverType === 'success' ? '✅ Marked as successful!' : '✅ Marked as rejected!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1200);
                } else {
                    errEl.textContent = data.error || 'Failed.';
                    errEl.style.display = 'block';
                }
            })
            .catch(function() {
                errEl.textContent = 'Network error.';
                errEl.style.display = 'block';
            })
            .finally(function() {
                btn.disabled = false;
                btn.textContent = _currentHandoverType === 'success' ? 'Confirm Success' : 'Confirm Rejection';
            });
    };

    /* ── Reschedule Modal ── */
    var rescheduleOverlay = document.getElementById('reschedule-modal-overlay');
    var rescheduleForm = document.getElementById('reschedule-modal-form');
    var reschedulePoIdIn = document.getElementById('reschedule-modal-po-id');
    var reschedulePoNumEl = document.getElementById('reschedule-modal-po-num');
    var rescheduleDateIn = document.getElementById('reschedule-date-input');
    var rescheduleError = document.getElementById('reschedule-modal-error');
    var rescheduleCancel = document.getElementById('reschedule-modal-cancel');
    window.openRescheduleModal = function(poId, poNumber) {
        reschedulePoIdIn.value = poId;
        reschedulePoNumEl.textContent = 'PO #' + poNumber;
        rescheduleDateIn.value = '';
        rescheduleError.style.display = 'none';
        rescheduleOverlay.classList.add('active');
        setTimeout(function() {
            rescheduleDateIn.focus();
        }, 80);
    };

    function closeRescheduleModal() {
        rescheduleOverlay.classList.remove('active');
    }
    if (rescheduleCancel) rescheduleCancel.addEventListener('click', closeRescheduleModal);
    if (rescheduleOverlay) rescheduleOverlay.addEventListener('click', function(e) {
        if (e.target === rescheduleOverlay) closeRescheduleModal();
    });
    if (rescheduleForm) rescheduleForm.addEventListener('submit', function(e) {
        if (!rescheduleDateIn.value) {
            e.preventDefault();
            rescheduleError.style.display = 'block';
            rescheduleDateIn.focus();
        } else {
            rescheduleError.style.display = 'none';
        }
    });

    /* ── Reject Modal (kept for compatibility) ── */
    var rejectOverlay = document.getElementById('reject-modal-overlay');
    var rejectForm = document.getElementById('reject-modal-form');
    var rejectPoIdIn = document.getElementById('reject-modal-po-id');
    var rejectPoNumEl = document.getElementById('reject-modal-po-num');
    var rejectTextarea = document.getElementById('reject-reason-textarea');
    var rejectError = document.getElementById('reject-modal-error');
    var rejectCancel = document.getElementById('reject-modal-cancel');
    window.openRejectModal = function(poId, poNumber) {
        rejectPoIdIn.value = poId;
        rejectPoNumEl.textContent = 'PO #' + poNumber;
        rejectTextarea.value = '';
        rejectError.style.display = 'none';
        rejectOverlay.classList.add('active');
        setTimeout(function() {
            rejectTextarea.focus();
        }, 80);
    };

    function closeRejectModal() {
        rejectOverlay.classList.remove('active');
    }
    if (rejectCancel) rejectCancel.addEventListener('click', closeRejectModal);
    if (rejectOverlay) rejectOverlay.addEventListener('click', function(e) {
        if (e.target === rejectOverlay) closeRejectModal();
    });
    if (rejectForm) rejectForm.addEventListener('submit', function(e) {
        if (!rejectTextarea.value.trim()) {
            e.preventDefault();
            rejectError.style.display = 'block';
            rejectTextarea.focus();
        } else {
            rejectError.style.display = 'none';
        }
    });

    /* ── ESC closes modals ── */
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        if (rejectOverlay && rejectOverlay.classList.contains('active')) closeRejectModal();
        if (rescheduleOverlay && rescheduleOverlay.classList.contains('active')) closeRescheduleModal();
        var dm = document.getElementById('dispatch-modal-overlay');
        if (dm && dm.classList.contains('active')) dm.classList.remove('active');
        var am = document.getElementById('arrival-modal-overlay');
        if (am && am.classList.contains('active')) am.classList.remove('active');
        var hm = document.getElementById('handover-modal-overlay');
        if (hm && hm.classList.contains('active')) hm.classList.remove('active');
    });

    /* ── Table drag-to-scroll ── */
    var tableDrag = document.getElementById('table-scroll-drag');
    var isDown = false,
        startX, scrollLeft;
    tableDrag.addEventListener('mousedown', function(e) {
        isDown = true;
        tableDrag.classList.add('dragging');
        startX = e.pageX - tableDrag.offsetLeft;
        scrollLeft = tableDrag.scrollLeft;
    });
    tableDrag.addEventListener('mouseleave', function() {
        isDown = false;
        tableDrag.classList.remove('dragging');
    });
    tableDrag.addEventListener('mouseup', function() {
        isDown = false;
        tableDrag.classList.remove('dragging');
    });
    tableDrag.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        tableDrag.scrollLeft = scrollLeft - (e.pageX - tableDrag.offsetLeft - startX) * 1.5;
    });
</script>

<?php include 'partials/footer.php'; ?>