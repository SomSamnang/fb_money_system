<?php
require_once "config/db.php";

// Start Session for visitor tracking
session_start();

// Check Maintenance Mode
$settings_res = $conn->query("SELECT * FROM settings WHERE setting_key IN ('maintenance_mode', 'maintenance_message', 'maintenance_end_time')");
$settings = [];
while($row = $settings_res->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; }
$m_mode = $settings['maintenance_mode'] ?? 'off';
$m_msg = $settings['maintenance_message'] ?? 'We are currently performing scheduled maintenance.<br>We will be back shortly.';
$m_end = $settings['maintenance_end_time'] ?? '';

if ($m_mode === 'on' && !isset($_SESSION['admin'])) {
    $countdown_script = "";
    if ($m_end && strtotime($m_end) > time()) {
        $countdown_script = "<h3 id='countdown' class='text-danger fw-bold mb-4'></h3><script>
        const countDownDate = new Date('$m_end').getTime();
        const x = setInterval(function() {
            const now = new Date().getTime();
            const distance = countDownDate - now;
            if (distance < 0) {
                clearInterval(x);
                document.getElementById('countdown').innerHTML = 'Maintenance ending soon...';
            } else {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                document.getElementById('countdown').innerHTML = days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's ';
            }
        }, 1000);
        </script>";
    }
    die("<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Maintenance</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='d-flex justify-content-center align-items-center vh-100 bg-light'><div class='text-center'><h1 class='display-1 fw-bold text-primary'>🛠️</h1><h2 class='mb-3'>Under Maintenance</h2><p class='lead text-muted mb-4'>$m_msg</p>$countdown_script<a href='admin/login.php' class='btn btn-outline-primary'>Admin Login</a></div></body></html>");
}

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

// Fetch Recent Winners (Redemptions)
$ticker_items = [];
$ticker_res = $conn->query("SELECT u.username, r.name FROM redemptions re JOIN users u ON re.user_id = u.id JOIN rewards r ON re.reward_id = r.id WHERE re.status = 'approved' ORDER BY re.created_at DESC LIMIT 10");
if ($ticker_res) {
    while($row = $ticker_res->fetch_assoc()) {
        $ticker_items[] = "🎉 <strong>" . htmlspecialchars($row['username']) . "</strong> redeemed <strong>" . htmlspecialchars($row['name']) . "</strong>";
    }
}
if (empty($ticker_items)) {
    $ticker_items[] = "🚀 Start earning points today and be our next winner!";
}
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
        .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 5rem 0; margin-bottom: 3rem; clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); }
        .page-card { transition: all 0.3s ease; border: none; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); overflow: hidden; }
        .page-card:hover { transform: translateY(-5px); }
        .btn-action { border-radius: 50px; font-weight: 600; }
        /* Ticker Styles */
        .ticker-container { background: #212529; color: #fff; overflow: hidden; white-space: nowrap; position: relative; height: 40px; display: flex; align-items: center; }
        .ticker-text { display: inline-block; animation: marquee 30s linear infinite; padding-left: 100%; }
        .ticker-item { display: inline-block; padding: 0 2rem; }
        @keyframes marquee { 0% { transform: translate(0, 0); } 100% { transform: translate(-100%, 0); } }
        
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
            <button class="btn btn-outline-light d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list"></i>
            </button>
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

    <!-- Recent Winners Ticker -->
    <div class="ticker-container border-bottom border-secondary">
        <div class="ticker-text">
            <?php foreach($ticker_items as $item): ?>
                <span class="ticker-item"><?php echo $item; ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="container py-4">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
                    <div class="offcanvas-header bg-dark text-white">
                        <h5 class="offcanvas-title" id="sidebarMenuLabel">Menu</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body p-0">
                        <div class="public-sidebar w-100">
                    <div class="sidebar-header">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="mb-3">
                                <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                            <div class="badge bg-warning text-dark shadow-sm mt-2 px-3 py-2 rounded-pill"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</div>
                        <?php else: ?>
                            <i class="bi bi-person-circle display-1 mb-3 d-block opacity-75"></i>
                            <h5 class="fw-bold">Welcome Guest</h5>
                            <p class="small opacity-75 mb-3">Join us to earn rewards!</p>
                            <a href="login_user.php" class="btn btn-light text-primary fw-bold w-100 shadow-sm rounded-pill">Login / Register</a>
                        <?php endif; ?>
                    </div>
                    <div class="sidebar-menu py-3">
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="index.php"><i class="bi bi-house-door-fill"></i> Home</a>
                            <a class="nav-link" href="videos.php"><i class="bi bi-play-btn-fill"></i> Watch & Earn</a>
                            <a class="nav-link" href="redeem.php"><i class="bi bi-gift-fill"></i> Rewards</a>
                            <a class="nav-link" href="leaderboard.php"><i class="bi bi-trophy-fill"></i> Leaderboard</a>
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
                    <div class="p-4 bg-light border-top text-center">
                        <p class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-qr-code me-1"></i> Your Referral Code</p>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm text-center fw-bold text-primary bg-white" value="<?php echo $user_referral_code; ?>" readonly>
                            <button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText('<?php echo $user_referral_code; ?>'); alert('Copied!');"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
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
                        <h1 class="fw-bold display-5 mb-3">Boost Your Engagement</h1>
                        <p class="lead opacity-75">Discover and support amazing Facebook pages.</p>
                    </div>
                </div>

                <!-- Page Cards -->
                <div class="row g-4">
                    <?php if ($pages_res && $pages_res->num_rows > 0): ?>
                        <?php while($page = $pages_res->fetch_assoc()): 
                            $current = $click_counts[$page['name']] ?? 0;
                            $target = (int)$page['target_clicks'];
                            
                            // Skip if target reached
                            if ($target > 0 && $current >= $target) continue;
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card page-card h-100 text-center">
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <div class="d-inline-block p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-2"><i class="bi bi-facebook display-4"></i></div>
                                    </div>
                                    <h5 class="card-title fw-bold text-capitalize mb-3"><?php echo htmlspecialchars($page['name']); ?></h5>
                                    <p class="text-muted small mb-4">Support this page by following or sharing!</p>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="<?php echo htmlspecialchars($page['fb_link']); ?>" target="_blank" 
                                        class="btn btn-primary btn-action shadow-sm" 
                                        onclick="trackClick(this, '<?php echo addslashes($page['name']); ?>', 'follow')">
                                            <i class="bi bi-hand-thumbs-up-fill me-2"></i>Follow Page
                                        </a>
                                        <button class="btn btn-outline-success btn-action shadow-sm" 
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