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

// Handle Update User (Points & Password)
if (isset($_POST['update_user'])) {
    $id = (int)$_POST['user_id'];
    $points = (int)$_POST['points'];
    $password = $_POST['password'];

    $sql = "UPDATE users SET points=?";
    $params = [$points];
    $types = "i";

    if (!empty($password)) {
        $sql .= ", password=?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
        $types .= "s";
    }

    $sql .= " WHERE id=?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $message = "User updated successfully!";
        $toast_class = "bg-success";
        logAction($conn, $_SESSION['admin'], 'Update Public User', "Updated user ID: $id");
    } else {
        $message = "Error updating user.";
        $toast_class = "bg-danger";
    }
}

// Handle Delete User
if (isset($_POST['delete_user'])) {
    $id = (int)$_POST['user_id'];
    $conn->query("DELETE FROM users WHERE id=$id");
    $message = "User deleted successfully.";
    $toast_class = "bg-warning";
    logAction($conn, $_SESSION['admin'], 'Delete Public User', "Deleted user ID: $id");
}

// Fetch Users
$search = $_GET['search'] ?? '';
if ($search) {
    $term = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC");
    $stmt->bind_param("ss", $term, $term);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT * FROM users ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Public Users - FB Money</title>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Public Members</span>
            </div>
        </nav>
        <div class="container-fluid px-4">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Registered Users</h5>
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    </form>
                </div>
                <table class="table table-hover">
                    <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Points</th><th>Joined</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span class="badge bg-warning text-dark"><?php echo $u['points']; ?></span></td>
                            <td><?php echo date("M d, Y", strtotime($u['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>', <?php echo $u['points']; ?>)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit User: <span id="editUsername"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST">
          <div class="modal-body">
              <input type="hidden" name="user_id" id="editUserId">
              <div class="mb-3"><label>Points</label><input type="number" name="points" id="editPoints" class="form-control" required></div>
              <div class="mb-3"><label>New Password (leave blank to keep current)</label><input type="password" name="password" class="form-control" placeholder="Enter new password"></div>
          </div>
          <div class="modal-footer"><button type="submit" name="update_user" class="btn btn-primary">Save Changes</button></div>
      </form>
    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="liveToast" class="toast align-items-center text-white <?php echo $toast_class; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body"><?php echo $message; ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); document.getElementById('wrapper').classList.toggle('toggled'); });
function editUser(id, username, points) { document.getElementById('editUserId').value = id; document.getElementById('editUsername').textContent = username; document.getElementById('editPoints').value = points; new bootstrap.Modal(document.getElementById('editModal')).show(); }
<?php if($message): ?>const toastEl = document.getElementById('liveToast'); const toast = new bootstrap.Toast(toastEl); toast.show();<?php endif; ?>
</script>
</body>
</html>