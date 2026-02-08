<?php
require_once 'auth.php';
require_once "../config/db.php";

// Handle AJAX Fetch Count
if(isset($_POST['action']) && $_POST['action'] == 'fetch_count'){
    header('Content-Type: application/json');
    $url = $_POST['link'] ?? '';
    $count = 0;
    
    if($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($ch);
        curl_close($ch);

        if($html){
            // Try to find follower count in meta description
            if(preg_match('/<meta\s+name="description"\s+content="([^"]+)"/i', $html, $matches)){
                $desc = $matches[1];
                if(preg_match('/([0-9,.]+[KkMm]?)\s+followers/i', $desc, $m)){
                    $raw = strtoupper(str_replace(',', '', $m[1]));
                    if(strpos($raw, 'M') !== false) $count = floatval($raw) * 1000000;
                    elseif(strpos($raw, 'K') !== false) $count = floatval($raw) * 1000;
                    else $count = intval($raw);
                }
            }
        }
    }
    echo json_encode(['count' => $count]);
    exit;
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

// Handle Favorites Logic
if(isset($_POST['add_favorite'])){
    $fav_name = trim($_POST['fav_name']);
    $fav_link = trim($_POST['fav_link']);
    $fav_cat = trim($_POST['fav_category']);
    if(empty($fav_cat)) $fav_cat = 'General';
    if(!empty($fav_name) && !empty($fav_link)){
        $stmt = $conn->prepare("INSERT INTO favorites (username, name, link, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $_SESSION['admin'], $fav_name, $fav_link, $fav_cat);
        $stmt->execute();
        $stmt->close();
        $message = "Link saved to favorites!";
        $toast_class = "bg-success";
    }
}

if(isset($_POST['delete_favorite'])){
    $fav_id = (int)$_POST['fav_id'];
    $stmt = $conn->prepare("DELETE FROM favorites WHERE id=? AND username=?");
    $stmt->bind_param("is", $fav_id, $_SESSION['admin']);
    $stmt->execute();
    $stmt->close();
    $message = "Favorite deleted.";
    $toast_class = "bg-warning";
}

// Fetch Favorites
$favs = [];
$categories = [];
$stmt = $conn->prepare("SELECT * FROM favorites WHERE username=? ORDER BY category ASC, created_at DESC");
$stmt->bind_param("s", $_SESSION['admin']);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $cat = $row['category'] ?: 'General';
    $favs[$cat][] = $row;
    $categories[$cat] = true;
}
$stmt->close();

if(isset($_POST['add_follower_fast'])){
    $name = trim($_POST['name']);
    $link = trim($_POST['link']);
    $amount = $_POST['amount'];
    $quality = $_POST['quality'] ?? 'standard';
    $start_count = $_POST['start_count'] ?? 0;
    $speed = $_POST['speed'] ?? 'normal';
    $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : NULL;
    $status = $scheduled_at ? 'scheduled' : 'active';
    
    if(!empty($name) && !empty($link) && $amount > 0){
        // Insert into pages table with type 'follower' and status 'active'
        $stmt = $conn->prepare("INSERT INTO pages (name, fb_link, target_clicks, type, status, is_fast, start_count, speed, scheduled_at) VALUES (?, ?, ?, 'follower', ?, 1, ?, ?, ?)");
        $stmt->bind_param("sssssss", $name, $link, $amount, $status, $start_count, $speed, $scheduled_at);
        
        if($stmt->execute()){
            $message = $scheduled_at ? "Success! Request scheduled for " . date('M d, H:i', strtotime($scheduled_at)) : "Success! Added $amount followers to queue.";
            $toast_class = "bg-success";
            logAction($conn, $_SESSION['admin'], 'Fast Add Follower', "Added $amount $quality followers for $name (Speed: $speed)");
        } else {
            $message = "Error: " . $conn->error;
            $toast_class = "bg-danger";
        }
        $stmt->close();
    } else {
        $message = "Please fill all fields correctly.";
        $toast_class = "bg-warning";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fast Add Follower - FB Money System</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Fast Add Follower</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #2af598 0%, #009efd 100%);">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-rocket-takeoff-fill me-2"></i>Add Followers Immediately</h5>
                </div>
                <div class="card-body p-5">
                    <form method="POST">
                        <div class="mb-4"><label class="form-label fw-bold">Page Name</label><input type="text" name="name" class="form-control form-control-lg" placeholder="e.g. My Page" required></div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Facebook Link</label>
                            <div class="input-group">
                                <input type="url" name="link" id="fb_link" class="form-control form-control-lg" placeholder="https://facebook.com/..." required>
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#favoritesModal" title="Favorites"><i class="bi bi-star-fill"></i></button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Current Followers (Start Count)</label>
                            <div class="input-group mb-2">
                                <select class="form-select form-select-lg" id="start_count_select" onchange="updateStartCountInput(this)">
                                    <option value="0">0 Followers</option>
                                    <option value="100">100 Followers</option>
                                    <option value="1000">1,000 Followers</option>
                                    <option value="10000">10,000 Followers</option>
                                    <option value="100000">100K Followers</option>
                                    <option value="1000000">1M Followers</option>
                                    <option value="custom">Custom / Fetch</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="fetchCountBtn" title="Fetch from Facebook"><i class="bi bi-cloud-download-fill me-1"></i> Fetch</button>
                            </div>
                            <div id="custom_start_count_wrapper" style="max-height: 0; opacity: 0; overflow: hidden; transition: all 0.4s ease-in-out;">
                                <input type="number" name="start_count" id="start_count" class="form-control form-control-lg" placeholder="Enter current count" value="0">
                            </div>
                            <div class="form-text text-muted">Select start count or fetch from link.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Amount to Add</label>
                            <select name="amount" id="amount" class="form-select form-select-lg" required>
                                <option value="100">100 Followers</option>
                                <option value="200">200 Followers</option>
                                <option value="300">300 Followers</option>
                                <option value="10000000">10M Followers</option>
                                <option value="20000000">20M Followers</option>
                                <option value="50000000">50M Followers</option>
                                <option value="5000000000">5000M Followers</option>
                            </select>
                            <div class="form-text text-muted">Select the number of followers you want to add.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Quality</label>
                            <select name="quality" id="quality" class="form-select form-select-lg">
                                <option value="standard" data-rate="0.01">Standard Quality ($0.01/follower)</option>
                                <option value="high" data-rate="0.03">High Quality ($0.03/follower)</option>
                                <option value="premium" data-rate="0.05">Premium Quality ($0.05/follower)</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Delivery Speed</label>
                            <select name="speed" class="form-select form-select-lg">
                                <option value="normal">Normal (Recommended)</option>
                                <option value="fast">Fast Delivery</option>
                                <option value="instant">Instant / Turbo</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Schedule for Later (Optional)</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control form-control-lg">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Total Price (Estimated)</label>
                            <div class="input-group"><span class="input-group-text">$</span><input type="text" id="total_price" class="form-control form-control-lg bg-white" value="0.00" readonly></div>
                            <div class="form-text text-muted">Calculated at $0.01 per follower.</div>
                        </div>
                        <button type="submit" name="add_follower_fast" class="btn btn-success btn-lg w-100 fw-bold shadow-sm"><i class="bi bi-plus-lg me-2"></i>Add Followers Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Favorites Modal -->
<div class="modal fade" id="favoritesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-star-fill me-2"></i>Manage Favorites</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" class="mb-4">
                    <label class="form-label fw-bold small text-muted">Save Current Link</label>
                    <div class="input-group mb-2">
                        <input type="text" name="fav_name" class="form-control" placeholder="Name (e.g. My Page)" required>
                        <input type="text" name="fav_category" list="cat_list" class="form-control" placeholder="Category (e.g. Gaming)">
                        <datalist id="cat_list">
                            <?php foreach(array_keys($categories) as $c): ?><option value="<?php echo htmlspecialchars($c); ?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="input-group">
                        <input type="text" name="fav_link" id="modal_fav_link" class="form-control" placeholder="Link" required>
                        <button type="submit" name="add_favorite" class="btn btn-success"><i class="bi bi-plus-lg"></i></button>
                    </div>
                </form>
                <div class="list-group list-group-flush">
                    <?php if(empty($favs)): ?>
                        <div class="text-center text-muted small py-3">No favorites saved yet.</div>
                    <?php else: ?>
                        <?php foreach($favs as $cat => $items): ?>
                            <div class="list-group-item bg-light fw-bold small text-uppercase text-muted py-1"><?php echo htmlspecialchars($cat); ?></div>
                            <?php foreach($items as $f): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 ps-3 border-0">
                                <a href="#" class="text-decoration-none text-dark flex-grow-1" onclick="selectFavorite('<?php echo htmlspecialchars($f['link']); ?>')">
                                    <div class="fw-bold"><?php echo htmlspecialchars($f['name']); ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($f['link']); ?></div>
                                </a>
                                <form method="POST" class="ms-2" onsubmit="return confirm('Delete this favorite?');">
                                    <input type="hidden" name="fav_id" value="<?php echo $f['id']; ?>">
                                    <button type="submit" name="delete_favorite" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3"><div id="liveToast" class="toast align-items-center text-white <?php echo $toast_class; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body"><?php echo $message; ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); document.getElementById('wrapper').classList.toggle('toggled'); });
document.getElementById('page-content-wrapper').addEventListener('click', function(e) { if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) { document.getElementById('wrapper').classList.remove('toggled'); } });
document.getElementById('sidebarClose').addEventListener('click', function(e) { e.preventDefault(); document.getElementById('wrapper').classList.remove('toggled'); });
const toggle = document.getElementById('darkModeToggle'); const body = document.body; if(localStorage.getItem('darkMode') === 'enabled'){ body.classList.add('dark-mode'); toggle.textContent = '☀️'; } toggle.addEventListener('click', () => { body.classList.toggle('dark-mode'); localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : 'disabled'); toggle.textContent = body.classList.contains('dark-mode') ? '☀️' : '🌙'; });
<?php if($message): ?>const toastEl = document.getElementById('liveToast'); const toast = new bootstrap.Toast(toastEl); toast.show();<?php endif; ?>

const priceInput = document.getElementById('total_price');
const qualityInput = document.getElementById('quality');

function calculatePrice() {
    let val = parseInt(amountInput.value);
    if(isNaN(val) || val < 0) val = 0;
    let rate = parseFloat(qualityInput.options[qualityInput.selectedIndex].getAttribute('data-rate'));
    let total = val * rate;
    priceInput.value = total.toFixed(2);
}

if(amountInput && priceInput){
    amountInput.addEventListener('change', calculatePrice);
    if(qualityInput) qualityInput.addEventListener('change', calculatePrice);
    // Calculate initial price
    calculatePrice();

    // Fetch Count Logic
    const fetchBtn = document.getElementById('fetchCountBtn');
    if(fetchBtn){
        fetchBtn.addEventListener('click', function() {
            const linkInput = document.querySelector('input[name="link"]');
            const link = linkInput ? linkInput.value : '';
            const btn = this;
            const input = document.getElementById('start_count');
            
            // Switch to custom mode
            const select = document.getElementById('start_count_select');
            if(select) {
                select.value = 'custom';
                if(typeof updateStartCountInput === 'function') updateStartCountInput(select);
            }
            
            if(!link) { alert('Please enter a Facebook Link first.'); return; }
            
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'fetch_count');
            formData.append('link', link);
            
            fetch('add_follower_fast.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => { if(data.count > 0) { input.value = data.count; } else { alert('Could not fetch count automatically. Please enter manually.'); } })
            .catch(err => { console.error(err); alert('Error fetching data.'); })
            .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
        });
    }

    // Favorites Logic
    window.selectFavorite = function(link) {
        document.getElementById('fb_link').value = link;
        const modal = bootstrap.Modal.getInstance(document.getElementById('favoritesModal'));
        modal.hide();
    };
    document.getElementById('favoritesModal').addEventListener('show.bs.modal', function () {
        document.getElementById('modal_fav_link').value = document.getElementById('fb_link').value;
    });

    // Start Count Dropdown Logic
    window.updateStartCountInput = function(select) {
        const input = document.getElementById('start_count');
        const wrapper = document.getElementById('custom_start_count_wrapper');
        if(select.value === 'custom') {
            wrapper.style.maxHeight = '100px';
            wrapper.style.opacity = '1';
            input.focus();
        } else {
            wrapper.style.maxHeight = '0';
            wrapper.style.opacity = '0';
            input.value = select.value;
        }
    };
}
</script>
</body>
</html>