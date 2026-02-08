<?php
require_once 'auth.php';
require_once "../config/db.php";

// Check if user is admin
$stmt = $conn->prepare("SELECT role FROM admins WHERE username=?");
$stmt->bind_param("s", $_SESSION['admin']);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (($row['role'] ?? 'editor') !== 'admin') {
    die("Access Denied: You do not have permission to view PHP configuration.");
}

phpinfo();
?>