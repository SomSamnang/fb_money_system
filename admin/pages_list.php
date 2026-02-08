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

// Handle update page
if(isset($_POST['update_page'])){
    $id = (int)$_POST['id'];
    $new_link = $_POST['fb_link'];
    $target = (int)$_POST['target_clicks'];
    $daily_limit = (int)$_POST['daily_limit'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE pages SET fb_link=?, target_clicks=?, daily_limit=?, status=?, paused_by_limit=0 WHERE id=?");
    $stmt->bind_param("siisi",$new_link,$target,$daily_limit,$status,$id);
    $stmt->execute();
    $stmt->close();
    logAction($conn, $_SESSION['admin'], 'Update Page', "Updated page ID: $id");
    $message = "Page updated successfully!";
    $toast_class = "bg-success";
}

// Handle delete page
if(isset($_POST['delete_page'])){
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM pages WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    logAction($conn, $_SESSION['admin'], 'Delete Page', "Deleted page ID: $id");
    $message = "Page deleted successfully!";
    $toast_class = "bg-danger";
}

// Handle bulk delete pages
if(isset($_POST['bulk_delete_pages'])){
    if(!empty($_POST['page_ids'])) {
        $ids_raw = explode(',', $_POST['page_ids']);
        $ids = array_map('intval', $ids_raw);
        if(!empty($ids)) {
            $ids_str = implode(',', $ids);
            $conn->query("DELETE FROM pages WHERE id IN ($ids_str)");
            logAction($conn, $_SESSION['admin'], 'Bulk Delete Pages', "Deleted page IDs: $ids_str");
            $message = "Selected pages deleted successfully!";
            $toast_class = "bg-danger";
        }
    }
}

// Filter Logic
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$allowed_statuses = ['active', 'paused', 'completed'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Fetch Pages
$sql = "SELECT * FROM pages WHERE type='page'";
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
$sql_stats = "SELECT page,type,COUNT(*) as total FROM clicks WHERE type='page' GROUP BY page,type";
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
<title>Manage Pages - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Manage Pages</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">📄 Manage Pages</h2>
                    <p class="text-muted mb-0">Track and boost your Facebook pages.</p>
                </div>
                <div class="d-flex gap-3 mt-3 mt-md-0">
                    <form method="GET" class="d-flex align-items-center position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="z-index: 5;"></i>
                        <input type="text" name="search" class="form-control form-control-lg shadow-sm border-0 rounded-pill ps-5 me-2" placeholder="Search pages..." value="<?php echo htmlspecialchars($search); ?>" style="min-width: 200px;">
                        <select name="status" class="form-select form-select-lg shadow-sm border-0 rounded-pill ps-4 pe-5" onchange="this.form.submit()" style="min-width: 180px;">
                            <option value="">All Statuses</option>
                            <option value="active" <?php if($status_filter == 'active') echo 'selected'; ?>>Active</option>
                            <option value="paused" <?php if($status_filter == 'paused') echo 'selected'; ?>>Paused</option>
                            <option value="completed" <?php if($status_filter == 'completed') echo 'selected'; ?>>Completed</option>
                        </select>
                        <?php if($search || $status_filter): ?>
                            <a href="pages_list.php" class="btn btn-light btn-lg rounded-circle shadow-sm ms-2 d-flex align-items-center justify-content-center text-danger" style="width: 48px; height: 48px;" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                    <a href="boost_page.php" class="btn btn-primary btn-lg rounded-pill shadow-sm px-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border:none;"><i class="bi bi-plus-lg me-2"></i>Add New</a>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllPages">
                    <label class="form-check-label" for="selectAllPages">Select All</label>
                </div>
                <form method="POST" id="bulkDeleteForm" onsubmit="return confirm('Are you sure you want to delete the selected pages?');">
                    <input type="hidden" name="page_ids" id="bulkDeleteInput">
                    <button type="submit" name="bulk_delete_pages" class="btn btn-danger btn-sm rounded-pill shadow-sm" id="bulkDeleteBtn" disabled>
                        <i class="bi bi-trash"></i> Delete Selected
                    </button>
                </form>
            </div>

            <div class="row g-4">
                <?php if(empty($pages)): ?>
                    <div class="col-12 text-center py-5 text-muted">No pages found matching your criteria.</div>
                <?php else: ?>
                    <?php foreach($pages as $name=>$page): 
                        $follow = $clicks[$name]['follow'] ?? 0;
                        $share  = $clicks[$name]['share'] ?? 0;
                        $total_clicks = $follow + $share;
                        $target = isset($page['target_clicks']) ? (int)$page['target_clicks'] : 0;
                        $daily_limit = isset($page['daily_limit']) ? (int)$page['daily_limit'] : 0;
                        $progress = ($target > 0) ? min(100, round(($total_clicks / $target) * 100)) : 0;
                        $status = $page['status'] ?? 'active';
                    ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-lg rounded-4 h-100 overflow-hidden">
                            <div class="card-header text-white p-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border:none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <input class="form-check-input page-checkbox me-2" type="checkbox" value="<?php echo $page['id']; ?>">
                                        <h5 class="mb-0 text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($name); ?>"><i class="bi bi-facebook me-2"></i><?php echo htmlspecialchars($name); ?></h5>
                                    </div>
                                    <span class="badge bg-white text-primary shadow-sm"><?php echo ucfirst($status); ?></span>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <!-- Progress -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between small fw-bold text-muted mb-1">
                                        <span><i class="bi bi-hand-thumbs-up-fill me-1"></i>Clicks</span>
                                        <span><?php echo formatNumber($total_clicks); ?> <?php if($target > 0) echo '/ ' . formatNumber($target); ?></span>
                                    </div>
                                    <?php if($target > 0): ?>
                                    <div class="progress rounded-pill" style="height: 10px; background-color: #e9ecef;">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, #667eea, #764ba2);" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <?php else: ?>
                                    <div class="progress rounded-pill" style="height: 10px; background-color: #e9ecef;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%; background: linear-gradient(90deg, #667eea, #764ba2);"></div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <form method="POST" class="mt-auto">
                                    <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold text-uppercase">Settings</label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-link-45deg text-muted"></i></span>
                                            <input type="text" name="fb_link" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($page['fb_link']); ?>" required placeholder="URL">
                                            <a href="<?php echo htmlspecialchars($page['fb_link']); ?>" target="_blank" class="btn btn-light border-start-0 border"><i class="bi bi-box-arrow-up-right text-muted"></i></a>
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0 px-2"><i class="bi bi-bullseye text-muted"></i></span>
                                                    <input type="number" name="target_clicks" class="form-control bg-light border-start-0 px-2" value="<?php echo $target; ?>" placeholder="Target">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0 px-2"><i class="bi bi-speedometer2 text-muted"></i></span>
                                                    <input type="number" name="daily_limit" class="form-control bg-light border-start-0 px-2" value="<?php echo $daily_limit; ?>" placeholder="Limit">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-toggle-on text-muted"></i></span>
                                            <select name="status" class="form-select bg-light border-start-0">
                                                <option value="active" <?php echo ($status=='active')?'selected':''; ?>>Active</option>
                                                <option value="completed" <?php echo ($status=='completed')?'selected':''; ?>>Completed</option>
                                                <option value="paused" <?php echo ($status=='paused')?'selected':''; ?>>Paused</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_page" class="btn btn-primary shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border:none;"><i class="bi bi-save me-2"></i>Update</button>
                                        <button type="submit" name="delete_page" class="btn btn-light text-danger shadow-sm" onclick="return confirm('Are you sure?');"><i class="bi bi-trash me-2"></i>Delete</button>
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
<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="liveToast" class="toast align-items-center text-white <?php echo $toast_class; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body"><?php echo $message; ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); document.getElementById('wrapper').classList.toggle('toggled'); });
document.getElementById('page-content-wrapper').addEventListener('click', function(e) { if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) { document.getElementById('wrapper').classList.remove('toggled'); } });
document.getElementById('sidebarClose').addEventListener('click', function(e) { e.preventDefault(); document.getElementById('wrapper').classList.remove('toggled'); });
const toggle = document.getElementById('darkModeToggle'); const body = document.body; if(localStorage.getItem('darkMode') === 'enabled'){ body.classList.add('dark-mode'); toggle.textContent = '☀️'; } toggle.addEventListener('click', () => { body.classList.toggle('dark-mode'); localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : 'disabled'); toggle.textContent = body.classList.contains('dark-mode') ? '☀️' : '🌙'; });
<?php if($message): ?>const toastEl = document.getElementById('liveToast'); const toast = new bootstrap.Toast(toastEl); toast.show();<?php endif; ?>

// Bulk Selection Logic
const selectAll = document.getElementById('selectAllPages');
const pageCheckboxes = document.querySelectorAll('.page-checkbox');
const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
const bulkDeleteInput = document.getElementById('bulkDeleteInput');

function updateBulkState() {
    const checked = document.querySelectorAll('.page-checkbox:checked');
    bulkDeleteBtn.disabled = checked.length === 0;
    bulkDeleteInput.value = Array.from(checked).map(cb => cb.value).join(',');
}

if(selectAll) {
    selectAll.addEventListener('change', function() {
        pageCheckboxes.forEach(cb => cb.checked = this.checked);
        updateBulkState();
    });
}

pageCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkState));
</script>
</body>
</html>