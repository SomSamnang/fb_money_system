<?php
require_once "config/db.php";

// Start Session for visitor tracking
session_start();

// Log Visitor (Once per session)
if (!isset($_SESSION['visited'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $country = "Unknown"; // Placeholder for GeoIP logic
    $stmt = $conn->prepare("INSERT INTO visitors (ip_address, country, visit_date) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ss", $ip, $country);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['visited'] = true;
}

// Fetch Click Counts for Progress/Filtering
$click_counts = [];
$c_res = $conn->query("SELECT page, COUNT(*) as total FROM clicks GROUP BY page");
if ($c_res) {
    while($row = $c_res->fetch_assoc()){
        $click_counts[$row['page']] = $row['total'];
    }
}

// Fetch User Points if logged in
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
        // Generate code for existing users if missing
        if (empty($user_referral_code)) {
            $user_referral_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $conn->query("UPDATE users SET referral_code='$user_referral_code' WHERE id=".$_SESSION['user_id']);
        }
    }
}

// Fetch Active Pages
$pages_res = $conn->query("SELECT * FROM pages WHERE status='active' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boosted Pages - FB Money System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .hero-section { background: linear-gradient(180deg, #4e73df 10%, #224abe 100%); color: white; padding: 3rem 0; margin-bottom: 2rem; }
        .page-card { transition: transform 0.2s; border: none; border-radius: 15px; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
        .page-card:hover { transform: translateY(-5px); }
        .btn-action { border-radius: 50px; font-weight: 600; }
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
                    <?php if($can_claim_daily): ?>
                        <button class="btn btn-warning btn-sm me-3 fw-bold" onclick="claimDailyBonus()" id="dailyBonusBtn"><i class="bi bi-calendar-check-fill me-1"></i>Daily Bonus</button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm me-3" disabled><i class="bi bi-check2-all me-1"></i>Claimed</button>
                    <?php endif; ?>
                    <button class="btn btn-outline-info btn-sm me-3" data-bs-toggle="modal" data-bs-target="#referralModal"><i class="bi bi-people-fill me-1"></i>Invite</button>
                    <span class="text-warning me-3 fw-bold"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</span>
                    <span class="text-white me-3">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
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

    <!-- Hero -->
    <div class="hero-section text-center">
        <div class="container">
            <h1 class="fw-bold display-5">Boost Your Engagement</h1>
            <p class="lead opacity-75">Discover and support amazing Facebook pages.</p>
        </div>
    </div>

    <!-- Content -->
    <div class="container mb-5">
        <div class="row g-4">
            <?php if ($pages_res && $pages_res->num_rows > 0): ?>
                <?php while($page = $pages_res->fetch_assoc()): 
                    $current = $click_counts[$page['name']] ?? 0;
                    $target = (int)$page['target_clicks'];
                    
                    // Skip if target reached
                    if ($target > 0 && $current >= $target) continue;
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card page-card h-100 p-3 text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="bi bi-facebook text-primary display-4"></i>
                            </div>
                            <h5 class="card-title fw-bold text-capitalize mb-3"><?php echo htmlspecialchars($page['name']); ?></h5>
                            <p class="text-muted small mb-4">Support this page by following or sharing!</p>
                            
                            <div class="d-grid gap-2">
                                <a href="<?php echo htmlspecialchars($page['fb_link']); ?>" target="_blank" 
                                   class="btn btn-primary btn-action" 
                                   onclick="trackClick(this, '<?php echo addslashes($page['name']); ?>', 'follow')">
                                    <i class="bi bi-hand-thumbs-up-fill me-2"></i>Follow Page
                                </a>
                                <button class="btn btn-outline-success btn-action" 
                                        onclick="sharePage(this, '<?php echo htmlspecialchars($page['fb_link']); ?>', '<?php echo addslashes($page['name']); ?>')">
                                    <i class="bi bi-share-fill me-2"></i>Share Page
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h3 class="text-muted">No pages available to boost right now.</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Referral Modal -->
    <div class="modal fade" id="referralModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title fw-bold"><i class="bi bi-gift-fill me-2"></i>Invite & Earn</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center p-4">
            <p class="lead">Share your referral code and earn <strong>10 points</strong> for every friend who joins!</p>
            <div class="bg-light p-3 rounded border mb-3">
                <h2 class="fw-bold text-primary mb-0" style="letter-spacing: 2px;"><?php echo htmlspecialchars($user_referral_code); ?></h2>
            </div>
            <p class="text-muted small">Or share this link:</p>
            <?php 
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
            $path = dirname($_SERVER['PHP_SELF']);
            $path = ($path == '/' || $path == '\\') ? '' : str_replace('\\', '/', $path);
            ?>
            <input type="text" class="form-control text-center small" value="<?php echo $protocol . "://" . $_SERVER['HTTP_HOST'] . $path . "/register_user.php?ref=" . $user_referral_code; ?>" readonly onclick="this.select()">
          </div>
        </div>
      </div>
    </div>

    <script>
    function trackClick(btn, pageName, type) {
        if (btn.classList.contains('disabled')) return;
        
        // Store original content to revert if needed
        const originalContent = btn.innerHTML;
        
        // Disable button and show verifying state
        btn.classList.add('disabled');
        let countdown = 10; // 10 seconds verification time
        
        btn.innerHTML = `<i class="bi bi-hourglass-split me-2"></i>Verifying (${countdown}s)`;
        
        const interval = setInterval(() => {
            countdown--;
            if (countdown > 0) {
                btn.innerHTML = `<i class="bi bi-hourglass-split me-2"></i>Verifying (${countdown}s)`;
            } else {
                clearInterval(interval);
                btn.innerHTML = `<i class="bi bi-arrow-repeat me-2"></i>Checking...`;
                submitClick(btn, pageName, type, originalContent);
            }
        }, 1000);
    }

    function submitClick(btn, pageName, type, originalContent) {
        const formData = new FormData();
        formData.append('page', pageName);
        formData.append('type', type);

        fetch('track_click.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.points_earned) {
                btn.classList.remove('btn-primary', 'btn-outline-success');
                btn.classList.add('btn-success');
                btn.innerHTML = `<i class="bi bi-check-lg me-2"></i>Earned!`;
                alert("You earned 1 point!");
                location.reload(); // Refresh to show new points
            }
        })
        .catch(err => console.error('Tracking error:', err));
    }

    function sharePage(btn, url, pageName) {
        window.open("https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(url), "_blank");
        trackClick(btn, pageName, 'share');
    }

    function claimDailyBonus() {
        fetch('claim_bonus.php')
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(err => console.error('Error:', err));
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>