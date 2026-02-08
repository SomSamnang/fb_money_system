<?php
$current_page = basename($_SERVER['PHP_SELF']);
$sidebar_pic = isset($profile_pic) ? $profile_pic : "https://via.placeholder.com/150";
$sidebar_login = isset($last_login) ? $last_login : "";
$sidebar_bio = isset($bio) ? $bio : "";

$settings_res = $conn->query("SELECT * FROM settings WHERE setting_key IN ('sidebar_color', 'notification_sound')");
$settings = [];
while($row = $settings_res->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; }

$custom_sb_color = $settings['sidebar_color'] ?? '';
$sb_style = $custom_sb_color ? "background: $custom_sb_color !important;" : "";

// Define Notification Sound URL
$notif_sound = $settings['notification_sound'] ?? '';
$notif_sound_url = $notif_sound ? "uploads/$notif_sound" : "https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3";

// Count unread notifications
$unread_count = 0;
if(isset($_SESSION['admin'])){
    $n_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE username=? AND is_read=0");
    if($n_stmt){
        $n_stmt->bind_param("s", $_SESSION['admin']);
        $n_stmt->execute();
        $n_stmt->bind_result($unread_count);
        $n_stmt->fetch();
        $n_stmt->close();
    }
}

// Count unread support messages (Admins only)
$support_unread_count = 0;
if (isset($user_role) && $user_role === 'admin') {
    $s_res = $conn->query("SELECT COUNT(*) FROM support_messages WHERE status='Open'");
    if ($s_res) $support_unread_count = $s_res->fetch_row()[0];
}
?>
<style>
/* Modern Sidebar Styles */
@media (min-width: 769px) {
    #wrapper.toggled-mini #sidebar-wrapper { width: 80px; transition: width 0.25s ease-out; }
    #wrapper.toggled-mini #sidebar-wrapper .sidebar-heading > div { display: none; }
    #wrapper.toggled-mini #sidebar-wrapper .sidebar-heading img { width: 40px; height: 40px; border-width: 2px !important; }
    #wrapper.toggled-mini #sidebar-wrapper .list-group-item span { display: none; }
    #wrapper.toggled-mini #sidebar-wrapper .list-group-item { text-align: center; padding-left: 0 !important; padding-right: 0 !important; }
    #wrapper.toggled-mini #sidebar-wrapper .list-group-item i { margin-right: 0 !important; font-size: 1.25rem; }
    #wrapper.toggled-mini #sidebar-wrapper .dropdown-toggle::after { display: none; }
    #wrapper.toggled-mini #sidebar-wrapper .sidebar-heading .fw-bold, #wrapper.toggled-mini #sidebar-wrapper .sidebar-heading .small { display: none; }
}
</style>
<div class="border-end shadow-lg" id="sidebar-wrapper" style="<?php echo $sb_style ? $sb_style : 'background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);'; ?>">
    <div class="sidebar-heading text-center position-relative">
        <button class="btn btn-link text-white position-absolute top-0 end-0 d-md-none" id="sidebarClose"><i class="bi bi-x-lg"></i></button>
       <div class="mt-2">FB Money</div>
        <img src="<?php echo $sidebar_pic; ?>" class="rounded-circle mb-2 shadow" width="80" height="80" style="object-fit:cover; border: 3px solid rgba(255,255,255,0.8);">
        
        <div class="fw-bold mt-1"><?php echo htmlspecialchars($_SESSION['admin'] ?? 'User'); ?></div>
        <?php if($sidebar_bio): ?>
        <div class="small text-white-50 fst-italic mt-1" style="font-size: 0.85rem;"><?php echo htmlspecialchars($sidebar_bio); ?></div>
        <?php endif; ?>
        <?php if($sidebar_login): ?>
        <div class="small text-white-50 mt-1" style="font-size: 0.75rem;">Last Login: <?php echo $sidebar_login; ?></div>
        <?php endif; ?>
    </div>
    <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-2 text-warning"></i> <span>Dashboard</span></a>
        <?php $is_clients = ($current_page == 'manage_clients.php' || $current_page == 'request_history.php' || $current_page == 'request_boost.php'); ?>
        <a href="#clientsSubmenu" class="list-group-item list-group-item-action dropdown-toggle" data-bs-toggle="collapse" aria-expanded="<?php echo $is_clients ? 'true' : 'false'; ?>"><i class="bi bi-briefcase-fill me-2 text-warning"></i> <span>Clients</span></a>
        <div class="collapse <?php echo $is_clients ? 'show' : ''; ?>" id="clientsSubmenu">
            <a href="request_boost.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'request_boost.php') ? 'active' : ''; ?>"><i class="bi bi-lightning-charge me-2"></i> <span>Request Boost</span></a>
            <a href="request_history.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'request_history.php') ? 'active' : ''; ?>"><i class="bi bi-clock-history me-2"></i> <span>History</span></a>
        </div>
        <?php $is_pages = ($current_page == 'boost_page.php' || $current_page == 'pages_list.php'); ?>
        <a href="#pagesSubmenu" class="list-group-item list-group-item-action dropdown-toggle" data-bs-toggle="collapse" aria-expanded="<?php echo $is_pages ? 'true' : 'false'; ?>"><i class="bi bi-rocket-takeoff me-2 text-info"></i> <span>Boost Page</span></a>
        <div class="collapse <?php echo $is_pages ? 'show' : ''; ?>" id="pagesSubmenu">
            <a href="boost_page.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'boost_page.php') ? 'active' : ''; ?>"><i class="bi bi-plus-circle me-2"></i> <span>Add Page</span></a>
            <a href="pages_list.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'pages_list.php') ? 'active' : ''; ?>"><i class="bi bi-list-ul me-2"></i> <span>Manage Pages</span></a>
        </div>
        <?php $is_posts = ($current_page == 'boost_post.php' || $current_page == 'posts_list.php'); ?>
        <a href="#postsSubmenu" class="list-group-item list-group-item-action dropdown-toggle" data-bs-toggle="collapse" aria-expanded="<?php echo $is_posts ? 'true' : 'false'; ?>"><i class="bi bi-postcard-heart me-2 text-primary"></i> <span>Boost Post</span></a>
        <div class="collapse <?php echo $is_posts ? 'show' : ''; ?>" id="postsSubmenu">
            <a href="boost_post.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'boost_post.php') ? 'active' : ''; ?>"><i class="bi bi-plus-circle me-2"></i> <span>Add Post</span></a>
            <a href="posts_list.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'posts_list.php') ? 'active' : ''; ?>"><i class="bi bi-list-ul me-2"></i> <span>Manage Posts</span></a>
        </div>
        <?php $is_followers = ($current_page == 'boost_follower.php' || $current_page == 'followers_list.php'); ?>
        <a href="#followersSubmenu" class="list-group-item list-group-item-action dropdown-toggle" data-bs-toggle="collapse" aria-expanded="<?php echo $is_followers ? 'true' : 'false'; ?>"><i class="bi bi-person-plus-fill me-2 text-light"></i> <span>Boost Follower</span></a>
        <div class="collapse <?php echo $is_followers ? 'show' : ''; ?>" id="followersSubmenu">
            <a href="boost_follower.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'boost_follower.php') ? 'active' : ''; ?>"><i class="bi bi-plus-circle me-2"></i> <span>Add Follower</span></a>
            <a href="followers_list.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'followers_list.php') ? 'active' : ''; ?>"><i class="bi bi-list-ul me-2"></i> <span>Manage Followers</span></a>
        </div>
        <?php $is_videos = ($current_page == 'boost_video.php' || $current_page == 'boost_reel.php' || $current_page == 'videos_list.php' || $current_page == 'reels_list.php' || $current_page == 'video_comments.php' || $current_page == 'boost_view.php'); ?>
        <a href="#videosSubmenu" class="list-group-item list-group-item-action dropdown-toggle" data-bs-toggle="collapse" aria-expanded="<?php echo $is_videos ? 'true' : 'false'; ?>"><i class="bi bi-youtube me-2 text-danger"></i> <span>Videos</span></a>
        <div class="collapse <?php echo $is_videos ? 'show' : ''; ?>" id="videosSubmenu">
            <a href="boost_video.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'boost_video.php') ? 'active' : ''; ?>"><i class="bi bi-plus-circle me-2"></i> <span>Add Video</span></a>
            <a href="boost_reel.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'boost_reel.php') ? 'active' : ''; ?>"><i class="bi bi-camera-reels me-2"></i> <span>Boost New Reel</span></a>
            <a href="boost_view.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'boost_view.php') ? 'active' : ''; ?>"><i class="bi bi-eye-fill me-2"></i> <span>Boost View</span></a>
            <a href="videos_list.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'videos_list.php') ? 'active' : ''; ?>"><i class="bi bi-list-ul me-2"></i> <span>Manage Videos</span></a>
            <a href="reels_list.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'reels_list.php') ? 'active' : ''; ?>"><i class="bi bi-collection-play me-2"></i> <span>Manage Reels</span></a>
            <a href="video_comments.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'video_comments.php') ? 'active' : ''; ?>"><i class="bi bi-chat-left-text me-2"></i> <span>Comments</span></a>
        </div>
        <a href="notifications.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?> d-flex justify-content-between align-items-center" title="Notifications">
            <div><i class="bi bi-bell me-2 text-warning"></i> <span>Notifications</span></div>
            <?php if($unread_count > 0): ?>
            <small class="badge bg-danger rounded-pill"><?php echo $unread_count; ?></small>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="bi bi-person-circle me-2 text-light"></i> <span>Profile</span></a>
        <?php if(isset($user_role) && $user_role === 'admin'): ?>
        <?php $is_users = ($current_page == 'users.php' || $current_page == 'register.php' || $current_page == 'banned_users.php' || $current_page == 'public_users.php'); ?>
        <a href="#usersSubmenu" class="list-group-item list-group-item-action dropdown-toggle" data-bs-toggle="collapse" aria-expanded="<?php echo $is_users ? 'true' : 'false'; ?>" title="Users"><i class="bi bi-people me-2 text-info"></i> <span>Users</span></a>
        <div class="collapse <?php echo $is_users ? 'show' : ''; ?>" id="usersSubmenu">
            <a href="users.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" title="Admins List"><i class="bi bi-shield-lock me-2 text-primary"></i> <span>Admins List</span></a>
            <a href="public_users.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'public_users.php') ? 'active' : ''; ?>" title="Public Members"><i class="bi bi-people-fill me-2 text-info"></i> <span>Public Members</span></a>
            <a href="register.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>" title="Add User"><i class="bi bi-person-plus-fill me-2 text-success"></i> <span>Add User</span></a>
            <a href="banned_users.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'banned_users.php') ? 'active' : ''; ?>" title="Banned Users"><i class="bi bi-slash-circle me-2 text-danger"></i> <span>Banned Users</span></a>
        </div>
        <?php endif; ?>
        
        <a href="#rewardsSubmenu" class="list-group-item list-group-item-action dropdown-toggle" data-bs-toggle="collapse" aria-expanded="false"><i class="bi bi-gift me-2 text-success"></i> <span>Rewards</span></a>
        <div class="collapse" id="rewardsSubmenu">
            <a href="rewards.php" class="list-group-item list-group-item-action ps-4 small"><i class="bi bi-plus-circle me-2"></i> <span>Manage Rewards</span></a>
            <a href="redemptions.php" class="list-group-item list-group-item-action ps-4 small"><i class="bi bi-list-check me-2"></i> <span>Redemptions</span></a>
        </div>

        <a href="do_done.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'do_done.php') ? 'active' : ''; ?>"><i class="bi bi-check2-square me-2 text-primary"></i> <span>Do List Done</span></a>
        <a href="reports.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"><i class="bi bi-bar-chart-line me-2 text-light"></i> <span>Reports</span></a>
        <a href="help.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'help.php') ? 'active' : ''; ?>"><i class="bi bi-question-circle me-2 text-warning"></i> <span>Help</span></a>
        <?php if(isset($user_role) && $user_role === 'admin'): ?>
        <a href="support_messages.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'support_messages.php') ? 'active' : ''; ?> d-flex justify-content-between align-items-center" title="Support Inbox">
            <div><i class="bi bi-envelope me-2 text-info"></i> <span>Support Inbox</span></div>
            <?php if($support_unread_count > 0): ?>
            <small class="badge bg-danger rounded-pill support-badge"><?php echo $support_unread_count; ?></small>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        
        <?php 
        $is_system = ($current_page == 'settings.php' || $current_page == 'logs.php' || $current_page == 'health.php' || $current_page == 'server_logs.php'); 
        ?>
        <a href="#systemSubmenu" class="list-group-item list-group-item-action dropdown-toggle" data-bs-toggle="collapse" aria-expanded="<?php echo $is_system ? 'true' : 'false'; ?>"><i class="bi bi-gear-fill me-2 text-secondary"></i> <span>System</span></a>
        <div class="collapse <?php echo $is_system ? 'show' : ''; ?>" id="systemSubmenu">
            <a href="settings.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="bi bi-sliders me-2 text-light"></i> <span>Settings</span></a>
            <a href="logs.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>"><i class="bi bi-journal-text me-2 text-warning"></i> <span>System Logs</span></a>
            <a href="health.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'health.php') ? 'active' : ''; ?>" title="System Health"><i class="bi bi-heart-pulse me-2 text-danger"></i> <span>System Health</span></a>
            <a href="server_logs.php" class="list-group-item list-group-item-action ps-4 small <?php echo ($current_page == 'server_logs.php') ? 'active' : ''; ?>" title="Server Logs"><i class="bi bi-terminal me-2 text-secondary"></i> <span>Server Logs</span></a>
        </div>
        
        <a href="../index.php" target="_blank" class="list-group-item list-group-item-action"><i class="bi bi-globe me-2 text-light"></i> <span>View Site</span></a>
        <a href="logout.php" class="list-group-item list-group-item-action text-warning mt-2"><i class="bi bi-box-arrow-right me-2 text-danger"></i> <span>Logout</span></a>
        
        <div class="list-group-item mt-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="sidebarDarkToggle" style="cursor: pointer;">
                <label class="form-check-label text-white-50 small" for="sidebarDarkToggle"><i class="bi bi-moon-stars me-1"></i> Sidebar Dark Mode</label>
            </div>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="sidebarMiniToggle" style="cursor: pointer;">
                <label class="form-check-label text-white-50 small" for="sidebarMiniToggle"><i class="bi bi-layout-sidebar-inset me-1"></i> Mini Sidebar</label>
            </div>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="globalDarkToggle" style="cursor: pointer;">
                <label class="form-check-label text-white-50 small" for="globalDarkToggle"><i class="bi bi-moon-fill me-1"></i> Dark Mode</label>
            </div>
            <div class="mt-3">
                <label class="form-label text-white-50 small mb-1"><i class="bi bi-volume-up me-1"></i> Alert Volume</label>
                <div class="d-flex align-items-center">
                    <input type="range" class="form-range me-2" id="notifVolume" min="0" max="1" step="0.1">
                    <button class="btn btn-sm btn-link text-white p-0 me-2" id="testVolBtn" title="Test Sound"><i class="bi bi-play-fill"></i></button>
                    <button class="btn btn-sm btn-link text-white p-0" id="muteBtn" title="Mute/Unmute"><i class="bi bi-volume-up-fill"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Global Notification Toast -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;">
  <div id="globalToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <i class="bi bi-bell-fill me-2"></i>
      <strong class="me-auto">New Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body text-dark bg-white" id="globalToastBody"></div>
  </div>
</div>

<script>
const sbToggle = document.getElementById('sidebarDarkToggle');
const sbWrapper = document.getElementById('sidebar-wrapper');
const defaultSbStyle = "<?php echo $sb_style; ?>";
const wrapper = document.getElementById('wrapper');

// Global Dark Mode Logic
const globalDarkToggle = document.getElementById('globalDarkToggle');

function applyDarkMode(isDark) {
    if(isDark) {
        document.body.classList.add('dark-mode');
        if(globalDarkToggle) globalDarkToggle.checked = true;
        const navToggle = document.getElementById('darkModeToggle');
        if(navToggle) navToggle.textContent = '☀️';
    } else {
        document.body.classList.remove('dark-mode');
        if(globalDarkToggle) globalDarkToggle.checked = false;
        const navToggle = document.getElementById('darkModeToggle');
        if(navToggle) navToggle.textContent = '🌙';
    }
}

// Initialize on load
if(localStorage.getItem('darkMode') === 'enabled') { applyDarkMode(true); }

if(globalDarkToggle) {
    globalDarkToggle.addEventListener('change', function() {
        if(this.checked) {
            localStorage.setItem('darkMode', 'enabled');
            applyDarkMode(true);
        } else {
            localStorage.setItem('darkMode', 'disabled');
            applyDarkMode(false);
        }
    });
}

// Sync with Navbar Toggle clicks
document.addEventListener('click', function(e) {
    if(e.target && e.target.id === 'darkModeToggle') {
        setTimeout(() => {
            if(document.body.classList.contains('dark-mode')) { if(globalDarkToggle) globalDarkToggle.checked = true; }
            else { if(globalDarkToggle) globalDarkToggle.checked = false; }
        }, 50);
    }
});

function applySidebarDark() { sbWrapper.style.cssText = "background: #212529 !important; background-image: none !important;"; }
function removeSidebarDark() { sbWrapper.style.cssText = defaultSbStyle; }

if(localStorage.getItem('sidebarDarkForced') === 'enabled') { sbToggle.checked = true; applySidebarDark(); }
sbToggle.addEventListener('change', function() {
    if(this.checked) { localStorage.setItem('sidebarDarkForced', 'enabled'); applySidebarDark(); }
    else { localStorage.setItem('sidebarDarkForced', 'disabled'); removeSidebarDark(); }
});

const miniToggle = document.getElementById('sidebarMiniToggle');
function applyMiniSidebar() { wrapper.classList.add('toggled-mini'); }
function removeMiniSidebar() { wrapper.classList.remove('toggled-mini'); }

if(localStorage.getItem('sidebarMini') === 'enabled') { if(miniToggle) miniToggle.checked = true; applyMiniSidebar(); }
if(miniToggle) {
    miniToggle.addEventListener('change', function() {
        if(this.checked) { localStorage.setItem('sidebarMini', 'enabled'); applyMiniSidebar(); }
        else { localStorage.setItem('sidebarMini', 'disabled'); removeMiniSidebar(); }
    });
}

// Global Notification Sound & Polling
const globalNotifSound = new Audio('<?php echo $notif_sound_url; ?>');
let globalLastUnread = <?php echo $unread_count; ?>;
let globalLastSupport = <?php echo $support_unread_count; ?>;

// Volume Control
const volumeSlider = document.getElementById('notifVolume');
const muteBtn = document.getElementById('muteBtn');
const testVolBtn = document.getElementById('testVolBtn');
let lastVolume = 0.5;

const savedVolume = localStorage.getItem('notifVolume');
if (savedVolume !== null) { 
    globalNotifSound.volume = parseFloat(savedVolume); 
    if(volumeSlider) volumeSlider.value = savedVolume; 
    if(parseFloat(savedVolume) > 0) lastVolume = parseFloat(savedVolume);
} else { 
    globalNotifSound.volume = 0.5; 
    if(volumeSlider) volumeSlider.value = 0.5; 
}

function updateMuteIcon() {
    if(!muteBtn) return;
    if(globalNotifSound.volume == 0) muteBtn.innerHTML = '<i class="bi bi-volume-mute-fill text-danger"></i>';
    else muteBtn.innerHTML = '<i class="bi bi-volume-up-fill"></i>';
}

if(volumeSlider) {
    updateMuteIcon();
    volumeSlider.addEventListener('input', function() {
        globalNotifSound.volume = this.value;
        if(this.value > 0) lastVolume = this.value;
        localStorage.setItem('notifVolume', this.value);
        updateMuteIcon();
    });
}

if(muteBtn) {
    muteBtn.addEventListener('click', function() {
        if(globalNotifSound.volume > 0) {
            globalNotifSound.volume = 0;
            volumeSlider.value = 0;
        } else {
            globalNotifSound.volume = lastVolume;
            volumeSlider.value = lastVolume;
        }
        localStorage.setItem('notifVolume', globalNotifSound.volume);
        updateMuteIcon();
    });
}

if(testVolBtn) {
    testVolBtn.addEventListener('click', function() {
        globalNotifSound.currentTime = 0;
        globalNotifSound.play().catch(e => console.log(e));
    });
}
function checkGlobalNotifications() {
    // Check for test success param on load
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('test_success')) {
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: newUrl}, '', newUrl);
        setTimeout(() => {
            globalNotifSound.currentTime = 0;
            globalNotifSound.play().catch(e => console.log('Auto play failed:', e));
        }, 500);
    }

    fetch('notifications.php?get_unread_count=1')
        .then(response => response.json())
        .then(data => {
            const newCount = data.count;
            const newSupport = data.support_count;
            const badge = document.querySelector('a[href="notifications.php"] .badge');
            
            // Update Badge
            if (badge) {
                if (newCount > 0) {
                    badge.textContent = newCount;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            } else if (newCount > 0) {
                const link = document.querySelector('a[href="notifications.php"]');
                if(link) {
                    const b = document.createElement('small');
                    b.className = 'badge bg-danger rounded-pill';
                    b.textContent = newCount;
                    link.appendChild(b);
                }
            }

            // Update Support Badge
            const supportBadge = document.querySelector('.support-badge');
            const supportLink = document.querySelector('a[href="support_messages.php"]');
            
            if (newSupport !== undefined && supportLink) {
                if (supportBadge) {
                    if (newSupport > 0) {
                        supportBadge.textContent = newSupport;
                        supportBadge.style.display = 'inline-block';
                    } else {
                        supportBadge.style.display = 'none';
                    }
                } else if (newSupport > 0) {
                    const b = document.createElement('small');
                    b.className = 'badge bg-danger rounded-pill support-badge';
                    b.textContent = newSupport;
                    supportLink.appendChild(b);
                }
            }

            // Play Sound if count increased
            if (newCount > globalLastUnread || (newSupport !== undefined && newSupport > globalLastSupport)) {
                globalNotifSound.play().catch(e => console.log('Audio play failed:', e));
                
                // Show Toast
                const toastEl = document.getElementById('globalToast');
                const toastBody = document.getElementById('globalToastBody');
                if (toastEl && toastBody && data.latest_message && newCount > globalLastUnread) {
                    toastBody.innerHTML = '';
                    const msgDiv = document.createElement('div');
                    msgDiv.className = 'mb-2';
                    msgDiv.textContent = data.latest_message;
                    toastBody.appendChild(msgDiv);
                    
                    if (data.latest_id) {
                        const btnDiv = document.createElement('div');
                        btnDiv.className = 'text-end';
                        btnDiv.innerHTML = `<button class="btn btn-sm btn-primary" onclick="markToastRead(${data.latest_id})"><i class="bi bi-check2"></i> Mark as Read</button>`;
                        toastBody.appendChild(btnDiv);
                    }

                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                }
            }
            globalLastUnread = newCount;
            if (newSupport !== undefined) globalLastSupport = newSupport;
        })
        .catch(err => console.log('Notif check failed', err));
}

function markToastRead(id) {
    fetch('notifications.php?read_ajax=' + id)
        .then(() => {
            const toastEl = document.getElementById('globalToast');
            const toast = bootstrap.Toast.getInstance(toastEl);
            if(toast) toast.hide();
            checkGlobalNotifications(); // Refresh badge
        });
}

// Poll every 10 seconds
setInterval(checkGlobalNotifications, 10000);
</script>