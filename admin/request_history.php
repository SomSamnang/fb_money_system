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
if(isset($_POST['delete_item'])){
    $id = (int)$_POST['id'];
    $source = $_POST['source'];
    if($source == 'pages'){
        $conn->query("DELETE FROM pages WHERE id=$id");
    } else {
        $conn->query("DELETE FROM videos WHERE id=$id");
    }
    $message = "History item deleted!";
    $toast_class = "bg-danger";
}

// Handle Update Status
if(isset($_POST['update_status'])){
    $id = (int)$_POST['id'];
    $source = $_POST['source'];
    $new_status = $_POST['status'];
    
    if($source == 'pages'){
        $stmt = $conn->prepare("UPDATE pages SET status=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("UPDATE videos SET status=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
    }
    $message = "Status updated!";
    $toast_class = "bg-success";
}

// Fetch History (Union Pages and Videos)
$items = [];
$res1 = $conn->query("SELECT id, name as title, type, target_clicks as target, status, created_at, 'pages' as source FROM pages");
while($row = $res1->fetch_assoc()) $items[] = $row;

$res2 = $conn->query("SELECT id, title, platform as type, target_views as target, status, created_at, 'videos' as source FROM videos");
while($row = $res2->fetch_assoc()) $items[] = $row;

// Sort by newest first
usort($items, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>History Request - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">History Request</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">📜 History & Registration</h2>
                    <p class="text-muted mb-0">Register new boosts and view request history.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button type="button" class="btn btn-primary btn-lg rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#registerModal" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border:none;">
                        <i class="bi bi-plus-lg me-2"></i>Register New Request
                    </button>
                </div>
            </div>

            <!-- History List -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5" id="historyCard">
                <div class="card-header bg-white p-4 border-bottom-0">
                    <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-clock-history me-2"></i>History Request Boost</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">Name Page</th>
                                <th>Boost Type</th>
                                <th>Number</th>
                                <th>Status</th>
                                <th>Boost Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): 
                                $type_label = ucfirst(str_replace('_', ' ', $item['type']));
                                if($item['type'] == 'facebook_reel') $type_label = 'Boost Real';
                                if($item['type'] == 'page') $type_label = 'Boost Page';
                                if($item['type'] == 'other') $type_label = 'Boost View';
                                if($item['type'] == 'facebook') $type_label = 'Boost Video';
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary"><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?php echo $type_label; ?></span></td>
                                <td class="fw-bold"><?php echo number_format($item['target']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="source" value="<?php echo $item['source']; ?>">
                                        <button type="submit" name="update_status" value="<?php echo $item['status'] == 'active' ? 'paused' : 'active'; ?>" class="btn btn-sm <?php echo $item['status'] == 'active' ? 'btn-success' : 'btn-warning'; ?> rounded-pill px-3" title="Toggle Status">
                                            <?php echo ucfirst($item['status']); ?>
                                        </button>
                                        <input type="hidden" name="status" value="<?php echo $item['status'] == 'active' ? 'paused' : 'active'; ?>">
                                    </form>
                                </td>
                                <td class="text-muted small"><?php echo date("M d, Y", strtotime($item['created_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <form method="POST" onsubmit="return confirm('Delete this history item?');">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="source" value="<?php echo $item['source']; ?>">
                                        <button type="submit" name="delete_item" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"><div id="liveToast" class="toast align-items-center text-white <?php echo $toast_class; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body"><?php echo $message; ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div></div>
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