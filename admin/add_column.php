<?php
require_once "../config/db.php";

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM admins LIKE 'remember_token'");

if ($result && $result->num_rows == 0) {
    // Add the column
    if ($conn->query("ALTER TABLE admins ADD COLUMN remember_token VARCHAR(255) NULL DEFAULT NULL")) {
        echo "Successfully added 'remember_token' column to 'admins' table.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'remember_token' already exists.";
}
?>