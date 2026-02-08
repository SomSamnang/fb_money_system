<?php
require_once "config/db.php";
session_start();

// Check Maintenance Mode
$settings_res = $conn->query("SELECT * FROM settings WHERE setting_key IN ('maintenance_mode', 'maintenance_message', 'maintenance_end_time')");
$settings = [];
while($row = $settings_res->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; }
$m_mode = $settings['maintenance_mode'] ?? 'off';
$m_msg = $settings['maintenance_message'] ?? 'We are currently performing scheduled maintenance.<br>We will be back shortly.';
$m_end = $settings['maintenance_end_time'] ?? '';

if ($m_mode === 'on' && !isset($_SESSION['admin'])) {
    $countdown_script = "";
    if ($m_end && strtotime($m_end) > time()) {
        $countdown_script = "<h3 id='countdown' class='text-danger fw-bold mb-4'></h3><script>
        const countDownDate = new Date('$m_end').getTime();
        const x = setInterval(function() {
            const now = new Date().getTime();
            const distance = countDownDate - now;
            if (distance < 0) {
                clearInterval(x);
                document.getElementById('countdown').innerHTML = 'Maintenance ending soon...';
            } else {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                document.getElementById('countdown').innerHTML = days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's ';
            }
        }, 1000);
        </script>";
    }
    die("<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Maintenance</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='d-flex justify-content-center align-items-center vh-100 bg-light'><div class='text-center'><h1 class='display-1 fw-bold text-primary'>🛠️</h1><h2 class='mb-3'>Under Maintenance</h2><p class='lead text-muted mb-4'>$m_msg</p>$countdown_script<a href='admin/login.php' class='btn btn-outline-primary'>Admin Login</a></div></body></html>");
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - FB Money</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">User Login</h3>
                    <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
                        <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <div class="text-center mt-3"><a href="register_user.php">Create an account</a></div>
                    <div class="text-center mt-2"><a href="index.php">Back to Home</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>