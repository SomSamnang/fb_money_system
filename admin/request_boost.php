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

// Handle Registration Request Boost
if(isset($_POST['register_boost'])){
    $name = trim($_POST['name']);
    $link = trim($_POST['link']);
    $type = $_POST['type'];
    $target = (int)$_POST['target'];
    $status = 'active';
    
    if(!empty($name) && !empty($link)){
        if($type == 'page' || $type == 'follower' || $type == 'post'){
            $stmt = $conn->prepare("INSERT INTO pages (name, fb_link, target_clicks, type, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $name, $link, $target, $type, $status);
            $stmt->execute();
            $stmt->close();
        } else {
            $platform = 'youtube'; // Default
            if($type == 'reel') $platform = 'facebook_reel';
            if($type == 'view') $platform = 'other';
            if($type == 'video') $platform = 'facebook';
            
            $stmt = $conn->prepare("INSERT INTO videos (title, video_link, target_views, platform, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $name, $link, $target, $platform, $status);
            $stmt->execute();
            $stmt->close();
        }
        $message = "Boost request registered successfully!";
        $toast_class = "bg-success";
        logAction($conn, $_SESSION['admin'], 'Register Boost', "Added $type boost for: $name");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Boost - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Request Boost</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">⚡ Registration Request Boost</h2>
                    <p class="text-muted mb-0">Create a new boost campaign for pages or videos.</p>
                </div>
            </div>

            <!-- Registration Request Boost Form -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                <div class="card-header text-white p-4 text-center" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border:none;">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-lightning-charge-fill me-2"></i>Registration Request Boost</h5>
                    <p class="mb-0 opacity-75">Quickly register a new boost campaign.</p>
                </div>
                <div class="card-body p-5">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Name Page / Video</label>
                                <div class="input-group"><span class="input-group-text"><i class="bi bi-tag-fill"></i></span><input type="text" name="name" class="form-control form-control-lg" placeholder="Enter Name..." required></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Link URL</label>
                                <div class="input-group"><span class="input-group-text"><i class="bi bi-link-45deg"></i></span><input type="text" name="link" class="form-control form-control-lg" placeholder="https://..." required></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Boost Type</label>
                                <div class="input-group"><span class="input-group-text"><i class="bi bi-collection-play-fill"></i></span>
                                <select name="type" class="form-select form-select-lg">
                                    <option value="follower">Boost Follower</option>
                                    <option value="page">Boost Page</option>
                                    <option value="post">Boost Post</option>
                                    <option value="view">Boost View</option>
                                    <option value="reel">Boost Reel</option>
                                    <option value="video">Boost Video</option>
                                </select></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Number (Target)</label>
                                <div class="input-group"><span class="input-group-text"><i class="bi bi-bullseye"></i></span><select name="target" class="form-select form-select-lg"><option value="1000">1K</option><option value="2000">2K</option><option value="3000">3K</option><option value="4000">4K</option><option value="1000000">1M</option><option value="2000000">2M</option><option value="100000000">100M</option><option value="200000000">200M</option><option value="420000000">420M</option><option value="440000000">440M</option><option value="500000000">500M</option></select></div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <button type="reset" class="btn btn-light btn-lg w-100 fw-bold shadow-sm border text-muted"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset Form</button>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" name="register_boost" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border:none;">
                                    <i class="bi bi-plus-lg me-2"></i>Register Request
                                </button>
                            </div>
                        </div>
                    </form>
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