<?php
include 'config.php';
include 'workflow_helper.php';
checkLogin();
checkRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$po_id = isset($_POST['po_id']) ? (int)$_POST['po_id'] : 0;

if ($po_id <= 0) {
    die("Invalid PO ID.");
}

$checkStmt = $conn->prepare("SELECT po_status, delivery_schedule_date FROM purchase_orders WHERE id = ?");
$checkStmt->bind_param("i", $po_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$po = $checkResult->fetch_assoc();

if (!$po) {
    die("PO not found.");
}

if ($po['po_status'] !== 'delivery_date_scheduled') {
    die("Only delivery date scheduled POs can be marked done.");
}

if (empty($po['delivery_schedule_date'])) {
    die("Delivery schedule date is missing.");
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE purchase_orders 
                        SET po_status = 'done'
                        WHERE id = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();

savePoWorkflow(
    $conn,
    $po_id,
    'PO Marked Done',
    'done',
    'Purchase order marked as done',
    $user_id
);

header("Location: dashboard.php");
exit();
?>