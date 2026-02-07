<?php
require_once "config/db.php";
session_start();

// Fetch User Points if logged in (for navbar display)
$user_points = 0;
if (isset($_SESSION['user_id'])) {
    $u_stmt = $conn->prepare("SELECT points FROM users WHERE id=?");
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    if($u_row = $u_res->fetch_assoc()) $user_points = $u_row['points'];
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
        .rank-1 { color: #FFD700; text-shadow: 0 0 5px rgba(255, 215, 0, 0.5); }
        .rank-2 { color: #C0C0C0; }
        .rank-3 { color: #CD7F32; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.02); }
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
            
            <div class="d-flex align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
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

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-primary text-white p-4 text-center border-0" style="background: linear-gradient(45deg, #4e73df, #224abe);">
                        <h2 class="fw-bold mb-1"><i class="bi bi-trophy-fill me-2"></i>Leaderboard</h2>
                        <p class="mb-0 opacity-75">Top earners in the community</p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>