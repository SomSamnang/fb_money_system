<?php
require_once "../config/db.php";

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM admins LIKE 'role'");

if ($result && $result->num_rows == 0) {
    // Add the column
    if ($conn->query("ALTER TABLE admins ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'editor'")) {
        echo "Successfully added 'role' column to 'admins' table.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'role' already exists.";
}
?>