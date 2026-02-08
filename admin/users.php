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

// Handle delete user
if(isset($_POST['delete_user'])){
    $user_id = (int)$_POST['user_id'];
    
    // Check if deleting self
    $stmt = $conn->prepare("SELECT username FROM admins WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $target_user = $res->fetch_assoc();
    $stmt->close();

    if ($target_user && $target_user['username'] === $_SESSION['admin']) {
        $message = "You cannot delete your own account!";
        $toast_class = "bg-warning";
    } else {
        if($user_role === 'admin') {
            $conn->query("DELETE FROM admins WHERE id=$user_id");
            logAction($conn, $_SESSION['admin'], 'Delete User', "Deleted user ID: $user_id");
            $message = "User deleted successfully!";
            $toast_class = "bg-danger";
        } else {
            $message = "Unauthorized action.";
            $toast_class = "bg-danger";
        }
    }
}

// Handle ban user
if(isset($_POST['ban_user'])){
    $user_id = (int)$_POST['user_id'];
    $reason = isset($_POST['ban_reason']) ? trim($_POST['ban_reason']) : '';
    
    // Check if banning self
    if ($user_id == $u_row['id']) { // Using $u_row from sidebar fetch which is current user
        $message = "You cannot ban yourself!";
        $toast_class = "bg-warning";
    } else {
        if($user_role === 'admin') {
            $stmt = $conn->prepare("UPDATE admins SET is_banned=1, ban_reason=? WHERE id=?");
            $stmt->bind_param("si", $reason, $user_id);
            $stmt->execute();
            logAction($conn, $_SESSION['admin'], 'Ban User', "Banned user ID: $user_id. Reason: $reason");
            $message = "User banned successfully!";
            $toast_class = "bg-warning";
        } else {
            $message = "Unauthorized action.";
            $toast_class = "bg-danger";
        }
    }
}

// Handle update user
if(isset($_POST['update_user'])){
    if($user_role === 'admin') {
        $user_id = (int)$_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        
        if(empty($username) || empty($email)){
            $message = "Username and Email are required.";
            $toast_class = "bg-danger";
        } else {
            // Check for duplicates
            $stmt = $conn->prepare("SELECT id FROM admins WHERE (username=? OR email=?) AND id!=?");
            $stmt->bind_param("ssi", $username, $email, $user_id);
            $stmt->execute();
            if($stmt->get_result()->num_rows > 0){
                $message = "Username or Email already exists.";
                $toast_class = "bg-danger";
            } else {
                if ($password) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admins SET username=?, email=?, role=?, password=? WHERE id=?");
                    $stmt->bind_param("ssssi", $username, $email, $role, $hashed, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE admins SET username=?, email=?, role=? WHERE id=?");
                    $stmt->bind_param("sssi", $username, $email, $role, $user_id);
                }

                if($stmt->execute()){
                    $message = "User updated successfully!";
                    $toast_class = "bg-success";
                    logAction($conn, $_SESSION['admin'], 'Update User', "Updated user ID: $user_id" . ($password ? " (Password changed)" : ""));
                } else {
                    $message = "Error updating user.";
                    $toast_class = "bg-danger";
                }
            }
            $stmt->close();
        }
    } else {
        $message = "Unauthorized action.";
        $toast_class = "bg-danger";
    }
}

// Handle send message
if(isset($_POST['send_message'])){
    if($user_role === 'admin') {
        $target_user = $_POST['username'];
        $msg = trim($_POST['message']);
        if(!empty($msg)){
            $stmt = $conn->prepare("INSERT INTO notifications (username, message) VALUES (?, ?)");
            $stmt->bind_param("ss", $target_user, $msg);
            if($stmt->execute()){
                $message = "Notification sent successfully!";
                $toast_class = "bg-success";
                logAction($conn, $_SESSION['admin'], 'Send Notification', "Sent message to $target_user");
            } else {
                $message = "Error sending notification.";
                $toast_class = "bg-danger";
            }
            $stmt->close();
        }
    }
}

// Fetch users list
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

if ($search && $role_filter) {
    $term = "%$search%";
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE (username LIKE ? OR email LIKE ?) AND role = ?");
    $count_stmt->bind_param("sss", $term, $term, $role_filter);
    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_row()[0];
    $count_stmt->close();

    $stmt = $conn->prepare("SELECT id, username, email, role, last_login, is_banned, last_active FROM admins WHERE (username LIKE ? OR email LIKE ?) AND role = ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii", $term, $term, $role_filter, $limit, $offset);

} elseif ($search) {
    $term = "%$search%";
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE username LIKE ? OR email LIKE ?");
    $count_stmt->bind_param("ss", $term, $term);
    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_row()[0];
    $count_stmt->close();

    $stmt = $conn->prepare("SELECT id, username, email, role, last_login, is_banned, last_active FROM admins WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $term, $term, $limit, $offset);

} elseif ($role_filter) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE role = ?");
    $count_stmt->bind_param("s", $role_filter);
    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_row()[0];
    $count_stmt->close();

    $stmt = $conn->prepare("SELECT id, username, email, role, last_login, is_banned, last_active FROM admins WHERE role = ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $role_filter, $limit, $offset);

} else {
    $total_users = $conn->query("SELECT COUNT(*) FROM admins")->fetch_row()[0];
    $stmt = $conn->prepare("SELECT id, username, email, role, last_login, is_banned, last_active FROM admins ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$users_list_res = $stmt->get_result();
$total_pages = ceil($total_users / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users List - FB Money System</title>
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

.user-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08); overflow: hidden; }
.user-header { background: linear-gradient(45deg, #4e73df, #224abe); color: white; padding: 1.5rem; }
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

/* Modal Form Styles */
.input-group-text { background-color: #fff; border-right: none; }
.form-control, .form-select { border-left: none; }
.input-group:focus-within .input-group-text { border-color: #4e73df; color: #4e73df; }
.input-group:focus-within .form-control, .input-group:focus-within .form-select { border-color: #4e73df; box-shadow: none; }
body.dark-mode .input-group-text { background-color: #2d2d2d; border-color: #444; color: #e0e0e0; }

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
                <span class="navbar-text ms-auto fw-bold text-primary">Users List</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="card user-card mb-5">
                <div class="user-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-bold"><i class="bi bi-people-fill me-2"></i>Users List</h4>
                        <p class="mb-0 opacity-75 small">Manage system users, roles, and permissions.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="GET" class="d-flex align-items-center bg-white rounded p-1">
                            <select name="role" class="form-select form-select-sm border-0 shadow-none me-1" style="width: auto; background-color: transparent;" onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <option value="admin" <?php if($role_filter == 'admin') echo 'selected'; ?>>Admin</option>
                                <option value="editor" <?php if($role_filter == 'editor') echo 'selected'; ?>>Editor</option>
                            </select>
                            <div class="vr me-2"></div>
                            <input type="text" name="search" class="form-control border-0 shadow-none form-control-sm" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 150px;">
                            <button type="submit" class="btn btn-primary btn-sm rounded-circle" style="width: 30px; height: 30px; padding: 0;"><i class="bi bi-search"></i></button>
                            <?php if($search || $role_filter): ?><a href="users.php" class="btn btn-light btn-sm ms-1 rounded-circle text-danger d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;"><i class="bi bi-x-lg"></i></a><?php endif; ?>
                        </form>
                        <?php if($user_role === 'admin'): ?>
                        <a href="register.php" class="btn btn-light text-primary fw-bold btn-sm d-flex align-items-center shadow-sm"><i class="bi bi-person-plus-fill me-1"></i> Add User</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="table-responsive p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($users_list_res && $users_list_res->num_rows > 0): ?>
                                <?php while($u = $users_list_res->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 text-primary fw-bold border" style="width:40px; height:40px; font-size: 1.1rem;">
                                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($u['username']); ?></div>
                                                <div class="small text-muted" style="font-size: 0.75rem;">ID: #<?php echo $u['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-secondary"><i class="bi bi-envelope me-2 opacity-50"></i><?php echo htmlspecialchars($u['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if(($u['role'] ?? 'editor') === 'admin'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill"><i class="bi bi-shield-lock-fill me-1"></i> Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill"><i class="bi bi-pencil-fill me-1"></i> Editor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $is_online = false;
                                        if (!empty($u['last_active'])) {
                                            if (time() - strtotime($u['last_active']) <= 300) { // 5 minutes
                                                $is_online = true;
                                            }
                                        }
                                        ?>
                                        <?php if($is_online): ?><span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="bi bi-circle-fill small me-1"></i> Online</span><?php else: ?><span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">Offline</span><?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><i class="bi bi-clock me-1"></i><?php echo $u['last_login'] ? date("M d, Y h:i A", strtotime($u['last_login'])) : 'Never'; ?></td>
                                    <td class="text-end pe-4">
                                        <?php if($u['username'] !== $_SESSION['admin']): ?>
                                        <?php if(($user_role ?? 'editor') === 'admin'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick="openEditUserModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>', '<?php echo htmlspecialchars($u['email']); ?>', '<?php echo $u['role'] ?? 'editor'; ?>')" title="Edit User">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info border-0" onclick="openMessageModal('<?php echo htmlspecialchars($u['username']); ?>')" title="Send Message">
                                            <i class="bi bi-chat-left-text"></i>
                                        </button>
                                        <?php if(empty($u['is_banned'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning border-0" onclick="openBanModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')" title="Ban User">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i> Banned</span>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="openDeleteUserModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')" title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">It's You</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-people fs-1 d-block mb-2"></i>No users found matching your criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="card-footer bg-white border-0 py-3">
                <nav aria-label="Users list pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 text-white" style="background: linear-gradient(45deg, #4e73df, #224abe);">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit User Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <div class="modal-body p-4">
            <input type="hidden" name="user_id" id="editUserId">
            
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Account Info</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" class="form-control" name="username" id="editUserUsername" placeholder="Username" required>
                </div>
            </div>
            <div class="mb-3">
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control" name="email" id="editUserEmail" placeholder="Email Address" required onblur="checkEmail(this, document.getElementById('editUserId').value)">
                    <button type="button" class="btn btn-outline-secondary" onclick="generateEmail('editUserUsername', 'editUserEmail', document.getElementById('editUserId').value)" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate Email"><i class="bi bi-magic"></i></button>
                    <div class="invalid-feedback">Email is already taken!</div>
                    <div class="valid-feedback">Email is available!</div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Permissions</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock text-muted"></i></span>
                    <select class="form-select" name="role" id="editUserRole">
                        <option value="editor">Editor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Security</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key text-muted"></i></span>
                    <input type="password" class="form-control" name="password" id="editUserPassword" placeholder="New Password (leave blank to keep current)">
                    <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('editUserPassword')" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate Password"><i class="bi bi-magic"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="copyPassword('editUserPassword', this)" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy to Clipboard"><i class="bi bi-clipboard"></i></button>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('editUserPassword', this)" data-bs-toggle="tooltip" data-bs-placement="top" title="Show/Hide Password"><i class="bi bi-eye"></i></button>
                </div>
            </div>
          </div>
          <div class="modal-footer border-0 px-4 pb-4">
            <button type="button" class="btn btn-light text-muted" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_user" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Ban User Modal -->
<div class="modal fade" id="banUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 text-white" style="background: linear-gradient(45deg, #e74a3b, #c0392b);">
        <h5 class="modal-title fw-bold"><i class="bi bi-slash-circle me-2"></i>Ban User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <div class="modal-body p-4">
            <input type="hidden" name="user_id" id="banUserId">
            <div class="text-center mb-4">
                <i class="bi bi-exclamation-triangle text-warning display-1"></i>
                <p class="mt-3 fs-5">Are you sure you want to ban <strong id="banUsername" class="text-danger"></strong>?</p>
                <p class="text-muted small">This user will no longer be able to log in to the system.</p>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Reason for Ban</label>
                <textarea class="form-control" name="ban_reason" rows="3" placeholder="Optional reason for banning..."></textarea>
            </div>
          </div>
          <div class="modal-footer border-0 px-4 pb-4">
            <button type="button" class="btn btn-light text-muted" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="ban_user" class="btn btn-danger px-4 shadow-sm"><i class="bi bi-slash-circle me-1"></i> Ban User</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 text-white" style="background: linear-gradient(45deg, #e74a3b, #c0392b);">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <div class="modal-body p-4">
            <input type="hidden" name="user_id" id="deleteUserId">
            <div class="text-center mb-4">
                <p class="fs-5">Are you sure you want to delete <strong id="deleteUsername" class="text-danger"></strong>?</p>
                <p class="text-muted small">This action is irreversible. To confirm, type <strong>DELETE</strong> below.</p>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control text-center" id="deleteConfirmationInput" placeholder="Type DELETE" autocomplete="off">
            </div>
          </div>
          <div class="modal-footer border-0 px-4 pb-4">
            <button type="button" class="btn btn-light text-muted" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_user" id="confirmDeleteBtn" class="btn btn-danger px-4 shadow-sm" disabled><i class="bi bi-trash-fill me-1"></i> Delete User</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Send Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 text-white" style="background: linear-gradient(45deg, #36b9cc, #258391);">
        <h5 class="modal-title fw-bold"><i class="bi bi-chat-left-text-fill me-2"></i>Send Message</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <div class="modal-body p-4">
            <input type="hidden" name="username" id="msgUsername">
            <p>Send a direct notification to <strong id="msgUserDisplay" class="text-info"></strong>.</p>
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Message</label>
                <textarea class="form-control" name="message" rows="4" placeholder="Type your message here..." required></textarea>
            </div>
          </div>
          <div class="modal-footer border-0 px-4 pb-4">
            <button type="button" class="btn btn-light text-muted" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="send_message" class="btn btn-info text-white px-4 shadow-sm"><i class="bi bi-send-fill me-1"></i> Send</button>
          </div>
      </form>
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

// Open Edit User Modal
function openEditUserModal(id, username, email, role) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editUserUsername').value = username;
    document.getElementById('editUserEmail').value = email;
    document.getElementById('editUserRole').value = role;
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

// Open Ban User Modal
function openBanModal(id, username) {
    document.getElementById('banUserId').value = id;
    document.getElementById('banUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('banUserModal')).show();
}

// Open Delete User Modal
function openDeleteUserModal(id, username) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUsername').textContent = username;
    document.getElementById('deleteConfirmationInput').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

// Open Message Modal
function openMessageModal(username) {
    document.getElementById('msgUsername').value = username;
    document.getElementById('msgUserDisplay').textContent = username;
    new bootstrap.Modal(document.getElementById('messageModal')).show();
}

// Enable delete button on correct input
document.getElementById('deleteConfirmationInput').addEventListener('input', function() {
    const btn = document.getElementById('confirmDeleteBtn');
    if (this.value === 'DELETE') {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
});

// Show Toast if message exists
<?php if($message): ?>
const toastEl = document.getElementById('liveToast');
const toast = new bootstrap.Toast(toastEl);
toast.show();
<?php endif; ?>

// Initialize Tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})

function checkEmail(input, excludeId = 0) {
    const email = input.value;
    if (email) {
        const formData = new FormData();
        formData.append('email', email);
        if (excludeId) formData.append('exclude_id', excludeId);

        fetch('check_email.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'taken') {
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
        });
    }
}

function generateEmail(usernameId, emailId, excludeId = 0) {
    var userField = document.getElementById(usernameId);
    var emailField = document.getElementById(emailId);
    var username = userField.value.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
    if(username === '') {
        username = 'user' + Math.floor(Math.random() * 10000);
    }
    emailField.value = username + '@fbmoney.com';
    checkEmail(emailField, excludeId);
}

function generatePassword(field1Id, field2Id = null) {
    const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const f1 = document.getElementById(field1Id);
    f1.value = password;
    f1.type = "text";
    if (field2Id) {
        const f2 = document.getElementById(field2Id);
        f2.value = password;
        f2.type = "text";
    }
}

function copyPassword(elementId, btn) {
    var copyText = document.getElementById(elementId);
    navigator.clipboard.writeText(copyText.value).then(() => {
        let icon = btn.querySelector('i');
        icon.classList.remove('bi-clipboard');
        icon.classList.add('bi-check-lg');
        setTimeout(() => {
            icon.classList.remove('bi-check-lg');
            icon.classList.add('bi-clipboard');
        }, 2000);
    });
}

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
</body>
</html>