<?php
require_once "../config/db.php";
session_start();

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Generate Token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Update DB
        $update = $conn->prepare("UPDATE admins SET reset_token=?, reset_expires=? WHERE id=?");
        $update->bind_param("ssi", $token, $expires, $row['id']);
        $update->execute();
        $update->close();

        // In a real app, send this via mail(). For now, show the link.
        $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        $message = "Reset link generated (Simulated Email): <br><a href='$link'>Click here to reset</a>";
        $msg_type = "alert-success";
    } else {
        $message = "We could not find an account with that email.";
        $msg_type = "alert-danger";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - FB Money</title>
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
                        <h1 class="h4 text-gray-900 mb-2 fw-bold">Forgot Your Password?</h1>
                        <p class="mb-4 text-muted">We get it, stuff happens. Just enter your email below!</p>
                    </div>
                    <?php if($message): ?>
                        <div class="alert <?php echo $msg_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Enter Email Address..." required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                    <hr>
                    <div class="text-center">
                        <a class="small text-decoration-none" href="signup.php">Create an Account!</a>
                    </div>
                    <div class="text-center">
                        <a class="small text-decoration-none" href="login.php">Already have an account? Login!</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>