<?php
require_once "../config/db.php";
require_once "logger.php";
session_start();

if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

// Fetch Settings
$settings_res = $conn->query("SELECT * FROM settings WHERE setting_key IN ('terms_of_service', 'privacy_policy')");
$settings = [];
while($row = $settings_res->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; }

$default_tos = <<<EOT
<p><strong>1. Acceptance</strong><br>By using this system, you agree to these terms.</p>
<p><strong>2. Account</strong><br>You are responsible for your account security.</p>
<p><strong>3. Conduct</strong><br>You agree not to misuse the system.</p>
EOT;

$default_pp = "<p><strong>Privacy Policy</strong><br>Your privacy is important to us.</p>";

$tos_content = $settings['terms_of_service'] ?? $default_tos;
$pp_content = $settings['privacy_policy'] ?? $default_pp;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (!isset($_POST['terms'])) {
        $error = "You must agree to the Terms of Service!";
    } elseif (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // Check if username/email exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username=? OR email=?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username or Email already exists!";
        } else {
            $stmt->close();
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'editor'; // Default role for public registration

            $insert = $conn->prepare("INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)");
            if ($insert) {
                $insert->bind_param("ssss", $username, $email, $hashedPassword, $role);

                if ($insert->execute()) {
                // Auto Login
                $_SESSION['admin'] = $username;
                
                // Update Last Login
                $conn->query("UPDATE admins SET last_login = NOW() WHERE username = '$username'");
                
                logAction($conn, $username, 'Register', "New user registered publicly");

                // Handle Remember Me
                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    $hashed_token = hash('sha256', $token);

                    $update_stmt = $conn->prepare("UPDATE admins SET remember_token = ? WHERE username = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("ss", $hashed_token, $username);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }

                    $cookie_value = $username . ':' . $token;
                    setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/admin", null, false, true);
                }

                header("Location: dashboard.php");
                exit();
                } else {
                    $error = "Something went wrong!";
                }
                $insert->close();
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - FB Money</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(180deg, #4e73df 10%, #224abe 100%); height: 100vh; display: flex; align-items: center; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .form-control { border-radius: 2rem; padding: 0.75rem 1rem; }
        .btn-primary { border-radius: 2rem; padding: 0.75rem 1rem; font-weight: bold; background-color: #4e73df; border-color: #4e73df; }
        .btn-primary:hover { background-color: #2e59d9; border-color: #2653d4; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8">
            <div class="card my-5">
                <div class="card-body p-5">
                    <div class="text-center mb-4"><h1 class="h4 text-gray-900 mb-4 fw-bold">Create an Account!</h1></div>
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <div class="mb-3"><input type="text" class="form-control" name="username" placeholder="Username" required></div>
                        <div class="mb-3"><input type="email" class="form-control" name="email" placeholder="Email Address" required></div>
                        <div class="mb-3 row">
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <input type="password" class="form-control" name="password" id="signupPassword" placeholder="Password" required>
                                <div class="progress mt-1" style="height: 5px;"><div class="progress-bar" role="progressbar" id="passwordStrengthBar" style="width: 0%;"></div></div>
                                <small class="text-muted" id="passwordStrengthText"></small>
                            </div>
                            <div class="col-sm-6"><input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required></div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a></label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register Account</button>
                    </form>
                    <hr>
                    <div class="text-center">
                        <a class="small text-decoration-none" href="login.php">Already have an account? Login!</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Terms of Service</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php echo $tos_content; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Privacy Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php echo $pp_content; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('signupPassword').addEventListener('input', function() {
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