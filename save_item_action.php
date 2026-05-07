<?php
include 'config.php';
include 'workflow_helper.php';

checkLogin();
checkRole(['user', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$po_id = isset($_POST['po_id']) ? (int)$_POST['po_id'] : 0;
$expected_delivery_date = trim($_POST['expected_delivery_date'] ?? '');
$deliverable_qtys = $_POST['deliverable_qty'] ?? [];
$short_reason = trim($_POST['short_reason_hidden'] ?? '');

if ($po_id <= 0) {
    die("Invalid PO.");
}

if ($expected_delivery_date === '') {
    die("Expected delivery date is required.");
}

if (empty($deliverable_qtys)) {
    die("Deliverable qty is required.");
}

$checkStmt = $conn->prepare("SELECT po_status, delivery_schedule_date FROM purchase_orders WHERE id = ?");
$checkStmt->bind_param("i", $po_id);
$checkStmt->execute();
$po = $checkStmt->get_result()->fetch_assoc();

if (!$po) {
    die("PO not found.");
}

if (
    $po['po_status'] === 'sent_to_schedule_delivery' ||
    $po['po_status'] === 'done' ||
    !empty($po['delivery_schedule_date'])
) {
    die("This PO cannot be changed.");
}

$conn->begin_transaction();

try {
    $hasShort = false;

    foreach ($deliverable_qtys as $item_id => $deliverable_qty) {
        $item_id = (int)$item_id;
        $deliverable_qty = (int)$deliverable_qty;

        $itemCheck = $conn->prepare("SELECT qty FROM po_items WHERE id = ? AND po_id = ?");
        $itemCheck->bind_param("ii", $item_id, $po_id);
        $itemCheck->execute();
        $item = $itemCheck->get_result()->fetch_assoc();

        if (!$item) {
            throw new Exception("Invalid item found.");
        }

        $poQty = (int)$item['qty'];

        if ($deliverable_qty < 0) {
            throw new Exception("Deliverable qty cannot be negative.");
        }

        if ($deliverable_qty > $poQty) {
            throw new Exception("Deliverable qty cannot be greater than PO qty.");
        }

        if ($deliverable_qty < $poQty) {
            $hasShort = true;
        }
    }

    if ($hasShort && $short_reason === '') {
        throw new Exception("Reason is required for short delivery.");
    }

    foreach ($deliverable_qtys as $item_id => $deliverable_qty) {
        $item_id = (int)$item_id;
        $deliverable_qty = (int)$deliverable_qty;

        $itemCheck = $conn->prepare("SELECT qty FROM po_items WHERE id = ? AND po_id = ?");
        $itemCheck->bind_param("ii", $item_id, $po_id);
        $itemCheck->execute();
        $item = $itemCheck->get_result()->fetch_assoc();

        $poQty = (int)$item['qty'];

        if ($deliverable_qty == 0) {
            $user_status = 'cannot';
        } elseif ($deliverable_qty < $poQty) {
            $user_status = 'partial';
        } else {
            $user_status = 'full';
        }

        $reasonToSave = ($deliverable_qty < $poQty) ? $short_reason : null;
        $updated_by = (int)$_SESSION['user_id'];

        $updateItem = $conn->prepare("
            UPDATE po_items
            SET user_status = ?,
                deliverable_qty = ?,
                expected_delivery_date = ?,
                reason = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ? AND po_id = ?
        ");

        $updateItem->bind_param(
            "sissiii",
            $user_status,
            $deliverable_qty,
            $expected_delivery_date,
            $reasonToSave,
            $updated_by,
            $item_id,
            $po_id
        );

        if (!$updateItem->execute()) {
            throw new Exception("Failed to update item.");
        }
    }

    $updatePO = $conn->prepare("
        UPDATE purchase_orders
        SET expected_delivery_date = ?,
            po_status = 'sent_to_schedule_delivery'
        WHERE id = ?
    ");
    $updatePO->bind_param("si", $expected_delivery_date, $po_id);

    if (!$updatePO->execute()) {
        throw new Exception("Failed to update PO.");
    }

    // ✅ Save workflow history
    savePoWorkflow(
        $conn,
        $po_id,
        'sent_to_schedule_delivery',
        'sent_to_schedule_delivery',
        'User saved expected delivery date and sent PO to schedule delivery',
        $_SESSION['user_id'] ?? null
    );

    $conn->commit();

    header("Location: po_view.php?id=" . $po_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die($e->getMessage());
}
?>