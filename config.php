<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "po_manager";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function checkRole($roles = []) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
        die("Access denied");
    }
}
?>