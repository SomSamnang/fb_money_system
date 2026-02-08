<?php
require_once "config/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$msg_type = "";

// Fetch User Info
$user_referral_code = "";
$can_claim_daily = false;
$u_stmt = $conn->prepare("SELECT points, referral_code, last_daily_bonus FROM users WHERE id=?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$u_row = $u_stmt->get_result()->fetch_assoc();
$user_points = $u_row['points'];
$user_referral_code = $u_row['referral_code'];
if($u_row['last_daily_bonus'] != date('Y-m-d')) $can_claim_daily = true;
$u_stmt->close();

// Handle Redemption
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_reward'])) {
    $reward_id = (int)$_POST['reward_id'];
    
    // Fetch Reward Details
    $r_stmt = $conn->prepare("SELECT * FROM rewards WHERE id=?");
    $r_stmt->bind_param("i", $reward_id);
    $r_stmt->execute();
    $reward = $r_stmt->get_result()->fetch_assoc();
    $r_stmt->close();

    if ($reward) {
        if ($user_points >= $reward['points_cost']) {
            if ($reward['stock'] == -1 || $reward['stock'] > 0) {
                // Deduct Points
                $conn->query("UPDATE users SET points = points - {$reward['points_cost']} WHERE id=$user_id");
                
                // Create Redemption Record
                $ins = $conn->prepare("INSERT INTO redemptions (user_id, reward_id) VALUES (?, ?)");
                $ins->bind_param("ii", $user_id, $reward_id);
                $ins->execute();
                $ins->close();

                // Decrease Stock if not infinite
                if ($reward['stock'] > 0) {
                    $conn->query("UPDATE rewards SET stock = stock - 1 WHERE id=$reward_id");
                }

                $message = "Redemption successful! Your request is pending approval.";
                $msg_type = "alert-success";
                $user_points -= $reward['points_cost']; // Update local variable
            } else {
                $message = "Sorry, this item is out of stock.";
                $msg_type = "alert-danger";
            }
        } else {
            $message = "You do not have enough points.";
            $msg_type = "alert-danger";
        }
    }
}

// Fetch Rewards
$rewards = $conn->query("SELECT * FROM rewards ORDER BY points_cost ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Rewards - FB Money</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .hero-section { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); color: white; padding: 5rem 0; margin-bottom: 3rem; clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); }
        .reward-card { transition: all 0.3s ease; border: none; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); overflow: hidden; }
        .reward-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .reward-img { height: 200px; object-fit: cover; width: 100%; }
        
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
                <span class="text-white me-3">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
                                <div class="mb-3"><div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div></div>
                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                                <div class="badge bg-warning text-dark shadow-sm mt-2 px-3 py-2 rounded-pill"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</div>
                            </div>
                            <div class="sidebar-menu py-3">
                                <nav class="nav flex-column">
                                    <a class="nav-link" href="index.php"><i class="bi bi-house-door-fill"></i> Home</a>
                                    <a class="nav-link" href="videos.php"><i class="bi bi-play-btn-fill"></i> Watch & Earn</a>
                                    <a class="nav-link active" href="redeem.php"><i class="bi bi-gift-fill"></i> Rewards</a>
                                    <a class="nav-link" href="leaderboard.php"><i class="bi bi-trophy-fill"></i> Leaderboard</a>
                                    <a class="nav-link" href="referrals.php"><i class="bi bi-people-fill"></i> Referrals</a>
                                    <a class="nav-link" href="watch_history.php"><i class="bi bi-clock-history"></i> History</a>
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
                        <h1 class="fw-bold display-5 mb-3">Redeem Your Points</h1>
                        <p class="lead opacity-75">Exchange your hard-earned points for amazing rewards.</p>
                    </div>
                </div>

        <?php if($message): ?><div class="alert <?php echo $msg_type; ?> text-center mb-4"><?php echo $message; ?></div><?php endif; ?>

        <div class="row g-4">
            <?php if($rewards && $rewards->num_rows > 0): ?>
                <?php while($r = $rewards->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card reward-card h-100">
                        <?php if($r['image']): ?>
                            <img src="admin/uploads/<?php echo htmlspecialchars($r['image']); ?>" class="reward-img" alt="<?php echo htmlspecialchars($r['name']); ?>">
                        <?php else: ?>
                            <div class="reward-img d-flex align-items-center justify-content-center bg-light text-muted"><i class="bi bi-gift display-1"></i></div>
                        <?php endif; ?>
                        <div class="card-body p-4 text-center d-flex flex-column">
                            <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($r['name']); ?></h5>
                            <p class="card-text text-muted small flex-grow-1 mb-3"><?php echo htmlspecialchars($r['description']); ?></p>
                            <div class="mb-4"><span class="badge bg-warning text-dark fs-5 px-3 py-2 rounded-pill shadow-sm"><i class="bi bi-coin me-1"></i><?php echo number_format($r['points_cost']); ?> Pts</span></div>
                            
                            <form method="POST">
                                <input type="hidden" name="reward_id" value="<?php echo $r['id']; ?>">
                                <?php if($user_points >= $r['points_cost'] && ($r['stock'] == -1 || $r['stock'] > 0)): ?>
                                    <button type="submit" name="redeem_reward" class="btn btn-success w-100 rounded-pill fw-bold shadow-sm py-2" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border:none;" onclick="return confirm('Redeem this reward for <?php echo $r['points_cost']; ?> points?');">Redeem Now</button>
                                <?php elseif($r['stock'] == 0): ?>
                                    <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold py-2" disabled>Out of Stock</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary w-100 rounded-pill fw-bold py-2" disabled>Not Enough Points</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-5">No rewards available at the moment.</div>
            <?php endif; ?>
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