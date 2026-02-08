<?php
require_once "config/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Info
$user_referral_code = "";
$can_claim_daily = false;
$u_stmt = $conn->prepare("SELECT username, points, referral_code, last_daily_bonus FROM users WHERE id=?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$u_res = $u_stmt->get_result();
$user = $u_res->fetch_assoc();
$user_points = $user['points'];
$user_referral_code = $user['referral_code'];
if($user['last_daily_bonus'] != date('Y-m-d')) $can_claim_daily = true;
$u_stmt->close();


// Fetch Watch History
$sql = "SELECT v.title, v.points_per_view, vv.viewed_at 
        FROM video_views vv 
        JOIN videos v ON vv.video_id = v.id 
        WHERE vv.user_id = ? 
        ORDER BY vv.viewed_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();

$total_watched = $history->num_rows;
$total_earned = 0;

$history_data = [];
while ($row = $history->fetch_assoc()) {
    $total_earned += $row['points_per_view'];
    $history_data[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch History - FB Money System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 5rem 0; margin-bottom: 3rem; clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); }
        .stat-card { border: none; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); transition: all 0.3s ease; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        
        /* Sidebar Styles */
        .public-sidebar { background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid rgba(0,0,0,0.05); }
        .sidebar-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; text-align: center; }
        .sidebar-menu .nav-link { color: #555; padding: 1rem 1.5rem; font-weight: 600; border-left: 4px solid transparent; transition: all 0.3s; display: flex; align-items: center; }
        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active { background: #f8f9fc; color: #764ba2; border-left-color: #764ba2; }
        .sidebar-menu i { width: 25px; font-size: 1.2rem; margin-right: 10px; }
        .hero-section { border-radius: 20px; margin-bottom: 2rem !important; }
        @media (max-width: 991.98px) { 
            .public-sidebar { border-radius: 0; box-shadow: none; border: none; width: 100% !important; } 
        }
        @media (min-width: 992px) { .col-lg-3 .offcanvas-lg { position: sticky; top: 90px; z-index: 10; } }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <button class="btn btn-outline-light d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"><i class="bi bi-list"></i></button>
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-facebook me-2"></i>FB Money</a>
            <a href="leaderboard.php" class="nav-link text-white mx-3 fw-bold"><i class="bi bi-trophy me-1"></i>Leaderboard</a>
            <a href="redeem.php" class="nav-link text-warning mx-3 fw-bold"><i class="bi bi-gift me-1"></i>Rewards</a>
            <a href="videos.php" class="nav-link text-danger mx-3 fw-bold"><i class="bi bi-play-btn-fill me-1"></i>Videos</a>
            <a href="referrals.php" class="nav-link text-info mx-3 fw-bold"><i class="bi bi-people me-1"></i>Referrals</a>
            <a href="watch_history.php" class="nav-link text-light mx-3 fw-bold"><i class="bi bi-clock-history me-1"></i>History</a>
            <div class="d-flex align-items-center ms-auto">
                <?php if($can_claim_daily): ?>
                    <button class="btn btn-warning btn-sm me-3 fw-bold" onclick="claimDailyBonus()"><i class="bi bi-calendar-check-fill me-1"></i>Daily Bonus</button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm me-3" disabled><i class="bi bi-check2-all me-1"></i>Claimed</button>
                <?php endif; ?>
                <span class="text-warning me-3 fw-bold"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</span>
                <span class="text-white me-3">Hi, <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="logout_user.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="sidebarMenu">
                    <div class="offcanvas-header bg-dark text-white"><h5 class="offcanvas-title">Menu</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div>
                    <div class="offcanvas-body p-0">
                        <div class="public-sidebar w-100">
                            <div class="sidebar-header">
                                <div class="mb-3"><div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div></div>
                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                                <div class="badge bg-warning text-dark shadow-sm mt-2 px-3 py-2 rounded-pill"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</div>
                            </div>
                            <div class="sidebar-menu py-3">
                                <nav class="nav flex-column">
                                    <a class="nav-link" href="index.php"><i class="bi bi-house-door-fill"></i> Home</a>
                                    <a class="nav-link" href="videos.php"><i class="bi bi-play-btn-fill"></i> Watch & Earn</a>
                                    <a class="nav-link" href="redeem.php"><i class="bi bi-gift-fill"></i> Rewards</a>
                                    <a class="nav-link" href="leaderboard.php"><i class="bi bi-trophy-fill"></i> Leaderboard</a>
                                    <a class="nav-link" href="referrals.php"><i class="bi bi-people-fill"></i> Referrals</a>
                                    <a class="nav-link active" href="watch_history.php"><i class="bi bi-clock-history"></i> History</a>
                                    <a class="nav-link text-danger" href="logout_user.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                                </nav>
                                <div class="px-4 mt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sidebarDarkToggle" style="cursor: pointer;">
                                        <label class="form-check-label small fw-bold text-muted" for="sidebarDarkToggle"><i class="bi bi-moon-stars-fill me-1"></i> Dark Mode</label>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 bg-light border-top text-center"><p class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-qr-code me-1"></i> Your Referral Code</p><div class="input-group"><input type="text" class="form-control form-control-sm text-center fw-bold text-primary bg-white" value="<?php echo $user_referral_code; ?>" readonly><button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText('<?php echo $user_referral_code; ?>'); alert('Copied!');"><i class="bi bi-clipboard"></i></button></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Hero -->
                <div class="hero-section text-center shadow-lg">
                    <div class="container">
                        <h1 class="fw-bold display-5 mb-3">Watch History</h1>
                        <p class="lead opacity-75">Track your video views and earnings.</p>
                    </div>
                </div>

        <div class="row g-4 mb-5 justify-content-center">
            <div class="col-md-4">
                <div class="card stat-card text-white p-3 text-center" style="background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);">
                    <div class="card-body p-4">
                        <h3 class="display-4 fw-bold"><?php echo $total_watched; ?></h3>
                        <p class="mb-0 opacity-75">Videos Watched</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card text-white p-3 text-center" style="background: linear-gradient(135deg, #2af598 0%, #009efd 100%);">
                    <div class="card-body p-4">
                        <h3 class="display-4 fw-bold"><?php echo $total_earned; ?></h3>
                        <p class="mb-0 opacity-75">Points Earned</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border:none;">
                <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Watched Videos</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">Video Title</th>
                                <th class="py-3">Points Earned</th>
                                <th class="pe-4 py-3 text-end">Date Watched</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($history_data) > 0): ?>
                                <?php foreach($history_data as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">+<?php echo $row['points_per_view']; ?> Pts</span></td>
                                    <td class="pe-4 text-end text-muted small"><?php echo date("M d, Y h:i A", strtotime($row['viewed_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">You haven't watched any videos yet. <a href="videos.php">Start watching now!</a></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function claimDailyBonus() {
        fetch('claim_bonus.php')
        .then(response => response.json())
        .then(data => { alert(data.message); if(data.status === 'success') location.reload(); });
    }
    </script>
</body>
</html>