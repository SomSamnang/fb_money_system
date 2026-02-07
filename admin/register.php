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

// Restrict access to Admins only
if (($u_row['role'] ?? 'editor') !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$profile_pic = !empty($u_row['profile_pic']) ? "uploads/" . $u_row['profile_pic'] : "https://via.placeholder.com/150";
$last_login = !empty($u_row['last_login']) ? date("M d, Y h:i A", strtotime($u_row['last_login'])) : "First Login";
$bio = $u_row['bio'] ?? '';
$user_role = $u_row['role'] ?? 'editor';

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $role     = $_POST['role'] ?? 'editor';
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $message = "All fields are required!";
        $msg_type = "alert-danger";
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match!";
        $msg_type = "alert-danger";
    } else {

        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username=? OR email=?");
        if ($stmt) {
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username or Email already exists!";
            $msg_type = "alert-danger";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssss", $username, $email, $hashedPassword, $role);

            if ($insert->execute()) {
                $message = "User created successfully!";
                $msg_type = "alert-success";
                logAction($conn, $_SESSION['admin'], 'Add User', "Created new user: $username");
            } else {
                $message = "Something went wrong!";
                $msg_type = "alert-danger";
            }

            $insert->close();
        }

        $stmt->close();
        } else {
            $message = "Database Error: " . $conn->error;
            $msg_type = "alert-danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add User - FB Money System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .navbar { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); background: #fff !important; position: sticky; top: 0; z-index: 100; }
        /* Dark Mode Styles */
        body.dark-mode { background: #121212; color: #e0e0e0; }
        body.dark-mode .card { background: #1e1e1e; color: #e0e0e0; }
        body.dark-mode .navbar { background: #1e1e1e !important; color: #e0e0e0; }
        body.dark-mode .form-control { background: #2d2d2d; border-color: #444; color: #e0e0e0; }
        
        .register-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08); overflow: hidden; }
        .register-header { background: linear-gradient(45deg, #4e73df, #224abe); color: white; padding: 2rem 1.5rem; text-align: center; }
        .form-control:focus, .form-select:focus { border-color: #4e73df; box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25); }
        .input-group-text { background-color: #fff; border-right: none; }
        .form-control, .form-select { border-left: none; }
        .input-group:focus-within .input-group-text { border-color: #4e73df; color: #4e73df; }
        .input-group:focus-within .form-control, .input-group:focus-within .form-select { border-color: #4e73df; }
        body.dark-mode .register-card { background: #1e1e1e; }
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
                <span class="navbar-text ms-auto fw-bold text-primary">Add User</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card register-card mb-5">
                        <div class="register-header">
                            <h3 class="mb-1 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Create New User</h3>
                            <p class="mb-0 opacity-75">Add a new administrator or editor to the system.</p>
                        </div>
                        <div class="card-body p-4 p-md-5">
                            <?php if($message): ?>
                                <div class="alert <?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Account Details</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="bi bi-person text-muted"></i></span>
                                        <input type="text" class="form-control" name="username" id="regUsername" placeholder="Username" required>
                                    </div>
                                    <div class="input-group has-validation mb-3">
                                        <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                                        <input type="email" class="form-control" name="email" id="regEmail" placeholder="Email Address" required onblur="checkEmail(this)">
                                        <button type="button" class="btn btn-outline-secondary" onclick="generateEmail('regUsername', 'regEmail')" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate Email"><i class="bi bi-magic"></i></button>
                                        <div class="invalid-feedback">Email is already taken!</div>
                                        <div class="valid-feedback">Email is available!</div>
                                    </div>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="bi bi-shield-lock text-muted"></i></span>
                                        <select class="form-select" name="role">
                                            <option value="editor">Editor</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Security</label>
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-key text-muted"></i></span>
                                                <input type="password" class="form-control" name="password" id="regPassword" placeholder="Password" required>
                                                <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('regPassword', 'regConfirmPassword')" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate Password"><i class="bi bi-magic"></i></button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="copyPassword('regPassword', this)" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy to Clipboard"><i class="bi bi-clipboard"></i></button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('regPassword', this)" data-bs-toggle="tooltip" data-bs-placement="top" title="Show/Hide Password"><i class="bi bi-eye"></i></button>
                                            </div>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar" role="progressbar" id="passwordStrengthBar" style="width: 0%;"></div>
                                            </div>
                                            <small class="text-muted" id="passwordStrengthText"></small>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-check2-circle text-muted"></i></span>
                                                <input type="password" class="form-control" name="confirm_password" id="regConfirmPassword" placeholder="Confirm" required>
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('regConfirmPassword', this)" data-bs-toggle="tooltip" data-bs-placement="top" title="Show/Hide Password"><i class="bi bi-eye"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                        <i class="bi bi-person-plus me-2"></i>Create Account
                                    </button>
                                    <a href="users.php" class="btn btn-light text-muted">Cancel</a>
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
                </div>
            </div>
        </footer>
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
const toggle = document.getElementById('darkModeToggle');
const body = document.body;
if(localStorage.getItem('darkMode') === 'enabled'){ body.classList.add('dark-mode'); toggle.textContent = '☀️'; }
toggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
    toggle.textContent = body.classList.contains('dark-mode') ? '☀️' : '🌙';
});

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
    f1.dispatchEvent(new Event('input')); // Update strength meter
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

document.getElementById('regPassword').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    let strength = 0;
    
    if (password.length > 0) strength += 10;
    if (password.length >= 6) strength += 20;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 20;
    if (password.match(/\d/)) strength += 20;
    if (password.match(/[^a-zA-Z\d]/)) strength += 30;

    if (password.length < 6 && password.length > 0) strength = Math.min(strength, 30);

    strengthBar.style.width = strength + '%';
    
    if (strength < 40) { strengthBar.className = 'progress-bar bg-danger'; strengthText.textContent = 'Weak'; strengthText.className = 'text-danger small'; }
    else if (strength < 80) { strengthBar.className = 'progress-bar bg-warning'; strengthText.textContent = 'Medium'; strengthText.className = 'text-warning small'; }
    else { strengthBar.className = 'progress-bar bg-success'; strengthText.textContent = 'Strong'; strengthText.className = 'text-success small'; }
});
</script>
</body>
</html>
