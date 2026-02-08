<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fb_money_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Database Schema Setup ---

// Helper function to check and add columns safely
if (!function_exists('checkAndAddColumn')) {
    function checkAndAddColumn($conn, $table, $col, $def) {
        $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE $table ADD COLUMN $col $def");
        }
    }
}

// 1. Admins Table Updates
checkAndAddColumn($conn, 'admins', 'role', "VARCHAR(50) NOT NULL DEFAULT 'editor'");
checkAndAddColumn($conn, 'admins', 'remember_token', "VARCHAR(255) NULL DEFAULT NULL");
checkAndAddColumn($conn, 'admins', 'is_banned', "TINYINT(1) DEFAULT 0");
checkAndAddColumn($conn, 'admins', 'ban_reason', "VARCHAR(255) NULL DEFAULT NULL");
checkAndAddColumn($conn, 'admins', 'last_active', "TIMESTAMP NULL DEFAULT NULL");
checkAndAddColumn($conn, 'admins', 'reset_token', "VARCHAR(255) NULL DEFAULT NULL");
checkAndAddColumn($conn, 'admins', 'reset_expires', "DATETIME NULL DEFAULT NULL");
checkAndAddColumn($conn, 'admins', 'profile_pic', "VARCHAR(255) NULL DEFAULT NULL");
checkAndAddColumn($conn, 'admins', 'bio', "TEXT NULL DEFAULT NULL");
checkAndAddColumn($conn, 'admins', 'last_login', "DATETIME NULL DEFAULT NULL");

// 2. Clicks Table
$conn->query("CREATE TABLE IF NOT EXISTS clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    ip VARCHAR(50) NOT NULL,
    click_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
checkAndAddColumn($conn, 'clicks', 'click_date', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// 3. Visitors Table
$conn->query("CREATE TABLE IF NOT EXISTS visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(50),
    country VARCHAR(100),
    visit_date DATETIME
)");

// 4. Todos Table
$conn->query("CREATE TABLE IF NOT EXISTS todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    task VARCHAR(255) NOT NULL,
    status ENUM('pending', 'done') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
checkAndAddColumn($conn, 'todos', 'priority', "ENUM('High', 'Medium', 'Low') DEFAULT 'Medium'");
checkAndAddColumn($conn, 'todos', 'due_date', "DATE NULL DEFAULT NULL");
checkAndAddColumn($conn, 'todos', 'position', "INT DEFAULT 0");

// 5. Task Comments & Support Messages & Notifications
$conn->query("CREATE TABLE IF NOT EXISTS task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY, task_id INT NOT NULL, username VARCHAR(50) NOT NULL, comment TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(task_id)
)");
$conn->query("CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) NOT NULL, subject VARCHAR(255) NOT NULL, message TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, status ENUM('Open', 'Resolved') DEFAULT 'Open'
)");
checkAndAddColumn($conn, 'support_messages', 'status', "ENUM('Open', 'Resolved') DEFAULT 'Open'");

$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) NOT NULL, message TEXT NOT NULL, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS server_metrics ( id INT AUTO_INCREMENT PRIMARY KEY, load_val FLOAT NOT NULL, recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP )");
$conn->query("CREATE TABLE IF NOT EXISTS settings ( setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT )");
$conn->query("CREATE TABLE IF NOT EXISTS pages ( id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, fb_link TEXT NOT NULL )");
checkAndAddColumn($conn, 'pages', 'target_clicks', "INT DEFAULT 0");
checkAndAddColumn($conn, 'pages', 'daily_limit', "INT DEFAULT 0");
checkAndAddColumn($conn, 'pages', 'type', "ENUM('page', 'follower', 'post') DEFAULT 'page'");
// Ensure 'post' is added to the ENUM list if the column already exists
$conn->query("ALTER TABLE pages MODIFY COLUMN type ENUM('page', 'follower', 'post') DEFAULT 'page'");
checkAndAddColumn($conn, 'pages', 'paused_by_limit', "TINYINT(1) DEFAULT 0");
checkAndAddColumn($conn, 'pages', 'status', "ENUM('active', 'completed', 'paused') DEFAULT 'active'");
checkAndAddColumn($conn, 'pages', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Public Users Table
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
checkAndAddColumn($conn, 'clicks', 'user_id', "INT NULL");
checkAndAddColumn($conn, 'users', 'referral_code', "VARCHAR(20) UNIQUE DEFAULT NULL");
checkAndAddColumn($conn, 'users', 'referred_by', "INT DEFAULT NULL");

// Rewards System
$conn->query("CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    points_cost INT NOT NULL,
    image VARCHAR(255),
    stock INT DEFAULT -1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
checkAndAddColumn($conn, 'users', 'last_daily_bonus', "DATE DEFAULT NULL");

// Video Boosting System
$conn->query("CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    video_link TEXT NOT NULL,
    target_views INT DEFAULT 0,
    points_per_view INT DEFAULT 1,
    duration INT DEFAULT 30,
    status ENUM('active', 'completed', 'paused') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS video_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
checkAndAddColumn($conn, 'videos', 'expires_at', "DATETIME NULL DEFAULT NULL");
checkAndAddColumn($conn, 'videos', 'platform', "VARCHAR(50) DEFAULT 'youtube'");
checkAndAddColumn($conn, 'videos', 'daily_limit', "INT DEFAULT 0");
checkAndAddColumn($conn, 'videos', 'paused_by_limit', "TINYINT(1) DEFAULT 0");

// Video Interactions
$conn->query("CREATE TABLE IF NOT EXISTS video_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, video_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS video_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
?>
