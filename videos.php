<?php
require_once "config/db.php";
session_start();

// Fetch User Points if logged in
$user_points = 0;
if (isset($_SESSION['user_id'])) {
    $u_stmt = $conn->prepare("SELECT points FROM users WHERE id=?");
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    if($u_row = $u_res->fetch_assoc()) $user_points = $u_row['points'];
}

// Fetch Active Videos
$videos_res = $conn->query("SELECT * FROM videos WHERE status='active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC");
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
        .video-card { transition: transform 0.2s; border: none; border-radius: 15px; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
        .video-card:hover { transform: translateY(-5px); }
        .thumbnail-placeholder { height: 180px; background: #000; display: flex; align-items: center; justify-content: center; color: #fff; border-radius: 15px 15px 0 0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-facebook me-2"></i>FB Money</a>
            <a href="leaderboard.php" class="nav-link text-white mx-3 fw-bold"><i class="bi bi-trophy me-1"></i>Leaderboard</a>
            <a href="redeem.php" class="nav-link text-warning mx-3 fw-bold"><i class="bi bi-gift me-1"></i>Rewards</a>
            <a href="videos.php" class="nav-link text-danger mx-3 fw-bold"><i class="bi bi-play-btn-fill me-1"></i>Videos</a>
            <a href="referrals.php" class="nav-link text-info mx-3 fw-bold"><i class="bi bi-people me-1"></i>Referrals</a>
            <a href="watch_history.php" class="nav-link text-light mx-3 fw-bold"><i class="bi bi-clock-history me-1"></i>History</a>
            <div class="d-flex align-items-center ms-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
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

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold"><i class="bi bi-youtube text-danger me-2"></i>Watch & Earn</h1>
            <p class="text-muted">Watch videos to earn points instantly.</p>
        </div>

        <div class="row g-4">
            <?php if ($videos_res && $videos_res->num_rows > 0): ?>
                <?php while($video = $videos_res->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card video-card h-100">
                        <div class="thumbnail-placeholder">
                            <i class="bi bi-play-circle display-1"></i>
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title fw-bold text-truncate"><?php echo htmlspecialchars($video['title']); ?></h5>
                            <p class="text-success fw-bold mb-3"><i class="bi bi-coin me-1"></i>Earn <?php echo $video['points_per_view']; ?> Points</p>
                            <p class="text-muted small"><i class="bi bi-clock me-1"></i>Watch for <?php echo $video['duration']; ?> seconds</p>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button class="btn btn-danger w-100 rounded-pill fw-bold" 
                                        onclick="openVideoModal(<?php echo $video['id']; ?>, '<?php echo htmlspecialchars($video['title']); ?>', '<?php echo htmlspecialchars($video['video_link']); ?>', <?php echo $video['duration']; ?>)">
                                    Watch Now
                                </button>
                            <?php else: ?>
                                <a href="login_user.php" class="btn btn-outline-secondary w-100 rounded-pill">Login to Earn</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 text-muted">No videos available to watch right now.</div>
            <?php endif; ?>
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
    </script>
</body>
</html>