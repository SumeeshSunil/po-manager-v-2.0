<?php
/**
 * dispatch_action.php
 * Handles POST requests from the Dispatch Supervisor modals:
 *   action=save_dispatch     → vehicle no, dispatch time, temp, temp photo, bill copy
 *   action=save_arrival      → arrival time, arrival temp, arrival temp photo
 *   action=handover_success  → mark delivery success (admin can then mark Done)
 *   action=handover_reject   → mark delivery rejected with reason
 */

ob_start();
include 'config.php';
include_once 'workflow_helper.php';
ob_end_clean();

header('Content-Type: application/json');

checkLogin();

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$role          = $_SESSION['role'] ?? '';

$allowed = in_array($role, ['dispatch_supervisor', 'admin', 'super']);
if (!$allowed) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$po_id  = (int)($_POST['po_id'] ?? 0);

if (!$po_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// ── Helper: upload a file ─────────────────────────────────────────────────────
function uploadFile(string $fieldName, int $po_id, string $suffix): ?string
{
    if (empty($_FILES[$fieldName]['tmp_name'])) return null;

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in the form.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $msg = $uploadErrors[$file['error']] ?? 'Unknown upload error (code '.$file['error'].').';
        throw new RuntimeException("Upload error for '$fieldName': $msg");
    }

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    if (!in_array($ext, $allowed)) {
        throw new RuntimeException("Invalid file type '.$ext' for '$fieldName'. Allowed: jpg, jpeg, png, gif, webp, pdf.");
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException("File '$fieldName' exceeds the 10 MB size limit.");
    }

    $dir = 'uploads/dispatch/';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) {
            throw new RuntimeException("Server could not create upload directory '$dir'. Check permissions.");
        }
    }

    if (!is_writable($dir)) {
        throw new RuntimeException("Upload directory '$dir' is not writable. Check server permissions.");
    }

    $name = 'po_' . $po_id . '_' . $suffix . '_' . time() . '.' . $ext;
    $dest = $dir . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("Failed to move uploaded file for '$fieldName' to '$dest'.");
    }

    return $dest;
}

// ── Fetch current PO ──────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM purchase_orders WHERE id = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po) {
    echo json_encode(['success' => false, 'error' => 'PO not found']);
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: save_dispatch
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'save_dispatch') {
    $vehicleNo   = trim($_POST['vehicle_number'] ?? '');
    $dispatchDt  = trim($_POST['dispatch_time'] ?? '');
    $temperature = trim($_POST['dispatch_temperature'] ?? '');

    if (!$vehicleNo || !$dispatchDt || !$temperature) {
        echo json_encode(['success' => false, 'error' => 'Vehicle number, dispatch time and temperature are required.']);
        exit;
    }

    try {
        $tempPhoto = uploadFile('dispatch_temp_photo', $po_id, 'disp_temp');
        $billCopy  = uploadFile('dispatch_bill_copy',  $po_id, 'bill');
    } catch (RuntimeException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

    $sets   = "dispatch_vehicle_number = ?, dispatch_time = ?, dispatch_temperature = ?";
    $params = [$vehicleNo, $dispatchDt, $temperature];
    $types  = "sss";

    if ($tempPhoto) { $sets .= ", dispatch_temp_photo = ?"; $params[] = $tempPhoto; $types .= "s"; }
    if ($billCopy)  { $sets .= ", dispatch_bill_copy = ?";  $params[] = $billCopy;  $types .= "s"; }

    $params[] = $po_id;
    $types   .= "i";

    $upd = $conn->prepare("UPDATE purchase_orders SET $sets WHERE id = ?");
    $upd->bind_param($types, ...$params);
    $upd->execute();
    $upd->close();

    // Build note — embed file paths so workflow history can always find them,
    // even after a reschedule clears the purchase_orders columns.
    $noteParts = ["Vehicle: $vehicleNo", "Temp: $temperature", "Dispatched: $dispatchDt"];
    if ($tempPhoto) $noteParts[] = "temp_photo_path:$tempPhoto";
    if ($billCopy)  $noteParts[] = "bill_copy_path:$billCopy";

    savePoWorkflow($conn, $po_id, 'dispatch_recorded',
        'dispatch_details_saved',
        implode(' | ', $noteParts),
        $currentUserId);

    echo json_encode(['success' => true, 'message' => 'Dispatch details saved successfully.']);
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: save_arrival
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'save_arrival') {
    $arrivalDt   = trim($_POST['arrival_time'] ?? '');
    $arrivalTemp = trim($_POST['arrival_temperature'] ?? '');

    if (!$arrivalDt || !$arrivalTemp) {
        echo json_encode(['success' => false, 'error' => 'Arrival time and temperature are required.']);
        exit;
    }

    try {
        $arrivalTempPhoto = uploadFile('arrival_temp_photo', $po_id, 'arr_temp');
    } catch (RuntimeException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

    $sets   = "arrival_time = ?, arrival_temperature = ?";
    $params = [$arrivalDt, $arrivalTemp];
    $types  = "ss";

    if ($arrivalTempPhoto) { $sets .= ", arrival_temp_photo = ?"; $params[] = $arrivalTempPhoto; $types .= "s"; }

    $params[] = $po_id;
    $types   .= "i";

    $upd = $conn->prepare("UPDATE purchase_orders SET $sets WHERE id = ?");
    $upd->bind_param($types, ...$params);
    $upd->execute();
    $upd->close();

    // Embed file path in note so history page can always retrieve it.
    $noteParts = ["Arrival Temp: $arrivalTemp", "Arrived: $arrivalDt"];
    if ($arrivalTempPhoto) $noteParts[] = "arrival_photo_path:$arrivalTempPhoto";

    savePoWorkflow($conn, $po_id, 'arrival_recorded',
        'arrival_details_saved',
        implode(' | ', $noteParts),
        $currentUserId);

    echo json_encode(['success' => true, 'message' => 'Arrival details saved successfully.']);
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: handover_success
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'handover_success') {
    $upd = $conn->prepare("UPDATE purchase_orders SET handover_status = 'success' WHERE id = ?");
    $upd->bind_param("i", $po_id);
    $upd->execute();
    $upd->close();

    savePoWorkflow($conn, $po_id, 'handover_success',
        'handover_success',
        'Dispatch supervisor marked delivery as successful. Awaiting admin confirmation.',
        $currentUserId);

    echo json_encode(['success' => true, 'message' => 'Delivery marked as successful. Admin will confirm.']);
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: handover_reject
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'handover_reject') {
    $reason = trim($_POST['handover_rejection_reason'] ?? '');
    if (!$reason) {
        echo json_encode(['success' => false, 'error' => 'Rejection reason is required.']);
        exit;
    }

    $upd = $conn->prepare("UPDATE purchase_orders SET handover_status = 'rejected', handover_rejection_reason = ? WHERE id = ?");
    $upd->bind_param("si", $reason, $po_id);
    $upd->execute();
    $upd->close();

    savePoWorkflow($conn, $po_id, 'handover_rejected',
        'handover_rejected',
        "Reason: $reason",
        $currentUserId);

    echo json_encode(['success' => true, 'message' => 'Delivery marked as rejected.']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit;