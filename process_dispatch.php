<?php
include 'partials/db_connect.php';
include 'workflowhelper.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $po_id = (int)$_POST['po_id'];
    $stage = $_POST['stage'];
    $uid = $_SESSION['user_id'] ?? $_SESSION['id'];
    $uploadDir = 'uploads/dispatch/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if ($stage === 'dispatch') {
        $veh = $_POST['vehicle_number'];
        $temp = $_POST['temp'];
        $dt = $_POST['dispatch_dt'];
        
        $pdf = $uploadDir . time() . "_bill.pdf";
        move_uploaded_file($_FILES['bill_pdf']['tmp_name'], $pdf);
        
        $img = $uploadDir . time() . "_temp.jpg";
        move_uploaded_file($_FILES['temp_photo']['tmp_name'], $img);

        $stmt = $conn->prepare("UPDATE purchase_orders SET po_status='dispatched', dispatch_vehicle_number=?, dispatch_temperature=?, dispatch_time=?, pdf_file_path=?, dispatch_temp_photo=? WHERE id=?");
        $stmt->bind_param("sssssi", $veh, $temp, $dt, $pdf, $img, $po_id);
        if ($stmt->execute()) savePoWorkflow($conn, $po_id, 'DISPATCH', 'dispatched', "Vehicle: $veh, Temp: $temp", $uid);

    } elseif ($stage === 'handover') {
        $outcome = $_POST['outcome'];
        $arr_temp = $_POST['arrival_temp'];
        $reason = $_POST['reason'] ?? '';
        $finalStatus = ($outcome === 'success') ? 'success' : 'handover_rejected';

        $stmt = $conn->prepare("UPDATE purchase_orders SET po_status=?, arrival_temperature=?, arrival_time=NOW(), handover_rejection_reason=? WHERE id=?");
        $stmt->bind_param("sssi", $finalStatus, $arr_temp, $reason, $po_id);
        if ($stmt->execute()) savePoWorkflow($conn, $po_id, 'HANDOVER', $finalStatus, "Outcome: $outcome, Temp: $arr_temp", $uid);
    }
    header("Location: dashboard.php");
}