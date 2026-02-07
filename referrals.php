<?php
require_once "config/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Info
$u_stmt = $conn->prepare("SELECT username, points, referral_code FROM users WHERE id=?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$u_res = $u_stmt->get_result();
$user = $u_res->fetch_assoc();
$u_stmt->close();

$user_points = $user['points'];
$referral_code = $user['referral_code'];

// Fetch Referrals
$r_stmt = $conn->prepare("SELECT username, created_at FROM users WHERE referred_by=? ORDER BY created_at DESC");
$r_stmt->bind_param("i", $user_id);
$r_stmt->execute();
$referrals = $r_stmt->get_result();

$total_referrals = $referrals->num_rows;
$earned_points = $total_referrals * 10; // Assuming 10 points per referral
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Referrals - FB Money System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-facebook me-2"></i>FB Money</a>
            <a href="leaderboard.php" class="nav-link text-white mx-3 fw-bold"><i class="bi bi-trophy me-1"></i>Leaderboard</a>
            <a href="redeem.php" class="nav-link text-warning mx-3 fw-bold"><i class="bi bi-gift me-1"></i>Rewards</a>
            <a href="videos.php" class="nav-link text-danger mx-3 fw-bold"><i class="bi bi-play-btn-fill me-1"></i>Videos</a>
            <a href="referrals.php" class="nav-link text-info mx-3 fw-bold"><i class="bi bi-people me-1"></i>Referrals</a>
            <a href="watch_history.php" class="nav-link text-light mx-3 fw-bold"><i class="bi bi-clock-history me-1"></i>History</a>
            <div class="d-flex align-items-center ms-auto">
                <span class="text-warning me-3 fw-bold"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</span>
                <span class="text-white me-3">Hi, <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="logout_user.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>Referral History</h1>
            <p class="text-muted">Track your invites and earnings.</p>
        </div>

        <div class="row g-4 mb-5 justify-content-center">
            <div class="col-md-4">
                <div class="card stat-card bg-primary text-white p-3 text-center">
                    <div class="card-body">
                        <h3 class="display-4 fw-bold"><?php echo $total_referrals; ?></h3>
                        <p class="mb-0 opacity-75">Total Friends Invited</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-success text-white p-3 text-center">
                    <div class="card-body">
                        <h3 class="display-4 fw-bold"><?php echo $earned_points; ?></h3>
                        <p class="mb-0 opacity-75">Points Earned</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white p-4 border-bottom-0">
                <h5 class="fw-bold mb-0">Referred Users</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">User</th>
                                <th class="py-3">Joined Date</th>
                                <th class="pe-4 py-3 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($total_referrals > 0): ?>
                                <?php while($row = $referrals->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="text-muted"><?php echo date("M d, Y h:i A", strtotime($row['created_at'])); ?></td>
                                    <td class="pe-4 text-end"><span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Active</span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">You haven't referred anyone yet. Share your code: <strong><?php echo htmlspecialchars($referral_code); ?></strong></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>