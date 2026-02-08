<?php
require_once "../config/db.php";

echo "<h3>Database Update Status</h3>";

function addColumn($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query("ALTER TABLE $table ADD COLUMN $column $definition")) {
            echo "<div style='color:green'>✔ Successfully added column <b>'$column'</b> to table <b>'$table'</b>.</div>";
        } else {
            echo "<div style='color:red'>✘ Error adding column <b>'$column'</b>: " . $conn->error . "</div>";
        }
    } else {
        echo "<div style='color:blue'>ℹ Column <b>'$column'</b> already exists in table <b>'$table'</b>.</div>";
    }
}

addColumn($conn, 'admins', 'role', "VARCHAR(50) NOT NULL DEFAULT 'editor'");
addColumn($conn, 'admins', 'remember_token', "VARCHAR(255) NULL DEFAULT NULL");
?>