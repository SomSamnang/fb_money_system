<?php
// This script resets the status of pages that were paused due to hitting their daily limit.
// It should be run once a day at midnight via a Cron Job.

if (php_sapi_name() !== 'cli') {
    die("Access Denied: This script can only be run from the command line.");
}

require_once __DIR__ . "/../config/db.php";

// Update pages: Set status to 'active' and reset the paused_by_limit flag
// Only for pages that were specifically paused by the limit logic
$sql = "UPDATE pages SET status = 'active', paused_by_limit = 0 WHERE paused_by_limit = 1";

// Update videos as well
$conn->query("UPDATE videos SET status = 'active', paused_by_limit = 0 WHERE paused_by_limit = 1");

if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    echo "Success: Resumed $affected campaigns that hit their daily limit.\n";
    
    // Optional: Log this action to system logs if you want to see it in the admin panel
    // Note: We use 'System' as username since this is automated
    $conn->query("INSERT INTO system_logs (username, action, details, ip_address) VALUES ('System', 'Cron Job', 'Resumed $affected campaigns (Daily Limit Reset)', '127.0.0.1')");
} else {
    echo "Error: " . $conn->error . "\n";
}
?>