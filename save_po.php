<?php
include 'config.php';
include 'workflow_helper.php';

checkLogin();
checkRole(['admin']);

$po_number          = trim($_POST['po_number']);
$release_date       = $_POST['release_date'];
$expiry_date        = $_POST['expiry_date'];
$platform           = trim($_POST['platform']);
$factory_name       = trim($_POST['factory_name']);
$buyer_expected_date = !empty($_POST['buyer_expected_date']) ? $_POST['buyer_expected_date'] : null;
$created_by         = $_SESSION['user_id'];

$pdf_file_name = null;
$pdf_file_path = null;

if (isset($_FILES['po_pdf']) && $_FILES['po_pdf']['error'] == 0) {
    $uploadDir = "uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = basename($_FILES['po_pdf']['name']);
    $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($fileExt == "pdf") {
        $newFileName = time() . "_" . preg_replace("/[^A-Za-z0-9_\-.]/", "_", $originalName);
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['po_pdf']['tmp_name'], $targetPath)) {
            $pdf_file_name = $originalName;
            $pdf_file_path = $targetPath;
        } else {
            die("Failed to upload PDF file.");
        }
    } else {
        die("Only PDF files are allowed.");
    }
}

$stmt = $conn->prepare("INSERT INTO purchase_orders 
    (po_number, release_date, expiry_date, platform, factory_name, buyer_expected_date, pdf_file_name, pdf_file_path, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "ssssssssi",
    $po_number,
    $release_date,
    $expiry_date,
    $platform,
    $factory_name,
    $buyer_expected_date,
    $pdf_file_name,
    $pdf_file_path,
    $created_by
);

if ($stmt->execute()) {
    $po_id = $conn->insert_id;

    // ✅ Save PO created workflow history
    savePoWorkflow(
        $conn,
        $po_id,
        'created',
        'created',
        'Purchase Order created',
        $created_by
    );

    $item_codes = $_POST['item_code'];
    $item_descriptions = $_POST['item_description'];
    $qtys = $_POST['qty'];

    $itemStmt = $conn->prepare("INSERT INTO po_items (po_id, item_code, item_description, qty) VALUES (?, ?, ?, ?)");

    for ($i = 0; $i < count($item_codes); $i++) {
        $item_code = trim($item_codes[$i]);
        $item_description = trim($item_descriptions[$i]);
        $qty = (int)$qtys[$i];

        $itemStmt->bind_param("issi", $po_id, $item_code, $item_description, $qty);
        $itemStmt->execute();
    }

    header("Location: po_view.php?id=" . $po_id);
    exit();
} else {
    echo "Error: " . $conn->error;
}
?>