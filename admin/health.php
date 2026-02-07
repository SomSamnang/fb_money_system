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

$message = "";
$msg_type = "";

// System Checks
$db_status = $conn->ping() ? 'Online' : 'Offline';
$server_os = php_uname('s') . ' ' . php_uname('r');
$php_version = phpversion();
$server_software = $_SERVER['SERVER_SOFTWARE'];
$mysql_version = $conn->server_info;

// Disk Space
$disk_free = disk_free_space(".");
$disk_total = disk_total_space(".");
$disk_used = $disk_total - $disk_free;
$disk_percent = round(($disk_used / $disk_total) * 100);

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 

$memory_usage = formatBytes(memory_get_usage());
$max_memory = ini_get('memory_limit');

$load = 'N/A';
if (function_exists('sys_getloadavg')) {
    $loads = sys_getloadavg();
    if ($loads) {
        $load = $loads[0] . ' (1m) / ' . $loads[1] . ' (5m) / ' . $loads[2] . ' (15m)';
    }
}

// Log Server Load (Once per minute)
$current_load = 0;
if (function_exists('sys_getloadavg')) {
    $l = sys_getloadavg();
    if ($l) $current_load = $l[0];
}
$last_log = $conn->query("SELECT recorded_at FROM server_metrics ORDER BY id DESC LIMIT 1");
$should_log = true;
if($last_log && $last_log->num_rows > 0){
    if(time() - strtotime($last_log->fetch_assoc()['recorded_at']) < 60) $should_log = false;
}
if($should_log) $conn->query("INSERT INTO server_metrics (load_val) VALUES ($current_load)");

// Fetch Chart Data
$metrics = $conn->query("SELECT * FROM (SELECT * FROM server_metrics ORDER BY id DESC LIMIT 20) sub ORDER BY id ASC");
$chart_labels = []; $chart_data = [];
while($row = $metrics->fetch_assoc()){
    $chart_labels[] = date('H:i', strtotime($row['recorded_at']));
    $chart_data[] = $row['load_val'];
}

// Handle Health Report
if (isset($_POST['send_health_report'])) {
    $issues = [];
    if ($db_status !== 'Online') $issues[] = "Database connection is unstable.";
    if ($disk_percent >= 90) $issues[] = "Disk usage is critical ($disk_percent%).";
    if (isset($loads) && $loads[0] > 5.0) $issues[] = "High server load detected: " . $loads[0];

    if (!empty($issues)) {
        $to = $u_row['email'];
        $subject = "⚠️ System Health Alert";
        $body = "Issues detected:\n" . implode("\n", $issues);
        $body .= "\n\nSnapshot:\nDB: $db_status\nDisk: $disk_percent%\nMemory: $memory_usage";
        
        // mail($to, $subject, $body); // Uncomment to send real email
        
        $message = "Issues detected! Alert email sent to $to.";
        $msg_type = "alert-warning";
        logAction($conn, $_SESSION['admin'], 'Health Check', "Failed. Alert sent.");
    } else {
        $message = "System is healthy. No email sent.";
        $msg_type = "alert-success";
        logAction($conn, $_SESSION['admin'], 'Health Check', "Passed.");
    }
}

// Handle Clear Cache
if (isset($_POST['clear_cache'])) {
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    clearstatcache();
    $message = "System cache (Opcache & Realpath) cleared successfully.";
    $msg_type = "alert-success";
    logAction($conn, $_SESSION['admin'], 'Clear Cache', "Cleared system cache.");
}

// Handle Optimize Database
if (isset($_POST['optimize_db'])) {
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $conn->query("OPTIMIZE TABLE " . $row[0]);
    }
    $message = "Database tables optimized successfully.";
    $msg_type = "alert-success";
    logAction($conn, $_SESSION['admin'], 'Optimize DB', "Optimized all database tables.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Health - FB Money System</title>
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
.navbar { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); background: #fff !important; position: sticky; top: 0; z-index: 100; }
/* Dark Mode Styles */
body.dark-mode { background: #121212; color: #e0e0e0; }
body.dark-mode .card { background: #1e1e1e; color: #e0e0e0; }
body.dark-mode .navbar { background: #1e1e1e !important; color: #e0e0e0; }
body.dark-mode .list-group-item { background-color: #1e1e1e; color: #e0e0e0; border-color: #444; }

.health-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08); overflow: hidden; }
.health-header { background: linear-gradient(45deg, #1cc88a, #13855c); color: white; padding: 1.5rem; }

#wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
@media (max-width: 768px) {
    #sidebar-wrapper { margin-left: -250px; position: fixed; z-index: 1000; height: 100%; top: 0; left: 0; }
    #wrapper.toggled #sidebar-wrapper { margin-left: 0; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
    #page-content-wrapper { width: 100%; min-width: 100%; }
    #wrapper.toggled #page-content-wrapper::before {
        content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;
    }
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
                <span class="navbar-text ms-auto fw-bold text-primary">System Health</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="card health-card mb-4">
                <div class="health-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-bold"><i class="bi bi-heart-pulse-fill me-2"></i>System Status</h4>
                        <p class="mb-0 opacity-75 small">Real-time monitoring of server and database health.</p>
                    </div>
                    <form method="POST" class="d-flex gap-2">
                        <a href="phpinfo.php" target="_blank" class="btn btn-light text-primary fw-bold btn-sm shadow-sm d-flex align-items-center"><i class="bi bi-info-circle-fill me-1"></i> PHP Info</a>
                        <a href="server_logs.php" class="btn btn-light text-secondary fw-bold btn-sm shadow-sm d-flex align-items-center"><i class="bi bi-terminal-fill me-1"></i> Logs</a>
                        <button type="submit" name="optimize_db" class="btn btn-light text-info fw-bold btn-sm shadow-sm" onclick="return confirm('Optimize database tables? This might take a moment.');"><i class="bi bi-database-gear me-1"></i> Optimize DB</button>
                        <button type="submit" name="clear_cache" class="btn btn-light text-warning fw-bold btn-sm shadow-sm" onclick="return confirm('Clear system cache?');"><i class="bi bi-lightning-charge-fill me-1"></i> Clear Cache</button>
                        <button type="submit" name="send_health_report" class="btn btn-light text-success fw-bold btn-sm shadow-sm"><i class="bi bi-envelope-check-fill me-1"></i> Run Check</button>
                    </form>
                </div>
                <div class="card-body p-4">
                    <?php if($message): ?>
                        <div class="alert <?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mb-3">ℹ️ System Info</h5>
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <div class="small text-muted fw-bold text-uppercase">Operating System</div>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($server_os); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <div class="small text-muted fw-bold text-uppercase">PHP Version</div>
                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($php_version); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <div class="small text-muted fw-bold text-uppercase">MySQL Version</div>
                                <div class="fw-bold text-warning"><?php echo htmlspecialchars($mysql_version); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Database Status -->
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 border rounded h-100 d-flex align-items-center">
                                <div class="fs-1 me-3 <?php echo $db_status == 'Online' ? 'text-success' : 'text-danger'; ?>">
                                    <i class="bi bi-database-check"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">Database Connection</h6>
                                    <span class="badge <?php echo $db_status == 'Online' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $db_status; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Server Load -->
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 border rounded h-100 d-flex align-items-center">
                                <div class="fs-1 me-3 text-warning">
                                    <i class="bi bi-speedometer"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">Server Load</h6>
                                    <span class="text-muted small"><?php echo $load; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Memory Usage -->
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 border rounded h-100 d-flex align-items-center">
                                <div class="fs-1 me-3 text-info">
                                    <i class="bi bi-memory"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">Memory Usage</h6>
                                    <span class="text-muted"><?php echo $memory_usage; ?> / <?php echo $max_memory; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Server Software -->
                        <div class="col-md-6 col-lg-8">
                            <div class="p-3 border rounded h-100 d-flex align-items-center">
                                <div class="fs-1 me-3 text-secondary">
                                    <i class="bi bi-hdd-rack"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">Server Software</h6>
                                    <span class="text-muted small"><?php echo htmlspecialchars($server_software); ?></span>
                                    <div class="small text-muted fst-italic"><?php echo htmlspecialchars($server_os); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Disk Usage -->
                    <h5 class="mb-3">💾 Disk Usage</h5>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar <?php echo $disk_percent > 80 ? 'bg-danger' : ($disk_percent > 50 ? 'bg-warning' : 'bg-success'); ?>" role="progressbar" style="width: <?php echo $disk_percent; ?>%;" aria-valuenow="<?php echo $disk_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $disk_percent; ?>%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Used: <?php echo formatBytes($disk_used); ?></span>
                        <span>Total: <?php echo formatBytes($disk_total); ?></span>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3">📈 Server Load History (1m Avg)</h5>
                    <canvas id="loadChart" height="100"></canvas>
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

const ctx = document.getElementById('loadChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Server Load',
            data: <?php echo json_encode($chart_data); ?>,
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.1)',
            fill: true, tension: 0.3
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>