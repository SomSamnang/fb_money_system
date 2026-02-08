<?php
require_once "config/db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_id'])) {
    $user_id = $_SESSION['user_id'];
    $video_id = (int)$_POST['video_id'];

    // Check if video exists and is active
    $v_stmt = $conn->prepare("SELECT points_per_view, target_views, expires_at, daily_limit FROM videos WHERE id=? AND status='active'");
    $v_stmt->bind_param("i", $video_id);
    $v_stmt->execute();
    $video = $v_stmt->get_result()->fetch_assoc();
    $v_stmt->close();

    if (!$video) {
        echo json_encode(['status' => 'error', 'message' => 'Video not found or inactive']);
        exit;
    }

    // Check expiration
    if ($video['expires_at'] && strtotime($video['expires_at']) < time()) {
        echo json_encode(['status' => 'error', 'message' => 'Campaign expired']);
        exit;
    }

    // Check Daily Limit
    if ($video['daily_limit'] > 0) {
        $today = date('Y-m-d');
        $cnt_daily = $conn->prepare("SELECT COUNT(*) FROM video_views WHERE video_id=? AND DATE(viewed_at) = ?");
        $cnt_daily->bind_param("is", $video_id, $today);
        $cnt_daily->execute();
        $daily_views = $cnt_daily->get_result()->fetch_row()[0];
        $cnt_daily->close();

        if ($daily_views >= $video['daily_limit']) {
            $conn->query("UPDATE videos SET status='paused', paused_by_limit=1 WHERE id=$video_id");
            echo json_encode(['status' => 'error', 'message' => 'Daily limit reached']);
            exit;
        }
    }

    // Check if user already viewed this video
    $check = $conn->prepare("SELECT id FROM video_views WHERE user_id=? AND video_id=?");
    $check->bind_param("ii", $user_id, $video_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Already watched']);
        exit;
    }
    $check->close();

    // Record View
    $conn->query("INSERT INTO video_views (user_id, video_id) VALUES ($user_id, $video_id)");

    // Award Points
    $conn->query("UPDATE users SET points = points + {$video['points_per_view']} WHERE id=$user_id");

    // Check if target reached
    if ($video['target_views'] > 0) {
        $cnt_stmt = $conn->prepare("SELECT COUNT(*) FROM video_views WHERE video_id=?");
        $cnt_stmt->bind_param("i", $video_id);
        $cnt_stmt->execute();
        $total_views = $cnt_stmt->get_result()->fetch_row()[0];
        $cnt_stmt->close();

        if ($total_views >= $video['target_views']) {
            $conn->query("UPDATE videos SET status='completed' WHERE id=$video_id");
        }
    }

    echo json_encode(['status' => 'success', 'points' => $video['points_per_view']]);
}
?>