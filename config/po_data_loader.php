<?php
/**
 * config/po_data_loader.php
 *
 * Loads all PO rows and resolves the current user's role + factory lock.
 * Requires: $conn (MySQLi connection), $_SESSION already started.
 *
 * Provides:
 *   $rows            — all PO rows as assoc arrays
 *   $factoryList     — sorted unique factory names (for filter dropdown)
 *   $currentUserId   — int
 *   $isAdmin, $isSuper, $isViewer, $isUser  — booleans
 *   $requiresFactoryLock — bool (true for 'user' role only)
 *   $sessionCompany  — string, the factory locked for this user ('' for admins)
 */

// ── Fetch all POs ─────────────────────────────────────────────────────────
$sql = "SELECT po.*, u.name AS creator_name
        FROM purchase_orders po
        LEFT JOIN users u ON po.created_by = u.id
        ORDER BY po.id DESC";
$result = $conn->query($sql);

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// ── Summary stats (used by po_stats.php) ─────────────────────────────────
$total      = count($rows);
$done       = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'done'));
$scheduled  = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'delivery_date_scheduled'));
$needsSched = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'sent_to_schedule_delivery'));
$rejected   = count(array_filter($rows, fn($r) => strtolower($r['po_status'] ?? '') === 'rejected'));
$open       = $total - $done - $rejected;

// ── Factory list for filter dropdown ─────────────────────────────────────
$factoryList = [];
foreach ($rows as $r) {
    $factory = trim($r['factory_name'] ?? '');
    if ($factory !== '') {
        $factoryList[$factory] = $factory;
    }
}
ksort($factoryList);

// ── Role flags ────────────────────────────────────────────────────────────
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$isAdmin  = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isSuper  = isset($_SESSION['role']) && $_SESSION['role'] === 'super';
$isViewer = isset($_SESSION['role']) && $_SESSION['role'] === 'viewer';
$isUser   = isset($_SESSION['role']) && $_SESSION['role'] === 'user';

$requiresFactoryLock = $isUser;

// ── Resolve factory lock for non-admin users ──────────────────────────────
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
