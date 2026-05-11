<?php
include 'config.php';

// Remove active session record before destroying
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sid = session_id();
if ($sid) {
    $stmt = $conn->prepare("DELETE FROM active_sessions WHERE session_id = ?");
    $stmt->bind_param("s", $sid);
    $stmt->execute();
}

session_destroy();
header("Location: login.php");
exit();
?>