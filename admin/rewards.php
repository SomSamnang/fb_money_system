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

// Add Reward
if (isset($_POST['add_reward'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $cost = (int)$_POST['points_cost'];
    $stock = (int)$_POST['stock'];
    $image = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $new_name = "reward_" . time() . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $new_name);
            $image = $new_name;
        }
    }

    $stmt = $conn->prepare("INSERT INTO rewards (name, description, points_cost, stock, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiis", $name, $desc, $cost, $stock, $image);
    if ($stmt->execute()) {
        $message = "Reward added successfully!";
        $toast_class = "bg-success";
        logAction($conn, $_SESSION['admin'], 'Add Reward', "Added reward: $name");
    } else {
        $message = "Error adding reward.";
        $toast_class = "bg-danger";
    }
}

// Delete Reward
if (isset($_POST['delete_reward'])) {
    $id = (int)$_POST['id'];
    $conn->query("DELETE FROM rewards WHERE id=$id");
    $message = "Reward deleted.";
    $toast_class = "bg-warning";
}

$rewards = $conn->query("SELECT * FROM rewards ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Rewards - FB Money</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Manage Rewards</span>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">🎁 Manage Rewards</h2>
                    <p class="text-muted mb-0">Create and manage redeemable items for users.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
                        <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border:none;">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Reward</h5>
                        </div>
                        <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3"><label class="form-label fw-bold small text-muted">Name</label><input type="text" name="name" class="form-control" placeholder="e.g. $5 Gift Card" required></div>
                            <div class="mb-3"><label class="form-label fw-bold small text-muted">Description</label><textarea name="description" class="form-control" rows="2" placeholder="Short description..."></textarea></div>
                            <div class="mb-3"><label class="form-label fw-bold small text-muted">Points Cost</label><div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-coin"></i></span><input type="number" name="points_cost" class="form-control" placeholder="100" required></div></div>
                            <div class="mb-3"><label class="form-label fw-bold small text-muted">Stock (-1 for infinite)</label><div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-box-seam"></i></span><input type="number" name="stock" class="form-control" value="-1"></div></div>
                            <div class="mb-3"><label class="form-label fw-bold small text-muted">Image</label><input type="file" name="image" class="form-control"></div>
                            <button type="submit" name="add_reward" class="btn btn-success w-100 fw-bold shadow-sm" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border:none;"><i class="bi bi-plus-lg me-1"></i> Add Reward</button>
                        </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
                        <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border:none;">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Existing Rewards</h5>
                        </div>
                        <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th class="ps-4 py-3">Image</th><th>Name</th><th>Cost</th><th>Stock</th><th class="text-end pe-4">Action</th></tr></thead>
                            <tbody>
                                <?php while($r = $rewards->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php if($r['image']): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($r['image']); ?>" width="50" height="50" class="rounded shadow-sm" style="object-fit:cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted small" style="width:50px; height:50px;">No Img</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td><span class="badge bg-warning text-dark"><i class="bi bi-coin me-1"></i><?php echo number_format($r['points_cost']); ?></span></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo $r['stock'] == -1 ? '∞ Infinite' : $r['stock']; ?></span></td>
                                    <td class="text-end pe-4">
                                        <form method="POST" onsubmit="return confirm('Delete this reward?');">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <button type="submit" name="delete_reward" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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
<?php if($message): ?>const toastEl = document.getElementById('liveToast'); const toast = new bootstrap.Toast(toastEl); toast.show();<?php endif; ?>
</script>
</body>
</html>