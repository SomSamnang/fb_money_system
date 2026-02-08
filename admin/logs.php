<?php
require_once 'auth.php';
require_once "../config/db.php";

$message = "";
$msg_type = "";

// Handle Clear Logs
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_logs'])) {
    if ($conn->query("TRUNCATE TABLE system_logs")) {
        // Log this action. This will be the only log left.
        logAction($conn, $_SESSION['admin'], 'Clear Logs', 'All system logs were cleared.');
        $message = "System logs have been cleared successfully.";
        $msg_type = "alert-success";
    } else {
        $message = "Error clearing logs.";
        $msg_type = "alert-danger";
    }
}

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
if (!$u_row) $u_row = []; // Ensure it is an array if user not found
$profile_pic = !empty($u_row['profile_pic']) ? "uploads/" . $u_row['profile_pic'] : "https://via.placeholder.com/150";
$last_login = !empty($u_row['last_login']) ? date("M d, Y h:i A", strtotime($u_row['last_login'])) : "First Login";
$bio = $u_row['bio'] ?? '';
$user_role = $u_row['role'] ?? 'editor';

// Fetch logs
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM system_logs";
if ($search) {
    $sql .= " WHERE username LIKE ? OR action LIKE ? OR details LIKE ? OR ip_address LIKE ?";
}
$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if ($search) {
    $term = "%$search%";
    $stmt->bind_param("ssss", $term, $term, $term, $term);
}
$stmt->execute();
$logs_res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Logs - FB Money System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
.navbar { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); background: #fff !important; position: sticky; top: 0; z-index: 100; }
/* Dark Mode Styles */
body.dark-mode { background: #121212; color: #e0e0e0; }
body.dark-mode .card { background: #1e1e1e; color: #e0e0e0; }
body.dark-mode .navbar { background: #1e1e1e !important; color: #e0e0e0; }
body.dark-mode .form-control { background: #2d2d2d; border-color: #444; color: #e0e0e0; }
body.dark-mode .table { color: #e0e0e0; }
body.dark-mode .log-header { background: linear-gradient(45deg, #2c3e50, #000); }
#wrapper.toggled #sidebar-wrapper { margin-left: -250px; }

.log-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08); overflow: hidden; }
.log-header { background: linear-gradient(45deg, #4e73df, #224abe); color: white; padding: 1.5rem; }
.table thead th { 
    border-top: none; 
    border-bottom: 2px solid #e3e6f0; 
    font-weight: 600; 
    text-transform: uppercase; 
    font-size: 0.85rem; 
    color: #858796;
    background: #f8f9fc;
}
body.dark-mode .table thead th { background: #1e1e1e; color: #b0b0b0; border-color: #444; }
.table td { vertical-align: middle; font-size: 0.9rem; border-color: #e3e6f0; }
body.dark-mode .table td { border-color: #444; }

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
                <span class="navbar-text ms-auto fw-bold text-primary">System Logs</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="card log-card mb-4">
                <div class="log-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-bold"><i class="bi bi-activity me-2"></i>System Activity Logs</h4>
                        <p class="mb-0 opacity-75 small">Monitor user actions and system events in real-time.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="GET" class="d-flex bg-white rounded p-1">
                            <input type="text" name="search" class="form-control border-0 shadow-none form-control-sm" placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 200px;">
                            <button type="submit" class="btn btn-primary btn-sm rounded"><i class="bi bi-search"></i></button>
                            <?php if($search): ?><a href="logs.php" class="btn btn-light btn-sm ms-1 rounded text-danger"><i class="bi bi-x-lg"></i></a><?php endif; ?>
                        </form>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to clear all logs?');">
                            <button type="submit" name="clear_logs" class="btn btn-danger btn-sm h-100 shadow-sm"><i class="bi bi-trash3-fill me-1"></i> Clear</button>
                        </form>
                    </div>
                </div>
                <div class="table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-4">Date & Time</th>
                                <th scope="col">User</th>
                                <th scope="col">Action</th>
                                <th scope="col">Details</th>
                                <th scope="col">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($logs_res && $logs_res->num_rows > 0): ?>
                                <?php while($log = $logs_res->fetch_assoc()): 
                                    $action_class = 'bg-secondary';
                                    $icon = 'bi-circle';
                                    if (stripos($log['action'], 'Login') !== false) { $action_class = 'bg-success'; $icon = 'bi-box-arrow-in-right'; }
                                    elseif (stripos($log['action'], 'Register') !== false) { $action_class = 'bg-info text-dark'; $icon = 'bi-person-plus'; }
                                    elseif (stripos($log['action'], 'Add') !== false) { $action_class = 'bg-primary'; $icon = 'bi-plus-circle'; }
                                    elseif (stripos($log['action'], 'Update') !== false) { $action_class = 'bg-warning text-dark'; $icon = 'bi-pencil-square'; }
                                    elseif (stripos($log['action'], 'Delete') !== false || stripos($log['action'], 'Clear') !== false) { $action_class = 'bg-danger'; $icon = 'bi-trash'; }
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted small fw-bold"><?php echo date("M d, Y h:i A", strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2 text-primary fw-bold border" style="width:35px; height:35px;">
                                                <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                            </div>
                                            <span class="fw-bold"><?php echo htmlspecialchars($log['username']); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge <?php echo $action_class; ?> rounded-pill px-3 py-2"><i class="bi <?php echo $icon; ?> me-1"></i> <?php echo htmlspecialchars($log['action']); ?></span></td>
                                    <td class="text-secondary"><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><span class="font-monospace small bg-light border rounded px-2 py-1 text-muted"><?php echo htmlspecialchars($log['ip_address']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No logs found matching your criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
  <div id="liveToast" class="toast align-items-center text-white <?php echo str_replace('alert-', 'bg-', $msg_type); ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <?php echo $message; ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script>
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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>