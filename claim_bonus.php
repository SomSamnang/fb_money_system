<?php
require_once "config/db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$bonus_points = 5; // Points to award daily

// Check last claim
$stmt = $conn->prepare("SELECT last_daily_bonus FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if ($row['last_daily_bonus'] == $today) {
    echo json_encode(['status' => 'error', 'message' => 'Already claimed today']);
    exit;
}

// Award points
$conn->query("UPDATE users SET points = points + $bonus_points, last_daily_bonus = '$today' WHERE id=$user_id");

echo json_encode(['status' => 'success', 'message' => "You earned $bonus_points points!", 'new_points' => $bonus_points]);
?>