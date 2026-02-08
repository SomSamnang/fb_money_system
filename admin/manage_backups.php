<?php
require_once 'auth.php';
require_once "../config/db.php";

// Admin-only page
$u_stmt = $conn->prepare("SELECT role FROM admins WHERE username=?");
$u_stmt->bind_param("s", $_SESSION['admin']);
$u_stmt->execute();
$u_role = $u_stmt->get_result()->fetch_assoc()['role'] ?? 'editor';
if ($u_role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

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
$backup_dir = dirname(__DIR__) . '/backups';

// Handle Delete
if (isset($_POST['delete_backup'])) {
    $filename = basename($_POST['filename']);
    $file_path = $backup_dir . '/' . $filename;

    // Security check: ensure file is in the backups dir and is a .sql file
    if (strpos(realpath($file_path), $backup_dir) === 0 && pathinfo($file_path, PATHINFO_EXTENSION) === 'sql') {
        if (unlink($file_path)) {
            $message = "Backup file '$filename' deleted successfully.";
            $toast_class = "bg-success";
            logAction($conn, $_SESSION['admin'], 'Delete Backup', "Deleted backup file: $filename");
        } else {
            $message = "Error deleting file.";
            $toast_class = "bg-danger";
        }
    } else {
        $message = "Invalid file specified.";
        $toast_class = "bg-danger";
    }
}

// Handle Restore
if (isset($_POST['restore_backup'])) {
    $filename = basename($_POST['filename']);
    $file_path = $backup_dir . '/' . $filename;

    // Security check
    if (strpos(realpath($file_path), $backup_dir) === 0 && pathinfo($file_path, PATHINFO_EXTENSION) === 'sql') {
        if (file_exists($file_path)) {
            $sql = file_get_contents($file_path);
            if ($conn->multi_query($sql)) {
                do {
                    if ($res = $conn->store_result()) { $res->free(); }
                } while ($conn->more_results() && $conn->next_result());
                
                $message = "Database restored successfully from '$filename'.";
                $toast_class = "bg-success";
                logAction($conn, $_SESSION['admin'], 'Restore Backup', "Restored DB from file: $filename");
            } else {
                $message = "Error restoring database: " . $conn->error;
                $toast_class = "bg-danger";
            }
        }
    } else {
        $message = "Invalid file specified.";
        $toast_class = "bg-danger";
    }
}

// Scan for backup files
$backup_files = [];
if (is_dir($backup_dir)) {
    $files = array_diff(scandir($backup_dir, SCANDIR_SORT_DESCENDING), array('..', '.'));
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($backup_dir . '/' . $file),
                'date' => filemtime($backup_dir . '/' . $file)
            ];
        }
    }
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Backups - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Manage Backups</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">🗄️ Manage Backups</h2>
                    <p class="text-muted mb-0">Download or delete saved database backups.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="backup.php" class="btn btn-primary btn-lg rounded-pill shadow-sm px-4">
                        <i class="bi bi-plus-lg me-2"></i>Create New Backup
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                <div class="card-header bg-white p-4 border-bottom-0">
                    <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-server me-2"></i>Saved Backup Files</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">Filename</th>
                                <th>Size</th>
                                <th>Date Created</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backup_files)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No backup files found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($backup_files as $file): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><i class="bi bi-file-earmark-zip-fill me-2"></i><?php echo htmlspecialchars($file['name']); ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?php echo formatBytes($file['size']); ?></span></td>
                                    <td class="text-muted small"><?php echo date('M d, Y H:i:s', $file['date']); ?></td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('WARNING: This will overwrite your current database! Are you sure you want to restore this backup?');">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file['name']); ?>">
                                            <button type="submit" name="restore_backup" class="btn btn-sm btn-warning text-dark me-1" title="Restore Database">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>
                                        <a href="../backups/<?php echo htmlspecialchars($file['name']); ?>" class="btn btn-sm btn-success me-1" download>
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this backup?');">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file['name']); ?>">
                                            <button type="submit" name="delete_backup" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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