<?php
require_once 'auth.php';
require_once "../config/db.php";

// Fetch user info for sidebar
$u_stmt = $conn->prepare("SELECT profile_pic, last_login, bio, role FROM admins WHERE username=?");
if ($u_stmt) {
    $u_stmt->bind_param("s", $_SESSION['admin']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    $u_row = $u_res->fetch_assoc();
} else {
    $u_row = [];
}
if (!$u_row) $u_row = [];
$profile_pic = !empty($u_row['profile_pic']) ? "uploads/" . $u_row['profile_pic'] : "https://via.placeholder.com/150";
$last_login = !empty($u_row['last_login']) ? date("M d, Y h:i A", strtotime($u_row['last_login'])) : "First Login";
$bio = $u_row['bio'] ?? '';
$user_role = $u_row['role'] ?? 'editor';

// Handle Actions
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND username=?");
    $stmt->bind_param("is", $id, $_SESSION['admin']);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id=? AND username=?");
    $stmt->bind_param("is", $id, $_SESSION['admin']);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE username=?");
    $stmt->bind_param("s", $_SESSION['admin']);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}
if (isset($_POST['clear_all_notifications'])) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE username=?");
    $stmt->bind_param("s", $_SESSION['admin']);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}
if (isset($_POST['create_test_notification'])) {
    $msg = "👋 Hello! This is a sample notification to show you how alerts look in the system. Generated at " . date("h:i A");
    $stmt = $conn->prepare("INSERT INTO notifications (username, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $_SESSION['admin'], $msg);
    $stmt->execute();
    header("Location: notifications.php?test_success=1");
    exit();
}

// Handle Get Unread Count (Global Polling)
if (isset($_GET['get_unread_count'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE username=? AND is_read=0");
    $stmt->bind_param("s", $_SESSION['admin']);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $latest_message = "";
    if ($count > 0) {
        $stmt = $conn->prepare("SELECT message FROM notifications WHERE username=? AND is_read=0 ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("s", $_SESSION['admin']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $latest_message = $row['message'];
        }
        $stmt->close();
    }
    echo json_encode(['count' => $count, 'latest_message' => $latest_message]);
    exit();
}

// Handle AJAX Fetch
if (isset($_GET['fetch_notifications'])) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE username=? ORDER BY created_at DESC");
    $stmt->bind_param("s", $_SESSION['admin']);
    $stmt->execute();
    $notifs = $stmt->get_result();

    if($notifs && $notifs->num_rows > 0){
        while($n = $notifs->fetch_assoc()){
            $unread = $n['is_read'] ? '' : 'unread';
            $msg = nl2br(htmlspecialchars($n['message']));
            $date = date("M d, Y h:i A", strtotime($n['created_at']));
            $readBtn = !$n['is_read'] ? '<a href="?read='.$n['id'].'" class="btn btn-sm btn-outline-primary" title="Mark as Read"><i class="bi bi-check2"></i></a>' : '';
            
            echo '<div class="list-group-item p-3 '.$unread.'">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-3"><div class="mb-1">'.$msg.'</div><small class="text-muted"><i class="bi bi-clock me-1"></i>'.$date.'</small></div>
                    <div class="d-flex gap-2">'.$readBtn.'<a href="?delete='.$n['id'].'" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm(\'Delete this notification?\');"><i class="bi bi-trash"></i></a></div>
                </div>
            </div>';
        }
    } else {
        echo '<div class="text-center py-5 text-muted"><i class="bi bi-bell-slash fs-1 d-block mb-2"></i>No notifications found.</div>';
    }
    exit();
}

// Fetch Notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE username=? ORDER BY created_at DESC");
$stmt->bind_param("s", $_SESSION['admin']);
$stmt->execute();
$notifs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - FB Money System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background:#f8f9fc; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
#wrapper { display: flex; width: 100%; }
#sidebar-wrapper { height: 100vh; position: sticky; top: 0; overflow-y: auto; width: 250px; background: linear-gradient(180deg, #4e73df 10%, #224abe 100%); color: #fff; transition: margin 0.25s ease-out; }
#sidebar-wrapper .sidebar-heading { padding: 1.5rem; font-size: 1.5rem; font-weight: bold; text-align: center; color:rgba(255,255,255,0.9); border-bottom: 1px solid rgba(255,255,255,0.1); }
#sidebar-wrapper .list-group-item { background: transparent; color: rgba(255,255,255,0.8); border: none; padding: 1rem 1.5rem; }
#sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.2); color: #fff; }
#sidebar-wrapper .list-group-item.active { background: rgba(255,255,255,0.3); color: #fff; font-weight: bold; }
#page-content-wrapper { width: 100%; display: flex; flex-direction: column; min-height: 100vh; }
.navbar { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); background: #fff !important; position: sticky; top: 0; z-index: 100; }
.notif-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08); overflow: hidden; }
.notif-header { background: linear-gradient(45deg, #36b9cc, #258391); color: white; padding: 1.5rem; }
.list-group-item.unread { background-color: #f8f9fc; border-left: 4px solid #36b9cc; }
/* Dark Mode */
body.dark-mode { background: #121212; color: #e0e0e0; }
body.dark-mode .notif-card { background: #1e1e1e; color: #e0e0e0; }
body.dark-mode .navbar { background: #1e1e1e !important; color: #e0e0e0; }
body.dark-mode .list-group-item { background-color: #1e1e1e; color: #e0e0e0; border-color: #444; }
body.dark-mode .list-group-item.unread { background-color: #2d2d2d; }
#wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
@media (max-width: 768px) {
    #sidebar-wrapper { margin-left: -250px; position: fixed; z-index: 1000; height: 100%; top: 0; left: 0; }
    #wrapper.toggled #sidebar-wrapper { margin-left: 0; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
    #page-content-wrapper { width: 100%; min-width: 100%; }
    #wrapper.toggled #page-content-wrapper::before { content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
}
</style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom mb-4">
            <div class="container-fluid">
                <button class="btn btn-primary" id="sidebarToggle">☰ Menu</button>
                <span class="navbar-text ms-auto fw-bold text-primary">Notifications</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="card notif-card mb-5">
                <div class="notif-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-bold"><i class="bi bi-bell-fill me-2"></i>Your Notifications</h4>
                        <p class="mb-0 opacity-75 small">Stay updated with the latest alerts and messages.</p>
                    </div>
                    <form method="POST" class="d-flex gap-2 align-items-center">
                        <div class="form-check form-switch mb-0 me-2" title="Auto Refresh (5s)">
                            <input class="form-check-input" type="checkbox" id="autoRefreshNotif">
                        </div>
                        <button type="button" id="testSoundBtn" class="btn btn-light text-warning fw-bold btn-sm shadow-sm"><i class="bi bi-volume-up-fill me-1"></i> Test Sound</button>
                        <button type="submit" name="create_test_notification" class="btn btn-light text-primary fw-bold btn-sm shadow-sm"><i class="bi bi-plus-circle me-1"></i> Test Notif</button>
                        <button type="submit" name="mark_all_read" class="btn btn-light text-info fw-bold btn-sm shadow-sm"><i class="bi bi-check2-all me-1"></i> Mark All Read</button>
                        <button type="submit" name="clear_all_notifications" class="btn btn-light text-danger fw-bold btn-sm shadow-sm" onclick="return confirm('Are you sure you want to delete all notifications?');"><i class="bi bi-trash3-fill me-1"></i> Clear All</button>
                    </form>
                </div>
                <div class="list-group list-group-flush" id="notificationList">
                    <?php if($notifs && $notifs->num_rows > 0): ?>
                        <?php while($n = $notifs->fetch_assoc()): ?>
                        <div class="list-group-item p-3 <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="me-3">
                                    <div class="mb-1"><?php echo nl2br(htmlspecialchars($n['message'])); ?></div>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?php echo date("M d, Y h:i A", strtotime($n['created_at'])); ?></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if(!$n['is_read']): ?>
                                    <a href="?read=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-primary" title="Mark as Read"><i class="bi bi-check2"></i></a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this notification?');"><i class="bi bi-trash"></i></a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted"><i class="bi bi-bell-slash fs-1 d-block mb-2"></i>No notifications found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); document.getElementById('wrapper').classList.toggle('toggled'); });
document.getElementById('page-content-wrapper').addEventListener('click', function(e) { if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) { document.getElementById('wrapper').classList.remove('toggled'); } });
document.getElementById('sidebarClose').addEventListener('click', function(e) { e.preventDefault(); document.getElementById('wrapper').classList.remove('toggled'); });
const toggle = document.getElementById('darkModeToggle'); const body = document.body; if(localStorage.getItem('darkMode') === 'enabled'){ body.classList.add('dark-mode'); toggle.textContent = '☀️'; } toggle.addEventListener('click', () => { body.classList.toggle('dark-mode'); localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : 'disabled'); toggle.textContent = body.classList.contains('dark-mode') ? '☀️' : '🌙'; });

const autoRefreshToggle = document.getElementById('autoRefreshNotif');
let refreshInterval;

function fetchNotifications() {
    fetch('notifications.php?fetch_notifications=1')
        .then(response => response.text())
        .then(data => {
            const list = document.getElementById('notificationList');
            list.innerHTML = data;
        });
}

if(localStorage.getItem('notifAutoRefresh') === 'enabled') {
    if(autoRefreshToggle) { autoRefreshToggle.checked = true; refreshInterval = setInterval(fetchNotifications, 5000); }
}

if(autoRefreshToggle) {
    autoRefreshToggle.addEventListener('change', function() {
        if(this.checked) { localStorage.setItem('notifAutoRefresh', 'enabled'); refreshInterval = setInterval(fetchNotifications, 5000); }
        else { localStorage.setItem('notifAutoRefresh', 'disabled'); clearInterval(refreshInterval); }
    });
}

document.getElementById('testSoundBtn').addEventListener('click', function() {
    if(typeof globalNotifSound !== 'undefined') {
        if(globalNotifSound.volume === 0) {
            alert('Sound is muted! Please increase the volume in the sidebar settings.');
            return;
        }
        globalNotifSound.currentTime = 0;
        globalNotifSound.play().catch(e => {
            console.log('Audio play failed:', e);
            alert('Audio play failed. Check your browser permissions or internet connection.');
        });
    }
});
</script>
</body>
</html>