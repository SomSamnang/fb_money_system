<?php
require_once "config/db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['page']) && isset($_POST['type'])) {
        $page = $_POST['page'];
        $type = $_POST['type'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $points_earned = false;
        
        $can_click = true;

        // Check for duplicate click if user is logged in
        if ($user_id) {
            $check = $conn->prepare("SELECT id FROM clicks WHERE user_id=? AND page=? AND type=?");
            $check->bind_param("iss", $user_id, $page, $type);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $can_click = false; // Already clicked
            }
            $check->close();
        }

        // Check Daily Limit
        if ($can_click) {
            $limit_stmt = $conn->prepare("SELECT id, daily_limit FROM pages WHERE name = ?");
            $limit_stmt->bind_param("s", $page);
            $limit_stmt->execute();
            $limit_res = $limit_stmt->get_result();
            if ($l_row = $limit_res->fetch_assoc()) {
                $page_id = $l_row['id'];
                $daily_limit = $l_row['daily_limit'];
                if ($daily_limit > 0) {
                    $today = date('Y-m-d');
                    $cnt_daily = $conn->prepare("SELECT COUNT(*) FROM clicks WHERE page=? AND DATE(click_date) = ?");
                    $cnt_daily->bind_param("ss", $page, $today);
                    $cnt_daily->execute();
                    $daily_clicks = $cnt_daily->get_result()->fetch_row()[0];
                    $cnt_daily->close();
                    
                    if ($daily_clicks >= $daily_limit) {
                        $can_click = false;
                        // Automatically pause campaign
                        $conn->query("UPDATE pages SET status='paused', paused_by_limit=1 WHERE id=$page_id");
                    }
                }
            }
            $limit_stmt->close();
        }

        if ($can_click) {
            $stmt = $conn->prepare("INSERT INTO clicks (page, type, ip, user_id) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssi", $page, $type, $ip, $user_id);
                $stmt->execute();
                $stmt->close();

                if ($user_id) {
                    $conn->query("UPDATE users SET points = points + 1 WHERE id = $user_id");
                    $points_earned = true;
                }

            // Check if target reached and update status
            $check = $conn->prepare("SELECT id, target_clicks FROM pages WHERE name=? AND status='active' AND target_clicks > 0");
            if($check){
                $check->bind_param("s", $page);
                $check->execute();
                $res = $check->get_result();
                if($p_row = $res->fetch_assoc()){
                    $p_id = $p_row['id'];
                    $target = $p_row['target_clicks'];
                    
                    // Count total clicks for this page
                    $cnt = $conn->prepare("SELECT COUNT(*) FROM clicks WHERE page=?");
                    $cnt->bind_param("s", $page);
                    $cnt->execute();
                    $total = $cnt->get_result()->fetch_row()[0];
                    $cnt->close();

                    if($total >= $target){
                        $conn->query("UPDATE pages SET status='completed' WHERE id=$p_id");
                    }
                }
                $check->close();
            }
            }
        }
        echo json_encode(['status' => 'success', 'points_earned' => $points_earned]);
    }
}
?>