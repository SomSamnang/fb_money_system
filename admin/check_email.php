<?php
require_once 'auth.php';
require_once "../config/db.php";

header('Content-Type: application/json');

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $exclude_id = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

    $sql = "SELECT id FROM admins WHERE email = ?";
    if ($exclude_id > 0) {
        $sql .= " AND id != ?";
    }

    $stmt = $conn->prepare($sql);
    if ($exclude_id > 0) {
        $stmt->bind_param("si", $email, $exclude_id);
    } else {
        $stmt->bind_param("s", $email);
    }
    
    $stmt->execute();
    $stmt->store_result();
    
    echo json_encode(['status' => ($stmt->num_rows > 0) ? 'taken' : 'available']);
    $stmt->close();
}
?>