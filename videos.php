<?php
require_once "config/db.php";
session_start();

// Fetch User Info if logged in
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

// Fetch Followed Pages by User
$followed_pages = [];
if (isset($_SESSION['user_id'])) {
    $f_stmt = $conn->prepare("SELECT page FROM clicks WHERE user_id=? AND type='follow'");
    $f_stmt->bind_param("i", $_SESSION['user_id']);
    $f_stmt->execute();
    $f_res = $f_stmt->get_result();
    while($row = $f_res->fetch_assoc()) {
        $followed_pages[$row['page']] = true;
    }
    $f_stmt->close();
}

// Fetch Active Content
$filter = $_GET['filter'] ?? 'all';
$items = [];

// 1. Fetch Videos
if ($filter == 'all' || $filter == 'videos' || $filter == 'reels') {
    $sql = "SELECT id, title, video_link as link, points_per_view as points, duration, platform, 'video' as content_type FROM videos WHERE status='active' AND (expires_at IS NULL OR expires_at > NOW())";
    if ($filter === 'reels') $sql .= " AND platform = 'facebook_reel'";
    elseif ($filter === 'videos') $sql .= " AND platform != 'facebook_reel'";
    $res = $conn->query($sql);
    while($row = $res->fetch_assoc()) $items[] = $row;
}

// 2. Fetch Followers
if ($filter == 'all' || $filter == 'followers') {
    $sql = "SELECT id, name as title, fb_link as link, 5 as points, 10 as duration, 'facebook' as platform, 'follower' as content_type FROM pages WHERE type='follower' AND status='active'";
    $res = $conn->query($sql);
    while($row = $res->fetch_assoc()) $items[] = $row;
}

// Sort by ID DESC
usort($items, function($a, $b) { return $b['id'] - $a['id']; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch & Earn - FB Money System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .hero-section { background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%); color: white; padding: 5rem 0; margin-bottom: 3rem; clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); }
        .video-card { transition: all 0.3s ease; border: none; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); overflow: hidden; }
        .video-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .thumbnail-placeholder { height: 200px; background: #000; display: flex; align-items: center; justify-content: center; color: #fff; position: relative; overflow: hidden; }
        .thumbnail-placeholder::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,0.7)); }
        .play-icon { z-index: 2; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .video-card:hover .play-icon { transform: scale(1.2); color: #ff0844; }
        
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
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($can_claim_daily): ?>
                        <button class="btn btn-warning btn-sm me-3 fw-bold" onclick="claimDailyBonus()"><i class="bi bi-calendar-check-fill me-1"></i>Daily Bonus</button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm me-3" disabled><i class="bi bi-check2-all me-1"></i>Claimed</button>
                    <?php endif; ?>
                    <span class="text-warning me-3 fw-bold"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</span>
                    <span class="text-white me-3">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout_user.php" class="btn btn-outline-light btn-sm">Logout</a>
                <?php else: ?>
                    <a href="login_user.php" class="btn btn-outline-light btn-sm me-2">Login</a>
                    <a href="register_user.php" class="btn btn-primary btn-sm">Register</a>
                <?php endif; ?>
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
                                    <a class="nav-link active" href="videos.php"><i class="bi bi-play-btn-fill"></i> Watch & Earn</a>
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
                        <h1 class="fw-bold display-5 mb-3">Watch & Earn</h1>
                        <p class="lead opacity-75">Watch videos to earn points instantly.</p>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="d-flex justify-content-center mb-4">
                    <a href="videos.php" class="btn <?php echo $filter == 'all' ? 'btn-dark' : 'btn-outline-dark'; ?> rounded-pill me-2 px-4 fw-bold">All</a>
                    <a href="videos.php?filter=videos" class="btn <?php echo $filter == 'videos' ? 'btn-danger' : 'btn-outline-danger'; ?> rounded-pill me-2 px-4 fw-bold"><i class="bi bi-play-btn-fill me-1"></i> Videos</a>
                    <a href="videos.php?filter=reels" class="btn <?php echo $filter == 'reels' ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill me-2 px-4 fw-bold"><i class="bi bi-camera-reels-fill me-1"></i> Reels</a>
                    <a href="videos.php?filter=followers" class="btn <?php echo $filter == 'followers' ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-4 fw-bold"><i class="bi bi-person-plus-fill me-1"></i> Followers</a>
                </div>

        <div class="row g-4">
            <?php if (!empty($items)): ?>
                <?php foreach($items as $item): 
                    $platform = $item['platform'] ?? 'youtube';
                    $icon_class = 'bi-play-circle-fill';
                    if ($platform === 'facebook_reel') $icon_class = 'bi-camera-reels-fill';
                    elseif ($platform === 'facebook') $icon_class = 'bi-facebook';
                    elseif ($platform === 'youtube') $icon_class = 'bi-youtube';
                    
                    if ($item['content_type'] == 'follower') $icon_class = 'bi-person-plus-fill';
                    
                    $btn_text = ($item['content_type'] == 'follower') ? 'Follow Now' : 'Watch Now';
                    $btn_action = ($item['content_type'] == 'follower') ? "openFollowModal({$item['id']}, '" . htmlspecialchars($item['title'], ENT_QUOTES) . "', '{$item['link']}', {$item['points']})" : "openVideoModal({$item['id']}, '" . htmlspecialchars($item['title'], ENT_QUOTES) . "', '" . htmlspecialchars($item['link'], ENT_QUOTES) . "', {$item['duration']})";
                    
                    $is_followed = ($item['content_type'] == 'follower' && isset($followed_pages[$item['title']]));
                    $btn_style = "background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%); border:none;";
                    $btn_disabled = "";

                    if ($is_followed) {
                        $btn_text = '<i class="bi bi-check-circle-fill me-1"></i> Followed';
                        $btn_style = "background: #198754; border:none; opacity: 0.8;";
                        $btn_disabled = "disabled";
                    }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card video-card h-100">
                        <div class="thumbnail-placeholder">
                            <?php if($is_followed): ?>
                                <div class="position-absolute top-0 end-0 m-3 badge bg-success rounded-pill shadow-sm z-3"><i class="bi bi-check-lg"></i> Done</div>
                            <?php endif; ?>
                            <i class="bi <?php echo $icon_class; ?> display-1 play-icon"></i>
                        </div>
                        <div class="card-body p-4 text-center">
                            <h5 class="card-title fw-bold text-truncate mb-3"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <div class="d-flex justify-content-center gap-3 mb-4">
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="bi bi-coin me-1"></i>+<?php echo $item['points']; ?> Pts</span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill"><i class="bi bi-clock me-1"></i><?php echo $item['duration']; ?>s</span>
                            </div>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button class="btn btn-danger w-100 rounded-pill fw-bold shadow-sm py-2" 
                                        style="<?php echo $btn_style; ?>"
                                        onclick="<?php echo $btn_action; ?>" <?php echo $btn_disabled; ?>>
                                    <?php echo $btn_text; ?>
                                </button>
                            <?php else: ?>
                                <a href="login_user.php" class="btn btn-outline-secondary w-100 rounded-pill py-2">Login to Earn</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 text-muted">No content available right now.</div>
            <?php endif; ?>
        </div>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title" id="videoModalTitle"></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="stopVideo()"></button>
          </div>
          <div class="modal-body p-0">
            <div class="ratio ratio-16x9 bg-black">
                <iframe id="videoFrame" src="" allowfullscreen allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
            </div>
            <div class="p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button class="btn btn-outline-primary btn-sm" id="likeBtn" onclick="toggleLike()">
                        <i class="bi bi-hand-thumbs-up"></i> <span id="likeCount">0</span> Likes
                    </button>
                    <span class="text-muted small"><i class="bi bi-chat-dots"></i> <span id="commentCount">0</span> Comments</span>
                </div>
                <div class="comments-section">
                    <div id="commentsList" class="mb-3" style="max-height: 200px; overflow-y: auto;"></div>
                    <div class="input-group">
                        <input type="text" id="commentInput" class="form-control form-control-sm" placeholder="Write a comment...">
                        <button class="btn btn-primary btn-sm" onclick="postComment()">Post</button>
                    </div>
                </div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <div class="fw-bold text-danger" id="timerDisplay"></div>
            <div>
                <a href="#" id="viewOnPlatformBtn" target="_blank" class="btn btn-primary" style="display: none;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> View on Platform
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopVideo()">Close</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Follow Modal -->
    <div class="modal fade" id="followModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Follow to Earn</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center p-4">
            <i class="bi bi-facebook display-1 text-primary mb-3"></i>
            <p class="lead">Click the button below to follow the page.</p>
            <p class="small text-muted">A new tab will open. Follow the page, then return here to claim your points.</p>
            <a href="#" id="followLinkBtn" target="_blank" class="btn btn-primary btn-lg rounded-pill px-5 mb-3" onclick="startFollowTimer()"><i class="bi bi-hand-thumbs-up-fill me-2"></i>Follow Page</a>
            <div id="followTimerDisplay" class="fw-bold text-muted mt-2"></div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let timerInterval;
    let currentVideoId;

    function openVideoModal(id, title, link, duration) {
        currentVideoId = id;
        document.getElementById('videoModalTitle').textContent = title;
        const separator = link.includes('?') ? '&' : '?';
        document.getElementById('videoFrame').src = link + separator + "autoplay=1"; // Auto-play
        
        // Reconstruct original link for external viewing
        let originalLink = '#';
        if (link.includes('youtube.com/embed/')) {
            originalLink = link.split('?')[0].replace('embed/', 'watch?v=');
        } else if (link.includes('facebook.com/plugins/video.php')) {
            try {
                const urlParams = new URLSearchParams(new URL(link).search);
                if (urlParams.has('href')) {
                    originalLink = urlParams.get('href');
                }
            } catch (e) {
                console.error("Could not parse Facebook URL", e);
            }
        } else {
            originalLink = link; // For 'other' platforms
        }

        const viewOnPlatformBtn = document.getElementById('viewOnPlatformBtn');
        if (originalLink !== '#') {
            viewOnPlatformBtn.href = originalLink;
            viewOnPlatformBtn.style.display = 'inline-block';
        } else {
            viewOnPlatformBtn.style.display = 'none';
        }

        // Reset Interactions
        document.getElementById('likeBtn').className = 'btn btn-outline-primary btn-sm';
        document.getElementById('likeCount').textContent = '0';
        document.getElementById('commentCount').textContent = '0';
        document.getElementById('commentsList').innerHTML = '<div class="text-center text-muted small">Loading comments...</div>';
        
        loadVideoStats(id);
        
        const modal = new bootstrap.Modal(document.getElementById('videoModal'));
        modal.show();

        let timeLeft = duration;
        const display = document.getElementById('timerDisplay');
        display.textContent = `Reward in ${timeLeft}s...`;
        display.className = "fw-bold text-danger";

        clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            timeLeft--;
            display.textContent = `Reward in ${timeLeft}s...`;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                display.textContent = "Claiming Reward...";
                display.className = "fw-bold text-success";
                claimReward(id);
            }
        }, 1000);
    }

    let followId;
    let followPageName;
    function openFollowModal(id, title, link, points) {
        followId = id;
        followPageName = title;
        document.getElementById('followLinkBtn').href = link;
        document.getElementById('followTimerDisplay').textContent = "";
        document.getElementById('followTimerDisplay').className = "fw-bold text-muted mt-2";
        new bootstrap.Modal(document.getElementById('followModal')).show();
    }

    function startFollowTimer() {
        // Track Click
        const formData = new FormData();
        formData.append('page', followPageName);
        formData.append('type', 'follow');
        fetch('track_click.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .catch(e => console.error(e));

        let timeLeft = 10; // 10 seconds to verify/return
        const display = document.getElementById('followTimerDisplay');
        display.textContent = `Verifying in ${timeLeft}s...`;
        
        clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            timeLeft--;
            display.textContent = `Verifying in ${timeLeft}s...`;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                display.textContent = "Points Added!";
                display.className = "fw-bold text-success mt-2";
                setTimeout(() => location.reload(), 1000);
            }
        }, 1000);
    }

    function stopVideo() {
        clearInterval(timerInterval);
        document.getElementById('videoFrame').src = "";
    }

    function claimReward(videoId) {
        fetch('track_view.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'video_id=' + videoId
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('timerDisplay').textContent = "Reward Claimed! + " + data.points + " Points";
                setTimeout(() => location.reload(), 1500);
            } else {
                document.getElementById('timerDisplay').textContent = data.message;
                document.getElementById('timerDisplay').className = "fw-bold text-warning";
            }
        });
    }

    function loadVideoStats(id) {
        fetch('video_actions.php?action=get_stats&video_id=' + id)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('likeCount').textContent = data.likes;
                document.getElementById('commentCount').textContent = data.comments_count;
                
                const likeBtn = document.getElementById('likeBtn');
                if(data.liked_by_user) {
                    likeBtn.className = 'btn btn-primary btn-sm';
                    likeBtn.innerHTML = '<i class="bi bi-hand-thumbs-up-fill"></i> <span id="likeCount">' + data.likes + '</span> Likes';
                } else {
                    likeBtn.className = 'btn btn-outline-primary btn-sm';
                    likeBtn.innerHTML = '<i class="bi bi-hand-thumbs-up"></i> <span id="likeCount">' + data.likes + '</span> Likes';
                }

                const list = document.getElementById('commentsList');
                list.innerHTML = '';
                if(data.comments.length > 0) {
                    data.comments.forEach(c => {
                        list.innerHTML += `
                            <div class="mb-2 border-bottom pb-1">
                                <div class="d-flex justify-content-between">
                                    <strong class="small text-primary">${c.username}</strong>
                                    <span class="text-muted small" style="font-size:0.7rem">${c.date}</span>
                                </div>
                                <div class="small text-dark">${c.comment}</div>
                            </div>`;
                    });
                } else {
                    list.innerHTML = '<div class="text-center text-muted small">No comments yet. Be the first!</div>';
                }
            }
        });
    }

    function toggleLike() {
        if(!currentVideoId) return;
        fetch('video_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=toggle_like&video_id=' + currentVideoId
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                loadVideoStats(currentVideoId);
            } else if(data.message === 'Login required') {
                alert('Please login to like videos.');
            }
        });
    }

    function postComment() {
        const input = document.getElementById('commentInput');
        const comment = input.value.trim();
        if(!comment || !currentVideoId) return;

        fetch('video_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=post_comment&video_id=' + currentVideoId + '&comment=' + encodeURIComponent(comment)
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                input.value = '';
                loadVideoStats(currentVideoId);
            } else if(data.message === 'Login required') {
                alert('Please login to comment.');
            } else {
                alert(data.message);
            }
        });
    }

    function claimDailyBonus() {
        fetch('claim_bonus.php')
        .then(response => response.json())
        .then(data => { alert(data.message); if(data.status === 'success') location.reload(); });
    }
    </script>
</body>
</html>