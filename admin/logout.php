<?php
require_once "../config/db.php";
session_start();

if (isset($_SESSION['admin'])) {
    // Clear remember me token from database
    $stmt = $conn->prepare("UPDATE admins SET remember_token = NULL WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['admin']);
        $stmt->execute();
        $stmt->close();
    }
}

// Clear remember me cookie
setcookie('remember_me', '', time() - 3600, "/admin");

session_destroy();
header("Location: login.php");
exit();
?>
