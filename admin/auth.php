<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/logger.php";

// If user is not logged in via session, check for remember me cookie
if (!isset($_SESSION['admin']) && isset($_COOKIE['remember_me'])) {
    list($selector, $token) = explode(':', $_COOKIE['remember_me'], 2);

    if (ctype_alnum($selector) && ctype_alnum($token)) {
        // Look up selector in DB (selector is username)
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND remember_token IS NOT NULL");
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin) {
            // Hash the token from the cookie and compare with the one in the DB
            $hashed_token_from_cookie = hash('sha256', $token);
            if (hash_equals($admin['remember_token'], $hashed_token_from_cookie)) {
                // Token is valid, log the user in
                $_SESSION['admin'] = $admin['username'];

                // Update Last Login
                $update_login = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE username = ?");
                $update_login->bind_param("s", $admin['username']);
                $update_login->execute();
                $update_login->close();

                // Log action
                logAction($conn, $admin['username'], 'Login (Cookie)', 'Auto-login via remember me');

                // --- Security: Regenerate token ---
                $new_token = bin2hex(random_bytes(32));
                $new_hashed_token = hash('sha256', $new_token);
                $cookie_value = $selector . ':' . $new_token;

                // Update DB
                $update_stmt = $conn->prepare("UPDATE admins SET remember_token = ? WHERE username = ?");
                $update_stmt->bind_param("ss", $new_hashed_token, $selector);
                $update_stmt->execute();
                $update_stmt->close();

                // Update cookie
                setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/admin", null, false, true); // 30 days, httpOnly
            }
        }
    }
}

// Final check after attempting cookie login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Update Last Active Timestamp
if (isset($_SESSION['admin'])) {
    $u_act = $conn->prepare("UPDATE admins SET last_active = NOW() WHERE username = ?");
    if ($u_act) {
        $u_act->bind_param("s", $_SESSION['admin']);
        $u_act->execute();
        $u_act->close();
    }
}

// Helper function to format large numbers (e.g. 1.5K, 2M, 5B)
if (!function_exists('formatNumber')) {
    function formatNumber($num) {
        if ($num >= 1000000000) return round($num / 1000000000, 1) . 'B';
        if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
        if ($num >= 1000) return round($num / 1000, 1) . 'K';
        return number_format($num);
    }
}
?>
