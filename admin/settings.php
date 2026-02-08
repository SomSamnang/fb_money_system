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
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 'on' : 'off';
    $maintenance_message = trim($_POST['maintenance_message']);
    $maintenance_end_time = $_POST['maintenance_end_time'];
    $terms_of_service = $_POST['terms_of_service'];
    $privacy_policy = $_POST['privacy_policy'];
    
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

    // Upsert Maintenance Mode
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $maintenance_mode, $maintenance_mode);
        $stmt->execute();
        $stmt->close();
    }

    // Upsert Maintenance Message
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_message', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $maintenance_message, $maintenance_message);
        $stmt->execute();
        $stmt->close();
    }

    // Upsert Maintenance End Time
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_end_time', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $maintenance_end_time, $maintenance_end_time);
        $stmt->execute();
        $stmt->close();
    }

    // Upsert Terms of Service
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('terms_of_service', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $terms_of_service, $terms_of_service);
        $stmt->execute();
        $stmt->close();
    }

    // Upsert Privacy Policy
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('privacy_policy', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $privacy_policy, $privacy_policy);
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
$current_maintenance = "off";
$current_maintenance_msg = "We are currently performing scheduled maintenance.<br>We will be back shortly.";
$current_maintenance_end = "";
$current_tos = <<<EOT
<p><strong>1. Acceptance</strong><br>By using this system, you agree to these terms.</p>
<p><strong>2. Account</strong><br>You are responsible for your account security.</p>
<p><strong>3. Conduct</strong><br>You agree not to misuse the system.</p>
EOT;

$current_pp = <<<EOT
<p><strong>Privacy Policy</strong><br>Your privacy is important to us.</p>
<p>We collect basic information to provide our services.</p>
EOT;

$res = $conn->query("SELECT * FROM settings");
if ($res) {
    while($row = $res->fetch_assoc()){
        if($row['setting_key'] == 'site_title') $current_title = $row['setting_value'];
        if($row['setting_key'] == 'sidebar_color') $current_sidebar_color = $row['setting_value'];
        if($row['setting_key'] == 'notification_sound') $current_notif_sound = $row['setting_value'];
        if($row['setting_key'] == 'maintenance_mode') $current_maintenance = $row['setting_value'];
        if($row['setting_key'] == 'maintenance_message') $current_maintenance_msg = $row['setting_value'];
        if($row['setting_key'] == 'maintenance_end_time') {
            $current_maintenance_end = $row['setting_value'];
            if ($current_maintenance_end) {
                $current_maintenance_end = date('Y-m-d\TH:i', strtotime($current_maintenance_end));
            }
        }
        if($row['setting_key'] == 'terms_of_service') $current_tos = $row['setting_value'];
        if($row['setting_key'] == 'privacy_policy') $current_pp = $row['setting_value'];
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
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">⚙️ Site Settings</h2>
                    <p class="text-muted mb-0">Configure global system preferences.</p>
                </div>
            </div>

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border:none;">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-sliders me-2"></i>Global Configuration</h5>
                </div>
                <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Site Title</label>
                        <input type="text" class="form-control" name="site_title" value="<?php echo htmlspecialchars($current_title); ?>" required>
                        <div class="form-text">This title will appear on the main index page.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Sidebar Color</label>
                        <input type="color" class="form-control form-control-color" name="sidebar_color" value="<?php echo htmlspecialchars($current_sidebar_color); ?>" title="Choose your color">
                        <div class="form-text">Select a custom background color for the admin sidebar.</div>
                    </div>
                    <div class="mb-4 form-check form-switch p-3 bg-light rounded border">
                        <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenance_mode" <?php echo ($current_maintenance == 'on') ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold ms-2" for="maintenanceMode">Maintenance Mode</label>
                        <div class="form-text text-danger ms-2">When enabled, only Admins can access the public site.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Maintenance Message</label>
                        <textarea class="form-control" name="maintenance_message" rows="3"><?php echo htmlspecialchars($current_maintenance_msg); ?></textarea>
                        <div class="form-text">Custom message to display when maintenance mode is active. HTML allowed.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Maintenance End Time (Optional)</label>
                        <input type="datetime-local" class="form-control" name="maintenance_end_time" value="<?php echo htmlspecialchars($current_maintenance_end); ?>">
                        <div class="form-text">Set a target time for maintenance completion to show a countdown timer.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Terms of Service Content</label>
                        <textarea class="form-control" name="terms_of_service" rows="6"><?php echo htmlspecialchars($current_tos); ?></textarea>
                        <div class="form-text">HTML content for the Terms of Service modal.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Privacy Policy Content</label>
                        <textarea class="form-control" name="privacy_policy" rows="6"><?php echo htmlspecialchars($current_pp); ?></textarea>
                        <div class="form-text">HTML content for the Privacy Policy modal.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Notification Sound</label>
                        <input type="file" class="form-control" name="notification_sound" accept=".mp3,.wav,.ogg">
                        <div class="form-text">Upload a custom sound file (MP3, WAV, OGG). Leave empty to keep current.</div>
                        <?php if($current_notif_sound): ?>
                            <div class="mt-2"><small class="text-success"><i class="bi bi-check-circle"></i> Current: <?php echo htmlspecialchars($current_notif_sound); ?></small></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="update_settings" class="btn btn-primary shadow-sm px-4"><i class="bi bi-save me-2"></i>Save Changes</button>
                </form>
                </div>
            </div>
                
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-success text-white p-4 border-0" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-database-down me-2"></i>Database Backup</h5>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted">Download a full SQL backup of your database structure and data.</p>
                            <a href="backup.php" class="btn btn-success w-100 shadow-sm"><i class="bi bi-download me-2"></i>Download SQL Backup</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-danger text-white p-4 border-0" style="background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%);">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-database-up me-2"></i>Restore Database</h5>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted">Upload a SQL file to restore. <strong>Warning: Overwrites data!</strong></p>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="restore_file" accept=".sql" required>
                                    <button type="submit" name="restore_database" class="btn btn-danger shadow-sm" onclick="return confirm('Are you sure? This will overwrite all current data!');"><i class="bi bi-upload me-2"></i>Restore</button>
                                </div>
                            </form>
                        </div>
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

// Dark Mode Toggle
const toggle = document.getElementById('darkModeToggle');
const body = document.body;
if(localStorage.getItem('darkMode') === 'enabled'){
    body.classList.add('dark-mode');
    toggle.textContent = '☀️';
}
toggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
    toggle.textContent = body.classList.contains('dark-mode') ? '☀️' : '🌙';
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