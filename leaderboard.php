<?php
require_once "config/db.php";
session_start();

// Fetch User Points if logged in (for navbar display)
$user_points = 0;
$user_referral_code = "";
$can_claim_daily = false;
if (isset($_SESSION['user_id'])) {
    $u_stmt = $conn->prepare("SELECT points, referral_code, last_daily_bonus FROM users WHERE id=?");
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    if($u_row = $u_res->fetch_assoc()) {
        $user_points = $u_row['points'];
        $user_referral_code = $u_row['referral_code'];
        if($u_row['last_daily_bonus'] != date('Y-m-d')) $can_claim_daily = true;
    }
}

// Fetch Top 20 Users
$top_users = $conn->query("SELECT username, points FROM users ORDER BY points DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - FB Money System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .hero-section { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; padding: 5rem 0; margin-bottom: 3rem; clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); }
        .rank-1 { color: #FFD700; text-shadow: 0 0 5px rgba(255, 215, 0, 0.5); }
        .rank-2 { color: #C0C0C0; }
        .rank-3 { color: #CD7F32; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.02); }
        
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
            
            <div class="d-flex align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($can_claim_daily): ?>
                        <button class="btn btn-warning btn-sm me-3 fw-bold" onclick="claimDailyBonus()"><i class="bi bi-calendar-check-fill me-1"></i>Daily Bonus</button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm me-3" disabled><i class="bi bi-check2-all me-1"></i>Claimed</button>
                    <?php endif; ?>
                    <span class="text-warning me-3 fw-bold"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</span>
                    <span class="text-white me-3 d-none d-md-inline">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout_user.php" class="btn btn-outline-light btn-sm me-2">Logout</a>
                <?php else: ?>
                    <a href="login_user.php" class="btn btn-outline-light btn-sm me-2">Login</a>
                    <a href="register_user.php" class="btn btn-primary btn-sm me-2">Register</a>
                <?php endif; ?>
            </div>
            <div class="ms-auto">
                <a href="admin/login.php" class="btn btn-outline-light btn-sm">Admin Login</a>
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
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <div class="mb-3"><div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div></div>
                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                                    <div class="badge bg-warning text-dark shadow-sm mt-2 px-3 py-2 rounded-pill"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</div>
                                <?php else: ?>
                                    <i class="bi bi-person-circle display-1 mb-3 d-block opacity-75"></i><h5 class="fw-bold">Welcome Guest</h5><p class="small opacity-75 mb-3">Join us to earn rewards!</p><a href="login_user.php" class="btn btn-light text-primary fw-bold w-100 shadow-sm rounded-pill">Login / Register</a>
                                <?php endif; ?>
                            </div>
                            <div class="sidebar-menu py-3">
                                <nav class="nav flex-column">
                                    <a class="nav-link" href="index.php"><i class="bi bi-house-door-fill"></i> Home</a>
                                    <a class="nav-link" href="videos.php"><i class="bi bi-play-btn-fill"></i> Watch & Earn</a>
                                    <a class="nav-link" href="redeem.php"><i class="bi bi-gift-fill"></i> Rewards</a>
                                    <a class="nav-link active" href="leaderboard.php"><i class="bi bi-trophy-fill"></i> Leaderboard</a>
                                    <a class="nav-link" href="referrals.php"><i class="bi bi-people-fill"></i> Referrals</a>
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                    <a class="nav-link" href="watch_history.php"><i class="bi bi-clock-history"></i> History</a>
                                    <a class="nav-link text-danger" href="logout_user.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                                    <?php endif; ?>
                                </nav>
                                <div class="px-4 mt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sidebarDarkToggle" style="cursor: pointer;">
                                        <label class="form-check-label small fw-bold text-muted" for="sidebarDarkToggle"><i class="bi bi-moon-stars-fill me-1"></i> Dark Mode</label>
                                    </div>
                                </div>
                            </div>
                            <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="p-4 bg-light border-top text-center"><p class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-qr-code me-1"></i> Your Referral Code</p><div class="input-group"><input type="text" class="form-control form-control-sm text-center fw-bold text-primary bg-white" value="<?php echo $user_referral_code; ?>" readonly><button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText('<?php echo $user_referral_code; ?>'); alert('Copied!');"><i class="bi bi-clipboard"></i></button></div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Hero -->
                <div class="hero-section text-center shadow-lg">
                    <div class="container">
                        <h1 class="fw-bold display-5 mb-3">Leaderboard</h1>
                        <p class="lead opacity-75">Top earners in the community.</p>
                    </div>
                </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border:none;">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-trophy-fill me-2"></i>Top Earners</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle text-center">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 text-uppercase small text-muted">Rank</th>
                                        <th class="py-3 text-uppercase small text-muted">User</th>
                                        <th class="py-3 text-uppercase small text-muted">Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    if ($top_users && $top_users->num_rows > 0):
                                        while($row = $top_users->fetch_assoc()): 
                                            $icon = "";
                                            $bg_class = "";
                                            if($rank == 1) { $icon = '<i class="bi bi-trophy-fill rank-1 fs-3"></i>'; $bg_class="bg-warning bg-opacity-10"; }
                                            elseif($rank == 2) { $icon = '<i class="bi bi-trophy-fill rank-2 fs-4"></i>'; }
                                            elseif($rank == 3) { $icon = '<i class="bi bi-trophy-fill rank-3 fs-4"></i>'; }
                                            else { $icon = '<span class="badge bg-light text-secondary rounded-circle border" style="width: 30px; height: 30px; line-height: 22px;">'.$rank.'</span>'; }
                                    ?>
                                    <tr class="<?php echo $bg_class; ?>">
                                        <td class="py-3"><?php echo $icon; ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td class="fw-bold text-primary"><?php echo number_format($row['points']); ?></td>
                                    </tr>
                                    <?php $rank++; endwhile; ?>
                                    <?php else: ?>
                                    <tr><td colspan="3" class="py-5 text-muted">No users found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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