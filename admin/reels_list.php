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

// Handle Update
if(isset($_POST['update_video'])){
    $id = (int)$_POST['id'];
    $daily_limit = (int)$_POST['daily_limit'];
    $status = $_POST['status'];
    
    $paused_sql = "";
    if ($status === 'active') {
        $paused_sql = ", paused_by_limit=0";
    }

    $stmt = $conn->prepare("UPDATE videos SET daily_limit=?, status=? $paused_sql WHERE id=?");
    $stmt->bind_param("isi", $daily_limit, $status, $id);
    
    if($stmt->execute()){
        $message = "Reel updated successfully!";
        $toast_class = "bg-success";
        logAction($conn, $_SESSION['admin'], 'Update Reel', "Updated reel ID: $id");
    } else {
        $message = "Error updating reel.";
        $toast_class = "bg-danger";
    }
    $stmt->close();
}

// Handle Delete
if(isset($_POST['delete_video'])){
    $id = (int)$_POST['id'];
    $conn->query("DELETE FROM videos WHERE id=$id");
    $conn->query("DELETE FROM video_views WHERE video_id=$id");
    $conn->query("DELETE FROM video_likes WHERE video_id=$id");
    $conn->query("DELETE FROM video_comments WHERE video_id=$id");
    $message = "Reel deleted successfully!";
    $toast_class = "bg-danger";
    logAction($conn, $_SESSION['admin'], 'Delete Reel', "Deleted reel ID: $id");
}

// Fetch Reels
$search = $_GET['search'] ?? '';
$platform_filter = $_GET['platform'] ?? '';

$sql = "SELECT * FROM videos WHERE platform IN ('facebook_reel', 'instagram_reel')";
if ($search) {
    $sql .= " AND title LIKE '%" . $conn->real_escape_string($search) . "%'";
}
if ($platform_filter) {
    $sql .= " AND platform = '" . $conn->real_escape_string($platform_filter) . "'";
}
$sql .= " ORDER BY id DESC";
$videos = $conn->query($sql);

// Fetch Stats
$views_data = [];
$today = date('Y-m-d');

// Total Views
$t_res = $conn->query("SELECT video_id, COUNT(*) as total FROM video_views GROUP BY video_id");
while($row = $t_res->fetch_assoc()){
    $views_data[$row['video_id']]['total'] = $row['total'];
}

// Daily Views
$d_res = $conn->query("SELECT video_id, COUNT(*) as today FROM video_views WHERE DATE(viewed_at) = '$today' GROUP BY video_id");
while($row = $d_res->fetch_assoc()){
    $views_data[$row['video_id']]['today'] = $row['today'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Reels - FB Money System</title>
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
body.dark-mode .form-control, body.dark-mode .form-select { background: #2d2d2d; border-color: #444; color: #e0e0e0; }
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
                <span class="navbar-text ms-auto fw-bold text-primary">Manage Reels</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">🎥 Manage Reels</h2>
                    <p class="text-muted mb-0">Track performance and manage your reel campaigns.</p>
                </div>
                <div class="d-flex gap-3 mt-3 mt-md-0">
                    <form method="GET" class="d-flex align-items-center position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="z-index: 5;"></i>
                        <input type="text" name="search" class="form-control form-control-lg shadow-sm border-0 rounded-pill ps-5 me-2" placeholder="Search reels..." value="<?php echo htmlspecialchars($search); ?>" style="min-width: 200px;">
                        <select name="platform" class="form-select form-select-lg shadow-sm border-0 rounded-pill ps-4 pe-5" onchange="this.form.submit()" style="min-width: 180px;">
                            <option value="">All Reels</option>
                            <option value="facebook_reel" <?php if($platform_filter == 'facebook_reel') echo 'selected'; ?>>Facebook Reels</option>
                            <option value="instagram_reel" <?php if($platform_filter == 'instagram_reel') echo 'selected'; ?>>Instagram Reels</option>
                        </select>
                        <?php if($search || $platform_filter): ?>
                            <a href="reels_list.php" class="btn btn-light btn-lg rounded-circle shadow-sm ms-2 d-flex align-items-center justify-content-center text-danger" style="width: 48px; height: 48px;" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                    <a href="boost_reel.php" class="btn btn-danger btn-lg rounded-pill shadow-sm px-4" style="background: linear-gradient(135deg, #e83e8c 0%, #d63384 100%); border:none;"><i class="bi bi-plus-lg me-2"></i>Add Reel</a>
                </div>
            </div>

            <div class="row g-4">
                <?php if($videos && $videos->num_rows > 0): ?>
                    <?php while($video = $videos->fetch_assoc()): 
                        $vid = $video['id'];
                        $total_views = $views_data[$vid]['total'] ?? 0;
                        $today_views = $views_data[$vid]['today'] ?? 0;
                        $target = $video['target_views'];
                        $daily_limit = $video['daily_limit'];
                        
                        $progress = ($target > 0) ? min(100, round(($total_views / $target) * 100)) : 0;
                        $daily_progress = ($daily_limit > 0) ? min(100, round(($today_views / $daily_limit) * 100)) : 0;
                        
                        $status = $video['status'];
                        
                        $platform = $video['platform'] ?? 'facebook_reel';
                        $icon = 'bi-camera-reels-fill';
                        if ($platform === 'instagram_reel') $icon = 'bi-instagram';
                    ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-lg rounded-4 h-100 overflow-hidden">
                            <div class="card-header text-white p-3" style="background: linear-gradient(135deg, #e83e8c 0%, #d63384 100%); border:none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-truncate" style="max-width: 70%;" title="<?php echo htmlspecialchars($video['title']); ?>"><i class="bi <?php echo $icon; ?> me-2"></i><?php echo htmlspecialchars($video['title']); ?></h5>
                                    <span class="badge bg-white text-danger shadow-sm"><?php echo ucfirst($status); ?></span>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <!-- Progress Bars -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between small fw-bold text-muted mb-1">
                                        <span><i class="bi bi-eye-fill me-1"></i>Total Views</span>
                                        <span><?php echo number_format($total_views); ?> / <?php echo number_format($target); ?></span>
                                    </div>
                                    <div class="progress rounded-pill" style="height: 10px; background-color: #e9ecef;">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, #e83e8c, #d63384);" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                                <?php if($daily_limit > 0): ?>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between small fw-bold text-muted mb-1">
                                        <span><i class="bi bi-speedometer2 me-1"></i>Daily Limit</span>
                                        <span><?php echo number_format($today_views); ?> / <?php echo number_format($daily_limit); ?></span>
                                    </div>
                                    <div class="progress rounded-pill" style="height: 10px; background-color: #e9ecef;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $daily_progress; ?>%;" aria-valuenow="<?php echo $daily_progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <form method="POST" class="mt-auto">
                                    <input type="hidden" name="id" value="<?php echo $video['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold text-uppercase">Settings</label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-speedometer2 text-muted"></i></span>
                                            <input type="number" name="daily_limit" class="form-control bg-light border-start-0" value="<?php echo $daily_limit; ?>" placeholder="Daily Limit">
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-toggle-on text-muted"></i></span>
                                            <select name="status" class="form-select bg-light border-start-0">
                                                <option value="active" <?php echo ($status=='active')?'selected':''; ?>>Active</option>
                                                <option value="paused" <?php echo ($status=='paused')?'selected':''; ?>>Paused</option>
                                                <option value="completed" <?php echo ($status=='completed')?'selected':''; ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_video" class="btn btn-primary shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border:none;"><i class="bi bi-save me-2"></i>Update</button>
                                        <button type="submit" name="delete_video" class="btn btn-light text-danger shadow-sm" onclick="return confirm('Delete this reel?');"><i class="bi bi-trash me-2"></i>Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5 text-muted">No reels found.</div>
                <?php endif; ?>
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
function updateTargetInput(select) { const input = document.getElementById('target_input'); const wrapper = document.getElementById('custom_amount_wrapper'); if(select.value === 'custom') { wrapper.style.maxHeight = '100px'; wrapper.style.opacity = '1'; input.value = ''; input.required = true; input.focus(); } else { wrapper.style.maxHeight = '0'; wrapper.style.opacity = '0'; input.value = select.value; input.required = false; } }
</script>
</body>
</html>