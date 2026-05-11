<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$sid = session_id();
$stmt = $conn->prepare("UPDATE active_sessions SET last_seen = NOW() WHERE session_id = ?");
$stmt->bind_param("s", $sid);
$stmt->execute();

echo "ok";