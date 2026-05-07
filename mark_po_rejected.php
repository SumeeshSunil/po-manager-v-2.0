<?php
include 'config.php';
include 'workflow_helper.php';
checkLogin();
checkRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$po_id = isset($_POST['po_id']) ? (int)$_POST['po_id'] : 0;
$rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';

if ($po_id <= 0) {
    die("Invalid PO ID.");
}

if ($rejection_reason === '') {
    die("Rejection reason is required.");
}

$checkStmt = $conn->prepare("SELECT po_status FROM purchase_orders WHERE id = ?");
$checkStmt->bind_param("i", $po_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$po = $checkResult->fetch_assoc();

if (!$po) {
    die("PO not found.");
}

if ($po['po_status'] !== 'delivery_date_scheduled') {
    die("Only delivery date scheduled POs can be marked as rejected.");
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE purchase_orders 
                        SET po_status = 'rejected', rejection_reason = ?
                        WHERE id = ?");
$stmt->bind_param("si", $rejection_reason, $po_id);
$stmt->execute();

savePoWorkflow(
    $conn,
    $po_id,
    'PO Rejected',
    'rejected',
    'Rejection reason: ' . $rejection_reason,
    $user_id
);

header("Location: dashboard.php");
exit();
?>