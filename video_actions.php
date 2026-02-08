<?php
require_once "config/db.php";
session_start();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$video_id = isset($_REQUEST['video_id']) ? (int)$_REQUEST['video_id'] : 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (!$video_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid video ID']);
    exit;
}

if ($action === 'get_stats') {
    // Get Like Count
    $l_res = $conn->query("SELECT COUNT(*) FROM video_likes WHERE video_id=$video_id");
    $likes = $l_res->fetch_row()[0];

    // Get Comment Count
    $c_res = $conn->query("SELECT COUNT(*) FROM video_comments WHERE video_id=$video_id");
    $comments_count = $c_res->fetch_row()[0];

    // Check if user liked
    $liked = false;
    if ($user_id) {
        $check = $conn->query("SELECT id FROM video_likes WHERE user_id=$user_id AND video_id=$video_id");
        if ($check->num_rows > 0) $liked = true;
    }

    // Get Recent Comments
    $comments = [];
    $com_sql = "SELECT c.comment, c.created_at, u.username FROM video_comments c JOIN users u ON c.user_id = u.id WHERE c.video_id=$video_id ORDER BY c.created_at DESC LIMIT 50";
    $com_res = $conn->query($com_sql);
    while ($row = $com_res->fetch_assoc()) {
        $comments[] = [
            'username' => htmlspecialchars($row['username']),
            'comment' => htmlspecialchars($row['comment']),
            'date' => date('M d, H:i', strtotime($row['created_at']))
        ];
    }

    echo json_encode([
        'status' => 'success',
        'likes' => $likes,
        'comments_count' => $comments_count,
        'liked_by_user' => $liked,
        'comments' => $comments
    ]);
    exit;
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

if ($action === 'toggle_like') {
    $check = $conn->query("SELECT id FROM video_likes WHERE user_id=$user_id AND video_id=$video_id");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM video_likes WHERE user_id=$user_id AND video_id=$video_id");
        $liked = false;
    } else {
        $conn->query("INSERT INTO video_likes (user_id, video_id) VALUES ($user_id, $video_id)");
        $liked = true;
    }
    // Return new count
    $l_res = $conn->query("SELECT COUNT(*) FROM video_likes WHERE video_id=$video_id");
    $likes = $l_res->fetch_row()[0];
    echo json_encode(['status' => 'success', 'liked' => $liked, 'likes' => $likes]);
    exit;
}

if ($action === 'post_comment') {
    $comment = trim($_POST['comment'] ?? '');
    if (empty($comment)) {
        echo json_encode(['status' => 'error', 'message' => 'Comment cannot be empty']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO video_comments (user_id, video_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $video_id, $comment);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>