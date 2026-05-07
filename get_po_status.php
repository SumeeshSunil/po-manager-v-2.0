<?php
include 'config.php';
checkLogin();

header('Content-Type: application/json');

$sql = "SELECT po.*, u.name AS creator_name
        FROM purchase_orders po
        LEFT JOIN users u ON po.created_by = u.id
        ORDER BY po.id DESC";

$result = $conn->query($sql);

$rows = [];

while ($row = $result->fetch_assoc()) {
    $st = strtolower($row['po_status'] ?? '');

    $isAdmin    = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $isSuper    = isset($_SESSION['role']) && $_SESSION['role'] === 'super';
    $isAdminRow = $isAdmin || $isSuper;

    $handoverStatus = strtolower($row['handover_status'] ?? 'pending');

    $row['can_mark_done'] = (
        $isAdminRow &&
        $st === 'delivery_date_scheduled' &&
        $handoverStatus === 'success'
    );

    $row['can_reschedule'] = (
        $isAdminRow &&
        in_array($st, ['delivery_date_scheduled', 'rejected'])
    );

    $rows[] = [
        // ── Core PO fields ──────────────────────────────────────────────
        'id'                        => (int)$row['id'],
        'po_number'                 => $row['po_number']                 ?? '',
        'platform'                  => $row['platform']                  ?? '',
        'factory_name'              => $row['factory_name']              ?? '',
        'release_date'              => $row['release_date']              ?? '',
        'expiry_date'               => $row['expiry_date']               ?? '',
        'buyer_expected_date'       => $row['buyer_expected_date']       ?? '',
        'po_status'                 => $row['po_status']                 ?? '',
        'expected_delivery_date'    => $row['expected_delivery_date']    ?? '',
        'delivery_schedule_date'    => $row['delivery_schedule_date']    ?? '',
        'reschedule_date'           => $row['reschedule_date']           ?? '',
        'rejection_reason'          => $row['rejection_reason']          ?? '',
        'creator_name'              => $row['creator_name']              ?? '',
        'pdf_file_path'             => $row['pdf_file_path']             ?? '',
        'can_mark_done'             => $row['can_mark_done'],
        'can_reschedule'            => $row['can_reschedule'],

        // ── Logistics / Dispatch fields ──────────────────────────────────
        'dispatch_vehicle_number'   => $row['dispatch_vehicle_number']   ?? '',
        'dispatch_time'             => $row['dispatch_time']             ?? '',
        'dispatch_temperature'      => $row['dispatch_temperature']      ?? '',
        'dispatch_temp_photo'       => $row['dispatch_temp_photo']       ?? '',
        'dispatch_bill_copy'        => $row['dispatch_bill_copy']        ?? '',
        'arrival_time'              => $row['arrival_time']              ?? '',
        'arrival_temperature'       => $row['arrival_temperature']       ?? '',
        'arrival_temp_photo'        => $row['arrival_temp_photo']        ?? '',
        'handover_status'           => $row['handover_status']           ?? 'pending',
        'handover_rejection_reason' => $row['handover_rejection_reason'] ?? '',
    ];
}

$total      = count($rows);
$done       = count(array_filter($rows, fn($r) => strtolower($r['po_status']) === 'done'));
$scheduled  = count(array_filter($rows, fn($r) => strtolower($r['po_status']) === 'delivery_date_scheduled'));
$needsSched = count(array_filter($rows, fn($r) => strtolower($r['po_status']) === 'sent_to_schedule_delivery'));
$rejected   = count(array_filter($rows, fn($r) => strtolower($r['po_status']) === 'rejected'));
$open       = $total - $done - $rejected;

echo json_encode([
    'success' => true,
    'rows'    => $rows,
    'stats'   => [
        'total'          => $total,
        'open'           => $open,
        'needs_schedule' => $needsSched,
        'scheduled'      => $scheduled,
        'done'           => $done,
        'rejected'       => $rejected,
    ]
]);
?>