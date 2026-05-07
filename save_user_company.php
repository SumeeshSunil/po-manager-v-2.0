<?php
session_start();
include 'partials/header.php'; // or however you include $conn

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['company'])) {
    $company = trim($_POST['company']);
    $userId  = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

    if ($userId && $company) {
        // Save to session
        $_SESSION['user_company'] = $company;

        // Save to database
        $stmt = $conn->prepare("UPDATE users SET company = ? WHERE id = ?");
        $stmt->bind_param("si", $company, $userId);
        $stmt->execute();
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}