<?php
require_once 'auth.php'; // Secure the page
require_once "../config/db.php";

// Fetch user info for sidebar
$u_stmt = $conn->prepare("SELECT profile_pic, last_login, bio, role FROM admins WHERE username=?");
if ($u_stmt) {
    $u_stmt->bind_param("s", $_SESSION['admin']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    $u_row = $u_res->fetch_assoc();
} else {
    // Fallback if query fails (e.g. missing columns)
    $u_row = [];
}
if (!$u_row) $u_row = []; // Ensure it is an array if user not found
$profile_pic = !empty($u_row['profile_pic']) ? "uploads/" . $u_row['profile_pic'] : "https://via.placeholder.com/150";
$last_login = !empty($u_row['last_login']) ? date("M d, Y h:i A", strtotime($u_row['last_login'])) : "First Login";
$bio = $u_row['bio'] ?? '';
$user_role = $u_row['role'] ?? 'editor';

$message = "";
$toast_class = "";
// Handle add new page
if(isset($_POST['add_page'])){
    $name = trim($_POST['name']);
    $fb_link = trim($_POST['fb_link']);
    $target = (int)$_POST['target_clicks'];
    if($name && $fb_link){
        $stmt = $conn->prepare("INSERT INTO pages(name,fb_link,target_clicks,type) VALUES(?,?,?,'page')");
        $stmt->bind_param("ssi",$name,$fb_link,$target);
        $stmt->execute();
        $stmt->close();
        logAction($conn, $_SESSION['admin'], 'Add Page', "Added page: $name");
        $message = "New page added successfully!";
        $toast_class = "bg-success";
    }
}

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

// Fetch pages
$pages_result = $conn->query("SELECT * FROM pages WHERE type='page'");
$pages = [];
while($row = $pages_result->fetch_assoc()){
    $pages[$row['name']] = $row;
}

// Fetch stats
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$sql = "SELECT page,type,COUNT(*) as total FROM clicks";
if($start_date && $end_date){
    $sql .= " WHERE click_date BETWEEN ? AND ?";
}
$sql .= " GROUP BY page,type";

$stmt = $conn->prepare($sql);
if($start_date && $end_date){
    $s = $start_date . " 00:00:00";
    $e = $end_date . " 23:59:59";
    $stmt->bind_param("ss", $s, $e);
}
$stmt->execute();
$stats_result = $stmt->get_result();

$clicks = [];
while($row = $stats_result->fetch_assoc()){
    $clicks[$row['page']][$row['type']] = $row['total'];
}

// Fetch overview stats
$total_users = $conn->query("SELECT COUNT(*) FROM admins")->fetch_row()[0]; // Change 'admins' to 'users' table later for public registration
$total_visitors_res = $conn->query("SELECT COUNT(*) FROM visitors");
$total_visitors = $total_visitors_res ? $total_visitors_res->fetch_row()[0] : 0;

// Fetch country stats
$country_res = $conn->query("SELECT country, COUNT(*) as total FROM visitors GROUP BY country ORDER BY total DESC LIMIT 10");
$c_labels = [];
$c_data = [];
if ($country_res) {
    while($row = $country_res->fetch_assoc()){
        $c_labels[] = $row['country'] ? $row['country'] : 'Unknown';
        $c_data[] = $row['total'];
    }
}

// Fetch Top Performing Pages
$top_pages_res = $conn->query("SELECT page, COUNT(*) as total_clicks FROM clicks GROUP BY page ORDER BY total_clicks DESC LIMIT 5");

// Fetch Most Liked Videos
$top_videos_res = $conn->query("SELECT v.title, COUNT(vl.id) as total_likes FROM video_likes vl JOIN videos v ON vl.video_id = v.id GROUP BY vl.video_id ORDER BY total_likes DESC LIMIT 5");

// Fetch Recent Activity
$recent_activity_res = $conn->query("SELECT username, action, created_at FROM system_logs ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - FB Money System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background:#f8f9fc; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
#wrapper { display: flex; width: 100%; }
#sidebar-wrapper {
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
    width: 250px;
    background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
    color: #fff;
    transition: margin 0.25s ease-out;
}
#sidebar-wrapper .sidebar-heading { padding: 1.5rem; font-size: 1.5rem; font-weight: bold; text-align: center; color:rgba(255,255,255,0.9); border-bottom: 1px solid rgba(255,255,255,0.1); }
#sidebar-wrapper .list-group-item {
    background: transparent; color: rgba(255,255,255,0.8); border: none; padding: 1rem 1.5rem;
}
#sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.2); color: #fff; }
#sidebar-wrapper .list-group-item.active { background: rgba(255,255,255,0.3); color: #fff; font-weight: bold; }
#page-content-wrapper { width: 100%; display: flex; flex-direction: column; min-height: 100vh; }
.card h5 { font-weight:600; }
.message { margin:15px 0; padding:10px; background:#d4edda; color:#155724; border-radius:8px; }
.navbar { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); background: #fff !important; position: sticky; top: 0; z-index: 100; }
/* Dark Mode Styles */
body.dark-mode { background: #121212; color: #e0e0e0; }
body.dark-mode .card { background: #1e1e1e; color: #e0e0e0; }
body.dark-mode .navbar { background: #1e1e1e !important; color: #e0e0e0; }
body.dark-mode .form-control { background: #2d2d2d; border-color: #444; color: #e0e0e0; }
#wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
@media (max-width: 768px) {
    #sidebar-wrapper { margin-left: -250px; position: fixed; z-index: 1000; height: 100%; top: 0; left: 0; }
    #wrapper.toggled #sidebar-wrapper { margin-left: 0; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
    #page-content-wrapper { width: 100%; min-width: 100%; }
    /* Overlay Backdrop */
    #wrapper.toggled #page-content-wrapper::before {
        content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;
    }
}
#page-loader {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: #ffffff; z-index: 9999;
    display: flex; justify-content: center; align-items: center;
    transition: opacity 0.5s ease;
}
body.dark-mode #page-loader { background: #121212; }
</style>
</head>
<body>

<div id="page-loader">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom mb-4">
            <div class="container-fluid">
                <button class="btn btn-primary" id="sidebarToggle">☰ Menu</button>
                <span class="navbar-text ms-auto fw-bold text-primary">🚀 Dashboard Overview</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">

<!-- Overview Cards -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card border-0 shadow-lg h-100 overflow-hidden text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body position-relative p-4">
                <div class="d-flex justify-content-between align-items-center z-1 position-relative">
                    <div>
                        <h6 class="text-uppercase mb-1 opacity-75 fw-bold">Total Users</h6>
                        <h2 class="display-5 fw-bold mb-0"><?php echo number_format($total_users); ?></h2>
                    </div>
                    <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                        <i class="bi bi-people-fill fs-1"></i>
                    </div>
                </div>
                <i class="bi bi-people-fill position-absolute bottom-0 end-0 display-1 opacity-10" style="transform: translate(20%, 20%);"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-lg h-100 overflow-hidden text-white" style="background: linear-gradient(135deg, #2af598 0%, #009efd 100%);">
            <div class="card-body position-relative p-4">
                <div class="d-flex justify-content-between align-items-center z-1 position-relative">
                    <div>
                        <h6 class="text-uppercase mb-1 opacity-75 fw-bold">Active Pages</h6>
                        <h2 class="display-5 fw-bold mb-0"><?php echo count($pages); ?></h2>
                    </div>
                    <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                        <i class="bi bi-file-earmark-text-fill fs-1"></i>
                    </div>
                </div>
                <i class="bi bi-file-earmark-text-fill position-absolute bottom-0 end-0 display-1 opacity-10" style="transform: translate(20%, 20%);"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-lg h-100 overflow-hidden text-white" style="background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);">
            <div class="card-body position-relative p-4">
                <div class="d-flex justify-content-between align-items-center z-1 position-relative">
                    <div>
                        <h6 class="text-uppercase mb-1 opacity-75 fw-bold">Total Visitors</h6>
                        <h2 class="display-5 fw-bold mb-0"><?php echo number_format($total_visitors); ?></h2>
                    </div>
                    <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                        <i class="bi bi-eye-fill fs-1"></i>
                    </div>
                </div>
                <i class="bi bi-eye-fill position-absolute bottom-0 end-0 display-1 opacity-10" style="transform: translate(20%, 20%);"></i>
            </div>
        </div>
    </div>
</div>

<!-- Add New Page -->
<div class="card border-0 shadow-lg rounded-4 mb-5 overflow-hidden">
    <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); border:none;">
        <h4 class="mb-0 fw-bold"><i class="bi bi-plus-circle-fill me-2"></i>Add New Page</h4>
    </div>
    <div class="card-body p-4">
        <form method="POST" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted">Page Name</label>
                <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-tag"></i></span><input type="text" class="form-control" name="name" placeholder="e.g. My Page" required></div>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold small text-muted">Facebook URL</label>
                <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-facebook"></i></span><input type="text" class="form-control" name="fb_link" placeholder="https://..." required></div>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small text-muted">Target</label>
                <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-bullseye"></i></span><input type="number" class="form-control" name="target_clicks" placeholder="0" value="0"></div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="add_page" class="btn btn-primary w-100 fw-bold shadow-sm" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); border:none;"><i class="bi bi-plus-lg me-1"></i> Add Page</button>
            </div>
        </form>
    </div>
</div>

<!-- Top Performing Pages Widget -->
<div class="card border-0 shadow-sm rounded-4 mb-5 p-4">
    <h5 class="mb-4 fw-bold text-secondary"><i class="bi bi-trophy-fill me-2 text-warning"></i>Top Performing Pages</h5>
    <div class="row g-3">
        <?php if($top_pages_res && $top_pages_res->num_rows > 0): ?>
            <?php while($tp = $top_pages_res->fetch_assoc()): ?>
            <div class="col-6 col-md">
                <div class="border-0 shadow-sm rounded-4 p-3 text-center bg-white h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10" style="background: linear-gradient(135deg, #4e73df, #224abe);"></div>
                    <div class="fw-bold text-primary text-capitalize text-truncate" title="<?php echo htmlspecialchars($tp['page']); ?>"><?php echo htmlspecialchars($tp['page']); ?></div>
                    <div class="h3 mb-0 mt-2 fw-bold"><?php echo $tp['total_clicks']; ?></div>
                    <small class="text-muted">Clicks</small>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted">No data available yet.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Most Liked Videos Widget -->
<div class="card border-0 shadow-sm rounded-4 mb-5 p-4">
    <h5 class="mb-4 fw-bold text-secondary"><i class="bi bi-heart-fill me-2 text-danger"></i>Most Liked Videos</h5>
    <div class="row g-3">
        <?php if($top_videos_res && $top_videos_res->num_rows > 0): ?>
            <?php while($tv = $top_videos_res->fetch_assoc()): ?>
            <div class="col-6 col-md">
                <div class="border-0 shadow-sm rounded-4 p-3 text-center bg-white h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10" style="background: linear-gradient(135deg, #ff0844, #ffb199);"></div>
                    <div class="fw-bold text-danger text-truncate" title="<?php echo htmlspecialchars($tv['title']); ?>"><?php echo htmlspecialchars($tv['title']); ?></div>
                    <div class="h3 mb-0 mt-2 fw-bold"><?php echo $tv['total_likes']; ?></div>
                    <small class="text-muted">Likes</small>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted">No liked videos yet.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Activity Widget -->
<div class="card border-0 shadow-sm rounded-4 mb-5 p-4">
    <h5 class="mb-3 fw-bold text-secondary"><i class="bi bi-clock-history me-2 text-info"></i>Recent Activity</h5>
    <ul class="list-group list-group-flush">
        <?php if($recent_activity_res && $recent_activity_res->num_rows > 0): ?>
            <?php while($log = $recent_activity_res->fetch_assoc()): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                <div>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($log['username']); ?></span>
                    <span class="text-muted mx-2 small">&bull;</span>
                    <span><?php echo htmlspecialchars($log['action']); ?></span>
                </div>
                <small class="text-muted bg-light px-2 py-1 rounded"><?php echo date("M d, H:i", strtotime($log['created_at'])); ?></small>
            </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li class="list-group-item text-center text-muted">No recent activity.</li>
        <?php endif; ?>
    </ul>
</div>

<!-- Page Cards -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="flex-grow-1 me-3 position-relative">
        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
        <input type="text" id="pageSearch" class="form-control form-control-lg ps-5 rounded-pill shadow-sm border-0" placeholder="Search pages...">
    </div>
    <form method="POST" id="bulkDeleteForm" onsubmit="return confirm('Are you sure you want to delete the selected pages?');">
        <input type="hidden" name="page_ids" id="bulkDeleteInput">
        <button type="submit" name="bulk_delete_pages" class="btn btn-danger btn-lg rounded-pill shadow-sm" id="bulkDeleteBtn" disabled>
            <i class="bi bi-trash"></i> Delete Selected
        </button>
    </form>
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="selectAllPages">
    <label class="form-check-label" for="selectAllPages">Select All</label>
</div>

<div id="pageList" class="row g-4 mb-5">
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
                        <h5 class="mb-0 text-truncate page-title" style="max-width: 150px;" title="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?></h5>
                    </div>
                    <span class="badge bg-white text-primary shadow-sm"><?php echo ucfirst($status); ?></span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <div class="d-flex justify-content-between small fw-bold text-muted mb-1">
                        <span><i class="bi bi-hand-thumbs-up-fill me-1"></i>Follows: <?php echo $follow; ?></span>
                        <span><i class="bi bi-share-fill me-1"></i>Shares: <?php echo $share; ?></span>
                    </div>
                    <?php if($target > 0): ?>
                    <div class="progress rounded-pill" style="height: 10px; background-color: #e9ecef;">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, #667eea, #764ba2);" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-end small text-muted mt-1"><?php echo $total_clicks; ?> / <?php echo $target; ?></div>
                    <?php else: ?>
                    <div class="progress rounded-pill" style="height: 10px; background-color: #e9ecef;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%; background: linear-gradient(90deg, #667eea, #764ba2);"></div>
                    </div>
                    <?php endif; ?>
                </div>

                <form method="POST" class="mt-auto">
                    <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                    
                    <div class="mb-3">
                        <div class="input-group mb-2">
                            <input type="text" name="fb_link" id="link-<?php echo $page['id']; ?>" class="form-control bg-light border-end-0" value="<?php echo htmlspecialchars($page['fb_link']); ?>" required>
                            <button type="button" class="btn btn-light border border-start-0" onclick="copyToClipboard('link-<?php echo $page['id']; ?>', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                            <button type="button" class="btn btn-light border border-start-0" onclick="showQRCode(this.getAttribute('data-url'), this.getAttribute('data-name'))" data-url="<?php echo htmlspecialchars($page['fb_link']); ?>" data-name="<?php echo htmlspecialchars($name); ?>" title="QR"><i class="bi bi-qr-code"></i></button>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-bullseye text-muted"></i></span>
                                    <input type="number" name="target_clicks" class="form-control bg-light border-start-0" value="<?php echo $target; ?>" placeholder="Target">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-speedometer2 text-muted"></i></span>
                                    <input type="number" name="daily_limit" class="form-control bg-light border-start-0" value="<?php echo $daily_limit; ?>" placeholder="Limit">
                                </div>
                            </div>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-toggle-on text-muted"></i></span>
                            <select name="status" class="form-select bg-light border-start-0">
                                <option value="active" <?php echo ($status=='active')?'selected':''; ?>>Active</option>
                                <option value="completed" <?php echo ($status=='completed')?'selected':''; ?>>Completed</option>
                                <option value="paused" <?php echo ($status=='paused')?'selected':''; ?>>Paused</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="update_link" class="btn btn-primary shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border:none;"><i class="bi bi-save me-2"></i>Update</button>
                        <button type="submit" name="delete_page" class="btn btn-light text-danger shadow-sm" onclick="return confirm('Are you sure?');"><i class="bi bi-trash me-2"></i>Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Stats chart -->
<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-5">
            <h5 class="text-center mb-4 fw-bold text-secondary">📊 Page Clicks Stats</h5>
            
            <form method="GET" class="row g-3 justify-content-center mb-4">
                <div class="col-auto d-flex align-items-center">
                    <label class="me-2 fw-bold">From:</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-auto d-flex align-items-center">
                    <label class="me-2 fw-bold">To:</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="dashboard.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <canvas id="statsChart" height="150"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 mb-5">
            <h4 class="text-center mb-4">🌍 Visitors by Country</h4>
            <canvas id="countryChart" height="200"></canvas>
        </div>
    </div>
</div>

        </div>

        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; FB Money System <?php echo date('Y'); ?></div>
                    <div>
                        <a href="#">Privacy Policy</a>
                        &middot;
                        <a href="#">Terms &amp; Conditions</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<button id="backToTop" class="btn btn-primary rounded-circle shadow" style="position: fixed; bottom: 20px; right: 20px; display: none; z-index: 1000; width: 50px; height: 50px;">
    <i class="bi bi-arrow-up"></i>
</button>

<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="liveToast" class="toast align-items-center text-white <?php echo $toast_class; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <?php echo $message; ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Scan QR Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <h5 id="qrPageName" class="mb-3 text-primary"></h5>
        <img id="qrImage" src="" alt="QR Code" class="img-fluid border p-2 shadow-sm" style="min-width: 200px; min-height: 200px;">
        <p class="mt-3 text-muted small text-break" id="qrLinkText"></p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-success" onclick="downloadQRCode()">
            <i class="bi bi-download me-2"></i>Download QR Code
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const ctx = document.getElementById('statsChart').getContext('2d');
const statsChart = new Chart(ctx,{
    type:'bar',
    data:{
        labels: <?php echo json_encode(array_keys($pages)); ?>,
        datasets:[
            {
                label:'Follow',
                data: <?php echo json_encode(array_map(fn($p)=>$clicks[$p]['follow']??0,array_keys($pages))); ?>,
                backgroundColor:'rgba(24,119,242,0.7)'
            },
            {
                label:'Share',
                data: <?php echo json_encode(array_map(fn($p)=>$clicks[$p]['share']??0,array_keys($pages))); ?>,
                backgroundColor:'rgba(40,167,69,0.7)'
            }
        ]
    },
    options:{
        responsive:true,
        scales:{ y:{ beginAtZero:true } }
    }
});

// Country Pie Chart
const ctx2 = document.getElementById('countryChart').getContext('2d');
new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($c_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($c_data); ?>,
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69']
        }]
    }
});

// Toggle Sidebar
document.getElementById('sidebarToggle').addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('wrapper').classList.toggle('toggled');
});

// Close Sidebar when clicking outside
document.getElementById('page-content-wrapper').addEventListener('click', function(e) {
    if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) {
        document.getElementById('wrapper').classList.remove('toggled');
    }
});

// Close Sidebar
document.getElementById('sidebarClose').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('wrapper').classList.remove('toggled');
});

// Dark Mode Toggle
const toggle = document.getElementById('darkModeToggle');
const body = document.body;
if(localStorage.getItem('darkMode') === 'enabled'){
    body.classList.add('dark-mode');
    toggle.textContent = '☀️';
}
toggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if(body.classList.contains('dark-mode')){
        localStorage.setItem('darkMode', 'enabled');
        toggle.textContent = '☀️';
    } else {
        localStorage.setItem('darkMode', 'disabled');
        toggle.textContent = '🌙';
    }
});

// Page Search
document.getElementById('pageSearch').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let cards = document.querySelectorAll('#pageList .col-md-4');

    cards.forEach(card => {
        let title = card.querySelector('.page-title').textContent.toUpperCase();
        if (title.indexOf(filter) > -1) {
            card.style.display = "";
        } else {
            card.style.display = "none";
        }
    });
});

// Back to Top
const backToTop = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        backToTop.style.display = 'block';
    } else {
        backToTop.style.display = 'none';
    }
});
backToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Page Loader
window.addEventListener('load', function() {
    const loader = document.getElementById('page-loader');
    loader.style.opacity = '0';
    setTimeout(() => {
        loader.style.display = 'none';
    }, 500);
});

// Show Toast if message exists
<?php if($message): ?>
const toastEl = document.getElementById('liveToast');
const toast = new bootstrap.Toast(toastEl);
toast.show();
<?php endif; ?>

// Copy to Clipboard
function copyToClipboard(elementId, btn) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(copyText.value).then(() => animateCopyBtn(btn));
    } else {
        document.execCommand('copy'); // Fallback
        animateCopyBtn(btn);
    }
}
function animateCopyBtn(btn) {
    const icon = btn.querySelector('i');
    icon.classList.remove('bi-clipboard');
    icon.classList.add('bi-check-lg');
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-success');
    setTimeout(() => {
        icon.classList.remove('bi-check-lg');
        icon.classList.add('bi-clipboard');
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}

// Show QR Code
function showQRCode(url, name) {
    const qrModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('qrModal'));
    document.getElementById('qrPageName').textContent = name;
    document.getElementById('qrLinkText').textContent = url;
    document.getElementById('qrImage').src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(url);
    qrModal.show();
}

// Download QR Code
function downloadQRCode() {
    const img = document.getElementById('qrImage');
    const url = img.src;
    const name = document.getElementById('qrPageName').textContent || 'qrcode';
    
    fetch(url)
        .then(response => response.blob())
        .then(blob => {
            const blobUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = name.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '-qr.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(blobUrl);
        })
        .catch(err => console.error('Error downloading QR code:', err));
}

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
