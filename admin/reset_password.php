<?php
require_once "../config/db.php";
$message = "";
$msg_type = "";
$valid_token = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $now = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("SELECT id FROM admins WHERE reset_token=? AND reset_expires > ?");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $valid_token = true;
    } else {
        $message = "Invalid or expired token.";
        $msg_type = "alert-danger";
    }
    $stmt->close();
} else {
    $message = "No token provided.";
    $msg_type = "alert-danger";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $token = $_POST['token']; // Hidden input

    if ($password === $confirm) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $stmt = $conn->prepare("UPDATE admins SET password=?, reset_token=NULL, reset_expires=NULL WHERE reset_token=?");
        $stmt->bind_param("ss", $hashed, $token);
        
        if ($stmt->execute()) {
            $message = "Password updated successfully! <a href='login.php'>Login now</a>";
            $msg_type = "alert-success";
            $valid_token = false; // Hide form
        } else {
            $message = "Error updating password.";
            $msg_type = "alert-danger";
        }
        $stmt->close();
    } else {
        $message = "Passwords do not match.";
        $msg_type = "alert-danger";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - FB Money</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(180deg, #4e73df 10%, #224abe 100%); height: 100vh; display: flex; align-items: center; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .form-control { border-radius: 2rem; padding: 0.75rem 1rem; }
        .btn-primary { border-radius: 2rem; padding: 0.75rem 1rem; font-weight: bold; background-color: #4e73df; border-color: #4e73df; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8">
            <div class="card my-5">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h1 class="h4 text-gray-900 mb-2 fw-bold">Set New Password</h1>
                    </div>
                    <?php if($message): ?>
                        <div class="alert <?php echo $msg_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if($valid_token): ?>
                    <form method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                        <div class="mb-3"><input type="password" class="form-control" name="password" placeholder="New Password" required></div>
                        <div class="mb-3"><input type="password" class="form-control" name="confirm_password" placeholder="Confirm New Password" required></div>
                        <button type="submit" class="btn btn-primary w-100">Change Password</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>