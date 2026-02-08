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
$toast_class = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['boost_follower'])) {
    $name = trim($_POST['page_name']);
    $link = trim($_POST['fb_link']);
    $target = (int)$_POST['target_followers'];
    $daily_limit = (int)$_POST['daily_limit'];
    
    if (!empty($name) && !empty($link)) {
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
             $message = "Invalid URL format.";
             $toast_class = "bg-danger";
        } else {
            // Check for duplicate link
            $check = $conn->prepare("SELECT id FROM pages WHERE fb_link = ?");
            $check->bind_param("s", $link);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = "This page is already being boosted!";
                $toast_class = "bg-warning";
            } else {
                // Insert into pages table (target_clicks serves as target followers)
                $stmt = $conn->prepare("INSERT INTO pages (name, fb_link, target_clicks, daily_limit, type) VALUES (?, ?, ?, ?, 'follower')");
                if ($stmt) {
                    $stmt->bind_param("ssii", $name, $link, $target, $daily_limit);
                    if ($stmt->execute()) {
                        $message = "Page successfully added for follower boosting!";
                        $toast_class = "bg-success";
                        logAction($conn, $_SESSION['admin'], 'Boost Follower', "Added page for followers: $name");
                    } else {
                        $message = "Error: " . $conn->error;
                        $toast_class = "bg-danger";
                    }
                    $stmt->close();
                }
            }
            $check->close();
        }
    } else {
        $message = "All fields are required.";
        $toast_class = "bg-danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Boost Followers - FB Money System</title>
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
body.dark-mode .form-control { background: #2d2d2d; border-color: #444; color: #e0e0e0; }
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
                <span class="navbar-text ms-auto fw-bold text-primary">Boost Followers</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg overflow-hidden rounded-4">
                        <div class="card-header text-white p-4 text-center" style="background: linear-gradient(135deg, #2af598 0%, #009efd 100%);">
                            <h2 class="fw-bold mb-1"><i class="bi bi-person-plus-fill me-2"></i>Boost Followers</h2>
                            <p class="mb-0 opacity-75">Grow your audience exponentially!</p>
                        </div>
                        <div class="card-body p-5">
                        <form method="POST">
                            <div class="mb-4"><label class="form-label fw-bold">Page Name</label><div class="input-group"><span class="input-group-text"><i class="bi bi-tag-fill"></i></span><input type="text" class="form-control form-control-lg" name="page_name" placeholder="e.g. My Brand Page" required></div></div>
                            <div class="mb-4"><label class="form-label fw-bold">Facebook Page URL</label><div class="input-group"><span class="input-group-text"><i class="bi bi-facebook"></i></span><input type="url" class="form-control form-control-lg" name="fb_link" placeholder="https://www.facebook.com/..." required></div></div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Target Followers</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="bi bi-bullseye"></i></span>
                                    <select class="form-select form-select-lg" id="target_select" onchange="updateTargetInput(this)">
                                        <option value="500">500 Followers</option>
                                        <option value="1000" selected>1,000 Followers</option>
                                        <option value="5000">5,000 Followers</option>
                                        <option value="10000">10,000 Followers</option>
                                        <option value="120000">120K Followers</option>
                                        <option value="1000000">1M Followers</option>
                                        <option value="44000000">44M Followers</option>
                                        <option value="120000000">120M Followers</option>
                                        <option value="custom">Custom Amount</option>
                                    </select>
                                </div>
                                <div id="custom_amount_wrapper" style="max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s ease-in-out;">
                                    <input type="number" class="form-control form-control-lg mt-2" name="target_followers" id="target_input" placeholder="Enter custom amount" value="1000" required>
                                </div>
                            </div>
                            <div class="mb-4"><label class="form-label fw-bold">Daily Limit (Optional)</label><div class="input-group"><span class="input-group-text"><i class="bi bi-speedometer2"></i></span><input type="number" class="form-control form-control-lg" name="daily_limit" placeholder="e.g. 100" value="0"></div></div>
                            <div class="d-grid"><button type="submit" name="boost_follower" class="btn btn-success btn-lg fw-bold shadow-sm" style="background: linear-gradient(135deg, #2af598 0%, #009efd 100%); border:none;"><i class="bi bi-graph-up-arrow me-2"></i>Start Boosting Followers</button></div>
                        </form>
                        </div>
                    </div>
                </div>
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

function updateTargetInput(select) {
    const input = document.getElementById('target_input');
    const wrapper = document.getElementById('custom_amount_wrapper');
    if(select.value === 'custom') {
        wrapper.style.maxHeight = '100px';
        wrapper.style.opacity = '1';
        input.value = '';
        input.required = true;
        input.focus();
    } else {
        wrapper.style.maxHeight = '0';
        wrapper.style.opacity = '0';
        input.value = select.value;
        input.required = false;
    }
}
</script>
</body>
</html>