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

// Date Filter Logic
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. Page Performance (Follow vs Share)
$sql = "SELECT page, type, COUNT(*) as total FROM clicks WHERE click_date BETWEEN ? AND ? GROUP BY page, type";
$stmt = $conn->prepare($sql);
$clicks_data = [];
$pages_list = [];

if ($stmt) {
    $s = $start_date . " 00:00:00";
    $e = $end_date . " 23:59:59";
    $stmt->bind_param("ss", $s, $e);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $clicks_data[$row['page']][$row['type']] = $row['total'];
        $pages_list[$row['page']] = true;
    }
    $stmt->close();
}
$pages_keys = array_keys($pages_list);

// 2. Daily Activity
$daily_sql = "SELECT DATE(click_date) as date, COUNT(*) as total FROM clicks WHERE click_date BETWEEN ? AND ? GROUP BY DATE(click_date) ORDER BY date ASC";
$d_stmt = $conn->prepare($daily_sql);
$daily_labels = [];
$daily_data = [];

if ($d_stmt) {
    $d_stmt->bind_param("ss", $s, $e);
    $d_stmt->execute();
    $d_res = $d_stmt->get_result();
    while($row = $d_res->fetch_assoc()){
        $daily_labels[] = date('M d', strtotime($row['date']));
        $daily_data[] = $row['total'];
    }
    $d_stmt->close();
}

// 3. Country Stats
$country_res = $conn->query("SELECT country, COUNT(*) as total FROM visitors GROUP BY country ORDER BY total DESC LIMIT 10");
$c_labels = [];
$c_data = [];
if ($country_res) {
    while($row = $country_res->fetch_assoc()){
        $c_labels[] = $row['country'] ? $row['country'] : 'Unknown';
        $c_data[] = $row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Reports</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <h4 class="mb-3">📊 Analytics Reports</h4>
            
            <!-- Filter -->
            <div class="card p-3 mb-4 shadow-sm">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter Reports</button>
                    </div>
                </form>
            </div>

            <div class="row g-4 mb-4">
                <!-- Daily Trend -->
                <div class="col-lg-8">
                    <div class="card p-4 h-100 shadow-sm">
                        <h5 class="card-title mb-4">📈 Daily Activity Trend</h5>
                        <canvas id="dailyChart" height="150"></canvas>
                    </div>
                </div>
                <!-- Country Stats -->
                <div class="col-lg-4">
                    <div class="card p-4 h-100 shadow-sm">
                        <h5 class="card-title mb-4">🌍 Visitors by Country</h5>
                        <canvas id="countryChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Page Performance -->
            <div class="card p-4 mb-4 shadow-sm">
                <h5 class="card-title mb-4">📊 Page Performance (Follow vs Share)</h5>
                <canvas id="pageChart" height="100"></canvas>
            </div>

            <!-- Detailed Table -->
            <div class="card p-4 shadow-sm">
                <h5 class="card-title mb-3">📋 Detailed Summary</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Page Name</th>
                                <th>Follows</th>
                                <th>Shares</th>
                                <th>Total Interactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($pages_keys)): ?>
                                <?php foreach($pages_keys as $page): 
                                    $f = $clicks_data[$page]['follow'] ?? 0;
                                    $s = $clicks_data[$page]['share'] ?? 0;
                                ?>
                                <tr>
                                    <td class="text-capitalize fw-bold"><?php echo htmlspecialchars($page); ?></td>
                                    <td><?php echo $f; ?></td>
                                    <td><?php echo $s; ?></td>
                                    <td><?php echo $f + $s; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">No data found for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

// Daily Chart
const ctxDaily = document.getElementById('dailyChart').getContext('2d');
new Chart(ctxDaily, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            label: 'Total Clicks',
            data: <?php echo json_encode($daily_data); ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Country Chart
const ctxCountry = document.getElementById('countryChart').getContext('2d');
new Chart(ctxCountry, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($c_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($c_data); ?>,
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69']
        }]
    }
});

// Page Chart
const ctxPage = document.getElementById('pageChart').getContext('2d');
new Chart(ctxPage, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($pages_keys); ?>,
        datasets: [
            {
                label: 'Follow',
                data: <?php echo json_encode(array_map(fn($p)=>$clicks_data[$p]['follow']??0, $pages_keys)); ?>,
                backgroundColor: '#4e73df'
            },
            {
                label: 'Share',
                data: <?php echo json_encode(array_map(fn($p)=>$clicks_data[$p]['share']??0, $pages_keys)); ?>,
                backgroundColor: '#1cc88a'
            }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>