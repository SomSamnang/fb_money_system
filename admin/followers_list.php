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

// Handle update link
if(isset($_POST['update_link'])){
    $id = (int)$_POST['id'];
    $new_link = $_POST['fb_link'];
    $target = (int)$_POST['target_clicks'];
    $daily_limit = (int)$_POST['daily_limit'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE pages SET fb_link=?, target_clicks=?, daily_limit=?, status=?, paused_by_limit=0 WHERE id=?");
    $stmt->bind_param("siisi",$new_link,$target,$daily_limit,$status,$id);
    $stmt->execute();
    $stmt->close();
    logAction($conn, $_SESSION['admin'], 'Update Follower Page', "Updated page ID: $id");
    $message = "Campaign updated successfully!";
    $toast_class = "bg-success";
}

// Handle delete page
if(isset($_POST['delete_page'])){
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM pages WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    logAction($conn, $_SESSION['admin'], 'Delete Follower Page', "Deleted page ID: $id");
    $message = "Campaign deleted successfully!";
    $toast_class = "bg-danger";
}

// Filter Logic
$status_filter = $_GET['status'] ?? '';
$allowed_statuses = ['active', 'paused', 'completed'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Fetch Follower Pages
$sql = "SELECT * FROM pages WHERE type='follower'";
if ($status_filter) {
    $sql .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= " ORDER BY id DESC";
$pages_result = $conn->query($sql);

$pages = [];
if ($pages_result) {
    while($row = $pages_result->fetch_assoc()){
        $pages[$row['name']] = $row;
    }
}

// Fetch stats
$sql_stats = "SELECT page,type,COUNT(*) as total FROM clicks GROUP BY page,type";
$stats_result = $conn->query($sql_stats);
$clicks = [];
if ($stats_result) {
    while($row = $stats_result->fetch_assoc()){
        $clicks[$row['page']][$row['type']] = $row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Followers - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Manage Followers</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">👥 Boosted Follower Campaigns</h4>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex">
                        <select name="status" class="form-select me-2" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="active" <?php if($status_filter == 'active') echo 'selected'; ?>>Active</option>
                            <option value="paused" <?php if($status_filter == 'paused') echo 'selected'; ?>>Paused</option>
                            <option value="completed" <?php if($status_filter == 'completed') echo 'selected'; ?>>Completed</option>
                        </select>
                    </form>
                    <a href="boost_follower.php" class="btn btn-success text-nowrap"><i class="bi bi-plus-circle me-2"></i>Add New</a>
                </div>
            </div>

            <div class="row g-4">
                <?php if(empty($pages)): ?>
                    <div class="col-12 text-center py-5 text-muted">No follower campaigns found matching your criteria.</div>
                <?php else: ?>
                    <?php foreach($pages as $name=>$page): 
                        $follow = $clicks[$name]['follow'] ?? 0;
                        $share  = $clicks[$name]['share'] ?? 0;
                        $total_clicks = $follow + $share;
                        $target = isset($page['target_clicks']) ? (int)$page['target_clicks'] : 0;
                        $daily_limit = isset($page['daily_limit']) ? (int)$page['daily_limit'] : 0;
                        $progress = ($target > 0) ? min(100, round(($total_clicks / $target) * 100)) : 0;
                        $status = $page['status'] ?? 'active';
                        $status_badge = 'bg-secondary';
                        if ($status === 'active') $status_badge = 'bg-success';
                        elseif ($status === 'completed') $status_badge = 'bg-primary';
                        elseif ($status === 'paused') $status_badge = 'bg-warning text-dark';
                    ?>
                    <div class="col-md-4">
                        <div class="card text-center p-3 position-relative">
                            <h5 class="text-success text-capitalize page-title mb-1"><?php echo htmlspecialchars($name); ?></h5>
                            <div class="mb-2"><span class="badge <?php echo $status_badge; ?>"><?php echo ucfirst($status); ?></span></div>
                            <p class="mb-1">Followers Gained: <strong><?php echo $follow; ?></strong></p>
                            
                            <?php if($target > 0): ?>
                            <div class="progress mb-2" style="height: 10px;" title="<?php echo $total_clicks . ' / ' . $target; ?>">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <small class="text-muted d-block mb-2"><?php echo $total_clicks; ?> / <?php echo $target; ?> (<?php echo $progress; ?>%)</small>
                            <?php endif; ?>
                            <form method="POST" class="mt-2">
                                <div class="input-group mb-2">
                                    <input type="text" name="fb_link" class="form-control" value="<?php echo htmlspecialchars($page['fb_link']); ?>" required>
                                    <a href="<?php echo htmlspecialchars($page['fb_link']); ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text small">Target</span>
                                    <input type="number" name="target_clicks" class="form-control" value="<?php echo $target; ?>" placeholder="0">
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text small">Daily Limit</span>
                                    <input type="number" name="daily_limit" class="form-control" value="<?php echo $daily_limit; ?>" placeholder="0">
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text small">Status</span>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo ($status=='active')?'selected':''; ?>>Active</option>
                                        <option value="completed" <?php echo ($status=='completed')?'selected':''; ?>>Completed</option>
                                        <option value="paused" <?php echo ($status=='paused')?'selected':''; ?>>Paused</option>
                                    </select>
                                </div>
                                <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                                <div class="d-flex gap-2">
                                    <input type="submit" name="update_link" class="btn btn-primary w-50" value="Update">
                                    <input type="submit" name="delete_page" class="btn btn-danger w-50" value="Delete" onclick="return confirm('Are you sure?');">
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
</script>
</body>
</html>