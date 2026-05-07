<?php
include 'config.php';
include 'workflow_helper.php';
checkLogin();
checkRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$po_id = isset($_POST['po_id']) ? (int)$_POST['po_id'] : 0;
$delivery_schedule_date = !empty($_POST['delivery_schedule_date']) ? $_POST['delivery_schedule_date'] : null;

if ($po_id <= 0) {
    die("Invalid PO ID.");
}

if (empty($delivery_schedule_date)) {
    die("Please select Delivery Schedule Date.");
}

$checkStmt = $conn->prepare("SELECT po_status, delivery_schedule_date FROM purchase_orders WHERE id = ?");
$checkStmt->bind_param("i", $po_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$po = $checkResult->fetch_assoc();

if (!$po) {
    die("PO not found.");
}

if ($po['po_status'] !== 'sent_to_schedule_delivery') {
    die("This PO is not in Sent to Schedule Delivery status.");
}

if (!empty($po['delivery_schedule_date'])) {
    die("Delivery Schedule Date already saved.");
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE purchase_orders 
                        SET delivery_schedule_date = ?, 
                            po_status = 'delivery_date_scheduled'
                        WHERE id = ?");
$stmt->bind_param("si", $delivery_schedule_date, $po_id);
$stmt->execute();

savePoWorkflow(
    $conn,
    $po_id,
    'Delivery Date Scheduled',
    'delivery_date_scheduled',
    'Delivery schedule date set to ' . $delivery_schedule_date,
    $user_id
);

header("Location: po_view.php?id=" . $po_id);
exit();
?>