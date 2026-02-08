<?php
require_once "../config/db.php";
require_once "logger.php";
session_start();

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        if (isset($admin['is_banned']) && $admin['is_banned'] == 1) {
            $reason = !empty($admin['ban_reason']) ? " Reason: " . htmlspecialchars($admin['ban_reason']) : "";
            $error = "Your account has been banned." . $reason;
        } else {
        $_SESSION['admin'] = $admin['username'];

        // Update Last Login
        $update_login = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE username = ?");
        $update_login->bind_param("s", $username);
        $update_login->execute();
        $update_login->close();

        // Log action
        logAction($conn, $admin['username'], 'Login', 'User logged in successfully');

        if (isset($_POST['remember'])) {
            $token = bin2hex(random_bytes(32));
            $hashed_token = hash('sha256', $token);

            $update_stmt = $conn->prepare("UPDATE admins SET remember_token = ? WHERE username = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("ss", $hashed_token, $username);
                $update_stmt->execute();
                $update_stmt->close();
            }

            // Set cookie: username:token
            $cookie_value = $username . ':' . $token;
            setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/admin", null, false, true); // 30 days, httpOnly
        }
        header("Location: dashboard.php");
        exit();
        }
    } else {
        $error = "Invalid login credentials!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - FB Money</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .form-control {
            border-radius: 2rem;
            padding: 0.75rem 1rem;
        }
        .btn-primary {
            border-radius: 2rem;
            padding: 0.75rem 1rem;
            font-weight: bold;
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover { background-color: #2e59d9; border-color: #2653d4; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8">
            <div class="card my-5">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h1 class="h4 text-gray-900 mb-4 fw-bold">Welcome Back!</h1>
                    </div>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="username" placeholder="Enter Username..." required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <hr>
                    <div class="text-center">
                        <a class="small text-decoration-none" href="forgot_password.php">Forgot Password?</a>
                    </div>
                    <div class="text-center">
                        <a class="small text-decoration-none" href="signup.php">Create an Account!</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
