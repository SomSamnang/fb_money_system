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
if (!$u_row) $u_row = []; // Ensure it is an array if user not found
$profile_pic = !empty($u_row['profile_pic']) ? "uploads/" . $u_row['profile_pic'] : "https://via.placeholder.com/150";
$last_login = !empty($u_row['last_login']) ? date("M d, Y h:i A", strtotime($u_row['last_login'])) : "First Login";
$bio = $u_row['bio'] ?? '';
$user_role = $u_row['role'] ?? 'editor';

$message = "";
$msg_type = "";

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $site_title = trim($_POST['site_title']);
    $sidebar_color = trim($_POST['sidebar_color']);
    
    // Upsert Site Title
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_title', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $site_title, $site_title);
        $stmt->execute();
        $stmt->close();
    }

    // Upsert Sidebar Color
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('sidebar_color', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $sidebar_color, $sidebar_color);
        $stmt->execute();
        $stmt->close();
    }
    
    // Handle Notification Sound Upload
    if (isset($_FILES['notification_sound']) && $_FILES['notification_sound']['error'] == 0) {
        $allowed = ['mp3', 'wav', 'ogg'];
        $filename = $_FILES['notification_sound']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = "notif_sound_" . time() . "." . $ext;
            $upload_dir = __DIR__ . '/uploads';
            if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
            
            if (move_uploaded_file($_FILES['notification_sound']['tmp_name'], $upload_dir . '/' . $new_name)) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('notification_sound', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("ss", $new_name, $new_name);
                $stmt->execute();
                $stmt->close();
            } else {
                 $message .= " Error uploading sound file.";
                 $msg_type = "alert-warning";
            }
        } else {
            $message .= " Invalid sound format (MP3, WAV, OGG only).";
            $msg_type = "alert-warning";
        }
    }

    $message = "Settings updated successfully!";
    $msg_type = "alert-success";
    logAction($conn, $_SESSION['admin'], 'Update Settings', "Updated Site Settings");
}

// Handle Restore
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore_database'])) {
    if ($user_role === 'admin') {
        if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] == 0) {
            $sql = file_get_contents($_FILES['restore_file']['tmp_name']);
            if ($conn->multi_query($sql)) {
                do {
                    if ($res = $conn->store_result()) { $res->free(); }
                } while ($conn->more_results() && $conn->next_result());
                
                $message = "Database restored successfully!";
                $msg_type = "alert-success";
                logAction($conn, $_SESSION['admin'], 'Restore Database', "Restored DB from file");
            } else {
                $message = "Error restoring database: " . $conn->error;
                $msg_type = "alert-danger";
            }
        } else {
            $message = "File upload failed.";
            $msg_type = "alert-danger";
        }
    } else {
        $message = "Unauthorized action.";
        $msg_type = "alert-danger";
    }
}

// Fetch current settings
$current_title = "FB Money System"; // Default
$current_sidebar_color = "#4e73df"; // Default
$current_notif_sound = "";

$res = $conn->query("SELECT * FROM settings");
if ($res) {
    while($row = $res->fetch_assoc()){
        if($row['setting_key'] == 'site_title') $current_title = $row['setting_value'];
        if($row['setting_key'] == 'sidebar_color') $current_sidebar_color = $row['setting_value'];
        if($row['setting_key'] == 'notification_sound') $current_notif_sound = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Site Settings</span>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="card p-4">
                <h4 class="mb-3">⚙️ Global Configuration</h4>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Site Title</label>
                        <input type="text" class="form-control" name="site_title" value="<?php echo htmlspecialchars($current_title); ?>" required>
                        <div class="form-text">This title will appear on the main index page.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sidebar Color</label>
                        <input type="color" class="form-control form-control-color" name="sidebar_color" value="<?php echo htmlspecialchars($current_sidebar_color); ?>" title="Choose your color">
                        <div class="form-text">Select a custom background color for the admin sidebar.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notification Sound</label>
                        <input type="file" class="form-control" name="notification_sound" accept=".mp3,.wav,.ogg">
                        <div class="form-text">Upload a custom sound file (MP3, WAV, OGG). Leave empty to keep current.</div>
                        <?php if($current_notif_sound): ?>
                            <div class="mt-2"><small class="text-success"><i class="bi bi-check-circle"></i> Current: <?php echo htmlspecialchars($current_notif_sound); ?></small></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="update_settings" class="btn btn-primary">Save Changes</button>
                </form>
                
                <hr class="my-4">
                <h5 class="mb-3">💾 Database Backup</h5>
                <p class="text-muted small">Download a full SQL backup of your database structure and data.</p>
                <a href="backup.php" class="btn btn-success"><i class="bi bi-download me-2"></i>Download SQL Backup</a>
                
                <hr class="my-4">
                <h5 class="mb-3 text-danger">⚠️ Restore Database</h5>
                <p class="text-muted small">Upload a SQL file to restore the database. <strong>Warning: This will overwrite existing data!</strong></p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="file" class="form-control" name="restore_file" accept=".sql" required>
                        <button type="submit" name="restore_database" class="btn btn-danger" onclick="return confirm('Are you sure? This will overwrite all current data!');"><i class="bi bi-upload me-2"></i>Restore</button>
                    </div>
                </form>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('wrapper').classList.toggle('toggled');
});
document.getElementById('page-content-wrapper').addEventListener('click', function(e) {
    if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) {
        document.getElementById('wrapper').classList.remove('toggled');
    }
});
document.getElementById('sidebarClose').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('wrapper').classList.remove('toggled');
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
</body>
</html>