<?php
require_once 'auth.php';
require_once "../config/db.php";

// Check Admin Role
$stmt = $conn->prepare("SELECT role FROM admins WHERE username=?");
$stmt->bind_param("s", $_SESSION['admin']);
$stmt->execute();
$u_role = $stmt->get_result()->fetch_assoc()['role'] ?? 'editor';

if ($u_role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Log Action
logAction($conn, $_SESSION['admin'], 'Backup Database', 'Downloaded database backup');

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$sqlScript = "-- Database Backup\n";
$sqlScript .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    // Structure
    $sqlScript .= "-- Table structure for table `$table`\n";
    $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
    $row = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
    $sqlScript .= $row[1] . ";\n\n";

    // Data
    $sqlScript .= "-- Dumping data for table `$table`\n";
    $result = $conn->query("SELECT * FROM `$table`");
    $columnCount = $result->field_count;

    while ($row = $result->fetch_row()) {
        $sqlScript .= "INSERT INTO `$table` VALUES(";
        for ($j = 0; $j < $columnCount; $j++) {
            if (isset($row[$j])) {
                $sqlScript .= '"' . $conn->real_escape_string($row[$j]) . '"';
            } else {
                $sqlScript .= 'NULL';
            }
            if ($j < ($columnCount - 1)) {
                $sqlScript .= ',';
            }
        }
        $sqlScript .= ");\n";
    }
    $sqlScript .= "\n";
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Download
$backup_name = "backup_" . date("Y-m-d_H-i-s") . ".sql";
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");
echo $sqlScript;
exit;
?>