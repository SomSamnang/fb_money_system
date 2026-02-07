<?php
if (!function_exists('logAction')) {
    function logAction($conn, $username, $action, $details = '') {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("INSERT INTO system_logs (username, action, details, ip_address) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $username, $action, $details, $ip);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>