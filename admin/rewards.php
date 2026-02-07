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
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-4">
                        <h5 class="mb-3">Add New Reward</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                            <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                            <div class="mb-3"><label>Points Cost</label><input type="number" name="points_cost" class="form-control" required></div>
                            <div class="mb-3"><label>Stock (-1 for infinite)</label><input type="number" name="stock" class="form-control" value="-1"></div>
                            <div class="mb-3"><label>Image</label><input type="file" name="image" class="form-control"></div>
                            <button type="submit" name="add_reward" class="btn btn-primary w-100">Add Reward</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-4">
                        <h5 class="mb-3">Existing Rewards</h5>
                        <table class="table table-hover">
                            <thead><tr><th>Image</th><th>Name</th><th>Cost</th><th>Stock</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php while($r = $rewards->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if($r['image']): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($r['image']); ?>" width="50" height="50" style="object-fit:cover; border-radius:5px;">
                                        <?php else: ?>
                                            <span class="text-muted">No Img</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td><?php echo number_format($r['points_cost']); ?></td>
                                    <td><?php echo $r['stock'] == -1 ? '∞' : $r['stock']; ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this reward?');">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <button type="submit" name="delete_reward" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
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
<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="liveToast" class="toast align-items-center text-white <?php echo $toast_class; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body"><?php echo $message; ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); document.getElementById('wrapper').classList.toggle('toggled'); });
<?php if($message): ?>const toastEl = document.getElementById('liveToast'); const toast = new bootstrap.Toast(toastEl); toast.show();<?php endif; ?>
</script>
</body>
</html>