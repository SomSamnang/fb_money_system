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
} else { $u_row = []; }
if (!$u_row) $u_row = [];
$profile_pic = !empty($u_row['profile_pic']) ? "uploads/" . $u_row['profile_pic'] : "https://via.placeholder.com/150";
$last_login = !empty($u_row['last_login']) ? date("M d, Y h:i A", strtotime($u_row['last_login'])) : "First Login";
$bio = $u_row['bio'] ?? '';
$user_role = $u_row['role'] ?? 'editor';

$message = "";
$toast_class = "";

// Handle Delete
if (isset($_POST['delete_video'])) {
    $id = (int)$_POST['video_id'];
    $conn->query("DELETE FROM videos WHERE id=$id");
    $conn->query("DELETE FROM video_views WHERE video_id=$id");
    $message = "Video deleted successfully.";
    $toast_class = "bg-danger";
    logAction($conn, $_SESSION['admin'], 'Delete Video', "Deleted video ID: $id");
}

// Handle Status Update
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['video_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE videos SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $message = "Video status updated to $status.";
    $toast_class = "bg-success";
    logAction($conn, $_SESSION['admin'], 'Update Video Status', "Updated video ID: $id to $status");
}

// Fetch Videos
$sql = "SELECT v.*, COUNT(vv.id) as current_views 
        FROM videos v 
        LEFT JOIN video_views vv ON v.id = vv.video_id 
        GROUP BY v.id 
        ORDER BY v.created_at DESC";
$videos = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Videos - FB Money System</title>
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
.card { border:none; border-radius:15px; box-shadow:0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); margin-bottom: 20px; }
body.dark-mode { background: #121212; color: #e0e0e0; }
body.dark-mode .card { background: #1e1e1e; color: #e0e0e0; }
body.dark-mode .navbar { background: #1e1e1e !important; color: #e0e0e0; }
body.dark-mode .table { color: #e0e0e0; }
#wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
@media (max-width: 768px) { #sidebar-wrapper { margin-left: -250px; position: fixed; z-index: 1000; height: 100%; top: 0; left: 0; } #wrapper.toggled #sidebar-wrapper { margin-left: 0; box-shadow: 0 0 15px rgba(0,0,0,0.5); } #page-content-wrapper { width: 100%; min-width: 100%; } #wrapper.toggled #page-content-wrapper::before { content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; } }
</style>
</head>
<body>
<div class="d-flex" id="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom mb-4">
            <div class="container-fluid">
                <button class="btn btn-primary" id="sidebarToggle">☰ Menu</button>
                <span class="navbar-text ms-auto fw-bold text-primary">Manage Videos</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="card p-4">
                <h5 class="mb-3">Boosted Videos List</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Video</th><th>Views / Target</th><th>Reward</th><th>Expires</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if($videos && $videos->num_rows > 0): ?>
                                <?php while($v = $videos->fetch_assoc()): 
                                    $progress = ($v['target_views'] > 0) ? min(100, round(($v['current_views'] / $v['target_views']) * 100)) : 0;
                                    $status_badge = 'bg-secondary';
                                    if ($v['status'] == 'active') $status_badge = 'bg-success';
                                    elseif ($v['status'] == 'completed') $status_badge = 'bg-primary';
                                    elseif ($v['status'] == 'paused') $status_badge = 'bg-warning text-dark';
                                    $expires = $v['expires_at'] ? date("M d, H:i", strtotime($v['expires_at'])) : 'Never';
                                ?>
                                <tr>
                                    <td style="max-width: 250px;"><div class="fw-bold text-truncate" title="<?php echo htmlspecialchars($v['title']); ?>"><?php echo htmlspecialchars($v['title']); ?></div><a href="<?php echo htmlspecialchars($v['video_link']); ?>" target="_blank" class="small text-decoration-none"><i class="bi bi-box-arrow-up-right"></i> View Link</a></td>
                                    <td style="min-width: 150px;"><div class="d-flex justify-content-between small mb-1"><span><?php echo $v['current_views']; ?></span><span><?php echo $v['target_views']; ?></span></div><div class="progress" style="height: 6px;"><div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $progress; ?>%"></div></div><small class="text-muted"><?php echo $progress; ?>%</small></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo $v['points_per_view']; ?> Pts</span></td>
                                    <td class="small text-muted"><?php echo $expires; ?></td>
                                    <td><span class="badge <?php echo $status_badge; ?>"><?php echo ucfirst($v['status']); ?></span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if($v['status'] == 'active'): ?>
                                                <form method="POST"><input type="hidden" name="video_id" value="<?php echo $v['id']; ?>"><input type="hidden" name="status" value="paused"><button type="submit" name="update_status" class="btn btn-sm btn-warning" title="Pause"><i class="bi bi-pause-fill"></i></button></form>
                                            <?php elseif($v['status'] == 'paused'): ?>
                                                <form method="POST"><input type="hidden" name="video_id" value="<?php echo $v['id']; ?>"><input type="hidden" name="status" value="active"><button type="submit" name="update_status" class="btn btn-sm btn-success" title="Resume"><i class="bi bi-play-fill"></i></button></form>
                                            <?php endif; ?>
                                            <form method="POST" onsubmit="return confirm('Delete this video?');"><input type="hidden" name="video_id" value="<?php echo $v['id']; ?>"><button type="submit" name="delete_video" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No videos found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="liveToast" class="toast align-items-center text-white <?php echo $toast_class; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body"><?php echo $message; ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); document.getElementById('wrapper').classList.toggle('toggled'); });
document.getElementById('page-content-wrapper').addEventListener('click', function(e) { if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) { document.getElementById('wrapper').classList.remove('toggled'); } });
document.getElementById('sidebarClose').addEventListener('click', function(e) { e.preventDefault(); document.getElementById('wrapper').classList.remove('toggled'); });
const toggle = document.getElementById('darkModeToggle'); const body = document.body; if(localStorage.getItem('darkMode') === 'enabled'){ body.classList.add('dark-mode'); toggle.textContent = '☀️'; } toggle.addEventListener('click', () => { body.classList.toggle('dark-mode'); localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : 'disabled'); toggle.textContent = body.classList.contains('dark-mode') ? '☀️' : '🌙'; });
<?php if($message): ?>const toastEl = document.getElementById('liveToast'); const toast = new bootstrap.Toast(toastEl); toast.show();<?php endif; ?>
</script>
</body>
</html>