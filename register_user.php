<?php
require_once "config/db.php";
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
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
    $referral_input = isset($_POST['referral_code']) ? trim($_POST['referral_code']) : '';

    if (!isset($_POST['terms'])) {
        $error = "You must agree to the Terms and Privacy Policy!";
    } elseif (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Username or Email already exists!";
        } else {
            // Generate unique referral code for new user
            $new_referral_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            // Check if referred by someone
            $referred_by_id = null;
            if (!empty($referral_input)) {
                $ref_stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
                $ref_stmt->bind_param("s", $referral_input);
                $ref_stmt->execute();
                $ref_res = $ref_stmt->get_result();
                if ($ref_row = $ref_res->fetch_assoc()) {
                    $referred_by_id = $ref_row['id'];
                }
                $ref_stmt->close();
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, referral_code, referred_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed, $new_referral_code, $referred_by_id);
            if ($stmt->execute()) {
                // Award points to referrer (e.g., 10 points)
                if ($referred_by_id) {
                    $conn->query("UPDATE users SET points = points + 10 WHERE id = $referred_by_id");
                }

                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $error = "Registration failed! " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - FB Money</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Create Account</h3>
                    <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
                        <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                        <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                        <div class="mb-3"><input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required></div>
                        <div class="mb-3"><input type="text" name="referral_code" class="form-control" placeholder="Referral Code (Optional)" value="<?php echo isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : ''; ?>"></div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label small" for="terms">I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms</a> & <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a></label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                    </form>
                    <div class="text-center mt-3"><a href="login_user.php">Already have an account? Login</a></div>
                    <div class="text-center mt-2"><a href="index.php">Back to Home</a></div>
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
</body>
</html>