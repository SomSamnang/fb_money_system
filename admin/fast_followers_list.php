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
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE pages SET fb_link=?, target_clicks=?, status=? WHERE id=?");
    $stmt->bind_param("sisi",$new_link,$target,$status,$id);
    $stmt->execute();
    $stmt->close();
    logAction($conn, $_SESSION['admin'], 'Update Fast Follower', "Updated fast request ID: $id");
    $message = "Fast request updated successfully!";
    $toast_class = "bg-success";
}

// Handle delete page
if(isset($_POST['delete_page'])){
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM pages WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    logAction($conn, $_SESSION['admin'], 'Delete Fast Follower', "Deleted fast request ID: $id");
    $message = "Fast request deleted successfully!";
    $toast_class = "bg-danger";
}

// Handle bulk delete
if(isset($_POST['bulk_delete'])){
    if(!empty($_POST['page_ids'])) {
        $ids_raw = explode(',', $_POST['page_ids']);
        $ids = array_map('intval', $ids_raw);
        if(!empty($ids)) {
            $ids_str = implode(',', $ids);
            $conn->query("DELETE FROM pages WHERE id IN ($ids_str) AND type='follower' AND is_fast=1");
            logAction($conn, $_SESSION['admin'], 'Bulk Delete Fast Followers', "Deleted IDs: $ids_str");
            $message = "Selected requests deleted!";
            $toast_class = "bg-success";
        }
    }
}

// Handle delete all completed
if(isset($_POST['delete_all_completed'])){
    $conn->query("DELETE FROM pages WHERE type='follower' AND is_fast=1 AND status='completed'");
    $affected = $conn->affected_rows;
    logAction($conn, $_SESSION['admin'], 'Clear Completed Fast Followers', "Deleted $affected completed requests");
    $message = "Deleted $affected completed requests!";
    $toast_class = "bg-success";
}

// Handle expire old requests (> 24 hours)
if(isset($_POST['expire_old'])){
    $conn->query("UPDATE pages SET status='completed' WHERE type='follower' AND is_fast=1 AND status='active' AND created_at < (NOW() - INTERVAL 24 HOUR)");
    $affected = $conn->affected_rows;
    logAction($conn, $_SESSION['admin'], 'Expire Fast Followers', "Expired $affected requests older than 24h");
    $message = "Expired $affected requests older than 24 hours!";
    $toast_class = "bg-success";
}

// Handle Retry
if(isset($_POST['retry_request'])){
    $id = (int)$_POST['id'];
    $conn->query("UPDATE pages SET status='active' WHERE id=$id");
    logAction($conn, $_SESSION['admin'], 'Retry Fast Follower', "Retried request ID: $id");
    $message = "Request reactivated successfully!";
    $toast_class = "bg-success";
}

// Filter Logic
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$allowed_statuses = ['active', 'paused', 'completed', 'scheduled'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Fetch Fast Follower Pages (is_fast = 1)
$sql = "SELECT * FROM pages WHERE type='follower' AND is_fast=1";
if ($status_filter) {
    $sql .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($search) {
    $sql .= " AND name LIKE '%" . $conn->real_escape_string($search) . "%'";
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

// Calculate Overall Progress
$total_target_all = 0;
$total_achieved_all = 0;
foreach($pages as $name => $page) {
    $target = isset($page['target_clicks']) ? (int)$page['target_clicks'] : 0;
    $achieved = $clicks[$name]['follow'] ?? 0;
    $total_target_all += $target;
    $total_achieved_all += $achieved;
}
$overall_progress = ($total_target_all > 0) ? min(100, round(($total_achieved_all / $total_target_all) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fast Add Requests - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Fast Add Requests</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">⚡ Fast Add Requests</h2>
                    <p class="text-muted mb-0">Manage high-priority follower requests.</p>
                </div>
                <div class="d-flex flex-column flex-md-row gap-3 mt-3 mt-md-0 align-items-center">
                    <form method="GET" class="d-flex align-items-center">
                        <div class="position-relative me-2">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="text" name="search" class="form-control form-control-lg shadow-sm border-0 rounded-pill ps-5" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 200px;">
                        </div>
                        <select name="status" class="form-select form-select-lg shadow-sm border-0 rounded-pill ps-4 pe-5" onchange="this.form.submit()" style="min-width: 160px;">
                            <option value="">All Status</option>
                            <option value="active" <?php if($status_filter == 'active') echo 'selected'; ?>>Active</option>
                            <option value="completed" <?php if($status_filter == 'completed') echo 'selected'; ?>>Completed</option>
                            <option value="scheduled" <?php if($status_filter == 'scheduled') echo 'selected'; ?>>Scheduled</option>
                        </select>
                        <?php if($status_filter || $search): ?>
                            <a href="fast_followers_list.php" class="btn btn-light btn-lg rounded-circle shadow-sm ms-2 d-flex align-items-center justify-content-center text-danger" style="width: 48px; height: 48px;" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                    <a href="add_follower_fast.php" class="btn btn-primary btn-lg rounded-pill shadow-sm px-4"><i class="bi bi-plus-lg me-2"></i>New Fast Add</a>
                </div>
            </div>

            <!-- Overall Progress Bar -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-graph-up-arrow me-2"></i>Overall Progress</h5>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: <?php echo $overall_progress; ?>%;" aria-valuenow="<?php echo $overall_progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $overall_progress; ?>%</div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-muted small fw-bold">
                        <span>Total Gained: <?php echo formatNumber($total_achieved_all); ?></span>
                        <span>Total Target: <?php echo formatNumber($total_target_all); ?></span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
                <div class="form-check ms-2">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label fw-bold text-secondary" for="selectAll">Select All</label>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" onsubmit="return confirm('Expire all active requests older than 24 hours?');">
                        <button type="submit" name="expire_old" class="btn btn-outline-warning btn-sm rounded-pill fw-bold">
                            <i class="bi bi-clock-history me-1"></i> Expire >24h
                        </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete ALL completed requests?');">
                        <button type="submit" name="delete_all_completed" class="btn btn-outline-success btn-sm rounded-pill fw-bold">
                            <i class="bi bi-check-all me-1"></i> Clear Completed
                        </button>
                    </form>
                    <form method="POST" id="bulkDeleteForm" onsubmit="return confirm('Delete selected requests?');">
                        <input type="hidden" name="page_ids" id="bulkDeleteInput">
                        <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm rounded-pill fw-bold" id="bulkDeleteBtn" disabled>
                            <i class="bi bi-trash me-1"></i> Delete Selected
                        </button>
                    </form>
                </div>
            </div>

            <div class="row g-4">
                <?php if(empty($pages)): ?>
                    <div class="col-12 text-center py-5 text-muted">No fast requests found.</div>
                <?php else: ?>
                    <?php foreach($pages as $name=>$page): 
                        $follow = $clicks[$name]['follow'] ?? 0;
                        $target = isset($page['target_clicks']) ? (int)$page['target_clicks'] : 0;
                        $progress = ($target > 0) ? min(100, round(($follow / $target) * 100)) : 0;
                        $status = $page['status'] ?? 'active';
                    ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-lg rounded-4 h-100 overflow-hidden">
                            <div class="card-header text-white p-3" style="background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%); border:none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <input class="form-check-input page-checkbox me-2 flex-shrink-0" type="checkbox" value="<?php echo $page['id']; ?>">
                                        <h5 class="mb-0 text-truncate" title="<?php echo htmlspecialchars($name); ?>"><i class="bi bi-lightning-fill me-2"></i><?php echo htmlspecialchars($name); ?></h5>
                                    </div>
                                    <span class="badge bg-white text-danger shadow-sm"><?php echo ucfirst($status); ?></span>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3 small border-bottom pb-3">
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span><i class="bi bi-calendar-plus me-1"></i>Created:</span>
                                        <span class="fw-bold"><?php echo date('M d, H:i', strtotime($page['created_at'])); ?></span>
                                    </div>
                                    <?php if($status == 'scheduled'): ?>
                                    <div class="d-flex justify-content-between text-primary mb-1">
                                        <span><i class="bi bi-clock me-1"></i>Scheduled:</span>
                                        <span class="fw-bold"><?php echo date('M d, H:i', strtotime($page['scheduled_at'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php 
                                    $expires_ts = strtotime($page['created_at'] . ' +24 hours');
                                    $is_expired = time() > $expires_ts;
                                    ?>
                                    <div class="d-flex justify-content-between <?php echo $is_expired ? 'text-danger fw-bold' : 'text-muted'; ?>" title="Auto-expires 24h after creation">
                                        <span><i class="bi bi-hourglass-split me-1"></i>Expires:</span>
                                        <span class="fw-bold"><?php echo date('M d, H:i', $expires_ts); ?></span>
                                    </div>
                                </div>
                                <?php 
                                $start_count = isset($page['start_count']) ? (int)$page['start_count'] : 0;
                                $current_total = $start_count + $follow;
                                ?>
                                <div class="d-flex justify-content-between small fw-bold text-muted mb-2 px-1">
                                    <span>Start: <?php echo formatNumber($start_count); ?></span>
                                    <span class="text-primary">Current: <?php echo formatNumber($current_total); ?></span>
                                </div>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between small fw-bold text-muted mb-1">
                                        <span><i class="bi bi-people-fill me-1"></i>Progress</span>
                                        <span><?php echo formatNumber($follow); ?> / <?php echo formatNumber($target); ?></span>
                                    </div>
                                    <div class="progress rounded-pill" style="height: 10px; background-color: #e9ecef;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <form method="POST" class="mt-auto">
                                    <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                                    <div class="mb-3"><input type="text" name="fb_link" class="form-control bg-light" value="<?php echo htmlspecialchars($page['fb_link']); ?>" required></div>
                                    <div class="d-grid gap-2">
                                        <?php if($status == 'completed' || $status == 'paused'): ?>
                                            <button type="submit" name="retry_request" class="btn btn-outline-primary shadow-sm"><i class="bi bi-arrow-repeat me-2"></i>Retry / Reactivate</button>
                                        <?php endif; ?>
                                        <button type="submit" name="delete_page" class="btn btn-light text-danger shadow-sm" onclick="return confirm('Delete this request?');"><i class="bi bi-trash me-2"></i>Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); document.getElementById('wrapper').classList.toggle('toggled'); });

// Bulk Selection Logic
const selectAll = document.getElementById('selectAll');
const pageCheckboxes = document.querySelectorAll('.page-checkbox');
const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
const bulkDeleteInput = document.getElementById('bulkDeleteInput');

function updateBulkState() {
    const checked = document.querySelectorAll('.page-checkbox:checked');
    bulkDeleteBtn.disabled = checked.length === 0;
    bulkDeleteInput.value = Array.from(checked).map(cb => cb.value).join(',');
}

if(selectAll) { selectAll.addEventListener('change', function() { pageCheckboxes.forEach(cb => cb.checked = this.checked); updateBulkState(); }); }
pageCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkState));
</script>
</body>
</html>