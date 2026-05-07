<?php
include 'config.php';
include 'workflow_helper.php';
checkLogin();
checkRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$po_id = isset($_POST['po_id']) ? (int)$_POST['po_id'] : 0;
$reschedule_date = isset($_POST['reschedule_date']) ? trim($_POST['reschedule_date']) : '';

if ($po_id <= 0) {
    die("Invalid PO ID.");
}

if ($reschedule_date === '') {
    die("Reschedule date is required.");
}

$checkStmt = $conn->prepare("SELECT po_status FROM purchase_orders WHERE id = ?");
$checkStmt->bind_param("i", $po_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$po = $checkResult->fetch_assoc();

if (!$po) {
    die("PO not found.");
}

if (!in_array($po['po_status'], ['delivery_date_scheduled', 'rejected'])) {
    die("Only scheduled or rejected POs can be rescheduled.");
}

$user_id = $_SESSION['user_id'];

// Update status + dates AND reset all logistics fields so the
// dispatch supervisor starts a fresh cycle for this delivery.
$stmt = $conn->prepare("UPDATE purchase_orders
                        SET
                            po_status                 = 'delivery_date_scheduled',
                            delivery_schedule_date    = ?,
                            reschedule_date           = ?,

                            dispatch_vehicle_number   = NULL,
                            dispatch_time             = NULL,
                            dispatch_temperature      = NULL,
                            dispatch_temp_photo       = NULL,
                            dispatch_bill_copy        = NULL,
                            arrival_time              = NULL,
                            arrival_temperature       = NULL,
                            arrival_temp_photo        = NULL,
                            handover_status           = 'pending',
                            handover_rejection_reason = NULL

                        WHERE id = ?");
$stmt->bind_param("ssi", $reschedule_date, $reschedule_date, $po_id);
$stmt->execute();

savePoWorkflow(
    $conn,
    $po_id,
    'PO Rescheduled',
    'delivery_date_scheduled',
    'Delivery rescheduled to: ' . $reschedule_date . ' — logistics data reset for new cycle.',
    $user_id
);

header("Location: dashboard.php");
exit();
?>