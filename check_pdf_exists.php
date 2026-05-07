<?php
include 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$pdf_name = trim($_POST['pdf_name'] ?? '');

if ($pdf_name === '') {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'PDF name is required.'
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT id, po_number FROM purchase_orders WHERE pdf_file_name = ? LIMIT 1");
$stmt->bind_param("s", $pdf_name);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'exists' => true,
        'id' => $row['id'],
        'po_number' => $row['po_number']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'exists' => false
    ]);
}

$stmt->close();
$conn->close();
?>