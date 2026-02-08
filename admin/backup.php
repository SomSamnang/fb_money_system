<?php
require_once 'auth.php';
require_once "../config/db.php";

// Increase execution time and memory limit for large databases
set_time_limit(0);
ini_set('memory_limit', '1024M');

// Check Admin Role
$stmt = $conn->prepare("SELECT role FROM admins WHERE username=?");
$stmt->bind_param("s", $_SESSION['admin']);
$stmt->execute();
$u_role = $stmt->get_result()->fetch_assoc()['role'] ?? 'editor';

if ($u_role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Define backup directory and file
$backup_dir = dirname(__DIR__) . '/backups';
$save_to_server = true;

// Try to create directory if it doesn't exist
if (!is_dir($backup_dir)) {
    if (!@mkdir($backup_dir, 0777, true)) {
        $save_to_server = false;
    }
} elseif (!is_writable($backup_dir)) {
    // Directory exists but is not writable
    $save_to_server = false;
}

$backup_name = "backup_" . date("Y-m-d_H-i-s") . ".sql";
$backup_file = $backup_dir . '/' . $backup_name;
$handle = null;

if ($save_to_server) {
    $handle = @fopen($backup_file, 'w');
    if (!$handle) {
        $save_to_server = false;
    }
}

// If saving to server failed (permissions), stream directly to browser
if (!$save_to_server) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    $handle = fopen('php://output', 'w');
}

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

fwrite($handle, "-- Database Backup\n");
fwrite($handle, "-- Generated: " . date("Y-m-d H:i:s") . "\n\n");
fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

foreach ($tables as $table) {
    // Structure
    fwrite($handle, "-- Table structure for table `$table`\n");
    fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
    $row = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
    fwrite($handle, $row[1] . ";\n\n");

    // Data
    fwrite($handle, "-- Dumping data for table `$table`\n");
    $result = $conn->query("SELECT * FROM `$table`");
    $columnCount = $result->field_count;

    while ($row = $result->fetch_row()) {
        fwrite($handle, "INSERT INTO `$table` VALUES(");
        for ($j = 0; $j < $columnCount; $j++) {
            if (isset($row[$j])) {
                fwrite($handle, '"' . $conn->real_escape_string($row[$j]) . '"');
            } else {
                fwrite($handle, 'NULL');
            }
            if ($j < ($columnCount - 1)) {
                fwrite($handle, ',');
            }
        }
        fwrite($handle, ");\n");
    }
    fwrite($handle, "\n");
}

fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");

if ($save_to_server) {
    fclose($handle);
    // Log Action
    logAction($conn, $_SESSION['admin'], 'Backup Database', "Created and downloaded backup: $backup_name");

    // Download the file
    if (file_exists($backup_file)) {
        // Clear any previous output to prevent file corruption
        if (ob_get_level()) ob_end_clean();

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backup_file));
        readfile($backup_file);
        exit;
    } else {
        die("Error: Backup file was not created.");
    }
} else {
    fclose($handle);
    exit;
}
?>