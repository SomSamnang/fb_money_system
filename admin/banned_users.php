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

// Restrict access to Admins only
if ($user_role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$toast_class = "";

// Handle Unban
if(isset($_POST['unban_user'])){
    $user_id = (int)$_POST['user_id'];
    $conn->query("UPDATE admins SET is_banned=0 WHERE id=$user_id");
    logAction($conn, $_SESSION['admin'], 'Unban User', "Unbanned user ID: $user_id");
    $message = "User unbanned successfully!";
    $toast_class = "bg-success";
}

// Fetch banned users
$search = $_GET['search'] ?? '';
if ($search) {
    $term = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM admins WHERE is_banned=1 AND (username LIKE ? OR email LIKE ?) ORDER BY id DESC");
    $stmt->bind_param("ss", $term, $term);
    $stmt->execute();
    $banned_users = $stmt->get_result();
} else {
    $banned_users = $conn->query("SELECT * FROM admins WHERE is_banned=1 ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Banned Users - FB Money System</title>
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
.card { border:none; border-radius:15px; box-shadow:0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); margin-bottom: 20px; }
.navbar { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); background: #fff !important; position: sticky; top: 0; z-index: 100; }
/* Dark Mode Styles */
body.dark-mode { background: #121212; color: #e0e0e0; }
body.dark-mode .card { background: #1e1e1e; color: #e0e0e0; }
body.dark-mode .navbar { background: #1e1e1e !important; color: #e0e0e0; }
body.dark-mode .form-control { background: #2d2d2d; border-color: #444; color: #e0e0e0; }
body.dark-mode .table { color: #e0e0e0; }

.banned-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08); overflow: hidden; }
.banned-header { background: linear-gradient(45deg, #e74a3b, #c0392b); color: white; padding: 1.5rem; }
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
                <span class="navbar-text ms-auto fw-bold text-primary">Banned Users</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="card banned-card mb-5">
                <div class="banned-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-bold"><i class="bi bi-slash-circle-fill me-2"></i>Banned Users</h4>
                        <p class="mb-0 opacity-75 small">Manage users who have been restricted from accessing the system.</p>
                    </div>
                    <div>
                        <form method="GET" class="d-flex align-items-center bg-white rounded p-1">
                            <input type="text" name="search" class="form-control border-0 shadow-none form-control-sm" placeholder="Search banned users..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 200px;">
                            <button type="submit" class="btn btn-danger btn-sm rounded-circle" style="width: 30px; height: 30px; padding: 0;"><i class="bi bi-search"></i></button>
                            <?php if($search): ?><a href="banned_users.php" class="btn btn-light btn-sm ms-1 rounded-circle text-danger d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;"><i class="bi bi-x-lg"></i></a><?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="table-responsive p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Ban Reason</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($banned_users && $banned_users->num_rows > 0): ?>
                                <?php while($u = $banned_users->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 text-danger fw-bold border border-danger" style="width:40px; height:40px; font-size: 1.1rem;">
                                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($u['username']); ?></div>
                                                <div class="small text-muted" style="font-size: 0.75rem;">ID: #<?php echo $u['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-secondary"><i class="bi bi-envelope me-2 opacity-50"></i><?php echo htmlspecialchars($u['email'] ?? 'N/A'); ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill"><?php echo htmlspecialchars(ucfirst($u['role'] ?? 'editor')); ?></span></td>
                                    <td class="text-danger small fw-bold"><i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($u['ban_reason'] ?? 'No reason provided'); ?></td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="unban_user" class="btn btn-sm btn-success shadow-sm px-3" onclick="return confirm('Unban this user?');">
                                                <i class="bi bi-check-circle-fill me-1"></i> Unban User
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-emoji-smile fs-1 d-block mb-2"></i>No banned users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); document.getElementById('wrapper').classList.toggle('toggled'); });
document.getElementById('page-content-wrapper').addEventListener('click', function(e) { if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) { document.getElementById('wrapper').classList.remove('toggled'); } });
document.getElementById('sidebarClose').addEventListener('click', function(e) { e.preventDefault(); document.getElementById('wrapper').classList.remove('toggled'); });
const toggle = document.getElementById('darkModeToggle'); const body = document.body; if(localStorage.getItem('darkMode') === 'enabled'){ body.classList.add('dark-mode'); toggle.textContent = '☀️'; } toggle.addEventListener('click', () => { body.classList.toggle('dark-mode'); localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : 'disabled'); toggle.textContent = body.classList.contains('dark-mode') ? '☀️' : '🌙'; });

<?php if($message): ?>
const toastEl = document.getElementById('liveToast');
const toast = new bootstrap.Toast(toastEl);
toast.show();
<?php endif; ?>
</script>
</body>
</html>