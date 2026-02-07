<?php
require_once 'auth.php';
require_once "../config/db.php";

$username = $_SESSION['admin'];
$message = "";
$msg_type = "";

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM admins WHERE username=?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    $user = [];
}
if (!$user) {
    $user = []; // Prevent crash if user not found
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update Email
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email']);
        $bio = trim($_POST['bio']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
             $message = "Invalid email format.";
             $msg_type = "alert-danger";
        } else {
            // Check if email is taken by another user
            $check = $conn->prepare("SELECT id FROM admins WHERE email=? AND username!=?");
            $check->bind_param("ss", $email, $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = "Email already in use by another account.";
                $msg_type = "alert-danger";
            } else {
                $update = $conn->prepare("UPDATE admins SET email=?, bio=? WHERE username=?");
                if ($update) {
                    $update->bind_param("sss", $email, $bio, $username);
                    if ($update->execute()) {
                    $message = "Profile updated successfully!";
                    $msg_type = "alert-success";
                    $user['email'] = $email;
                    $user['bio'] = $bio;
                    logAction($conn, $username, 'Update Profile', "Updated profile info");

                    // Handle Profile Picture Upload
                    if (!empty($_POST['cropped_image'])) {
                        // Handle Base64 Cropped Image
                        $data = $_POST['cropped_image'];
                        // Expect format: data:image/png;base64,......
                        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                            $data = substr($data, strpos($data, ',') + 1);
                            $type = strtolower($type[1]); // jpg, png, gif
                            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                                $type = 'png'; // Default fallback
                            }
                            $data = base64_decode($data);
                            if ($data !== false) {
                                $new_name = uniqid() . "." . $type;
                                $upload_dir = __DIR__ . '/uploads';
                                if (!is_dir($upload_dir)) {
                                    @mkdir($upload_dir, 0777, true);
                                }
                                // Attempt to fix permissions if directory exists
                                if (is_dir($upload_dir)) {
                                    @chmod($upload_dir, 0777);
                                }
                                if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                                    $message = "Upload failed: Permission denied. Please run 'chmod 777 admin/uploads' in terminal.";
                                    $msg_type = "alert-danger";
                                } elseif (file_put_contents($upload_dir . '/' . $new_name, $data)) {
                                    $upd = $conn->prepare("UPDATE admins SET profile_pic=? WHERE username=?");
                                    $upd->bind_param("ss", $new_name, $username);
                                    $upd->execute();
                                    $user['profile_pic'] = $new_name;
                                } else {
                                    $message = "Upload failed: Could not write file to disk.";
                                    $msg_type = "alert-danger";
                                }
                            }
                        }
                    } elseif (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $filename = $_FILES['profile_pic']['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed)) {
                            $new_name = uniqid() . "." . $ext;
                            $upload_dir = __DIR__ . '/uploads';
                            if (!is_dir($upload_dir)) {
                                @mkdir($upload_dir, 0777, true);
                            }
                            // Attempt to fix permissions if directory exists
                            if (is_dir($upload_dir)) {
                                @chmod($upload_dir, 0777);
                            }
                            if (is_dir($upload_dir) && is_writable($upload_dir) && move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . '/' . $new_name)) {
                                // Resize Image to save space (Max width 300px)
                                $target_file = $upload_dir . '/' . $new_name;
                                $info = getimagesize($target_file);
                                if ($info) {
                                    list($w, $h) = $info;
                                    $max_w = 300;
                                    if ($w > $max_w) {
                                        $max_h = ($h / $w) * $max_w;
                                        $src = null;
                                        if($ext == 'jpg' || $ext == 'jpeg') $src = imagecreatefromjpeg($target_file);
                                        if($ext == 'png') $src = imagecreatefrompng($target_file);
                                        if($ext == 'gif') $src = imagecreatefromgif($target_file);
                                        if ($src) {
                                            $dst = imagecreatetruecolor($max_w, $max_h);
                                            if($ext == 'png'){ imagealphablending($dst, false); imagesavealpha($dst, true); }
                                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $max_w, $max_h, $w, $h);
                                            if($ext == 'jpg' || $ext == 'jpeg') imagejpeg($dst, $target_file, 80);
                                            if($ext == 'png') imagepng($dst, $target_file, 8);
                                            if($ext == 'gif') imagegif($dst, $target_file);
                                            imagedestroy($src); imagedestroy($dst);
                                        }
                                    }
                                }
                                $upd = $conn->prepare("UPDATE admins SET profile_pic=? WHERE username=?");
                                $upd->bind_param("ss", $new_name, $username);
                                $upd->execute();
                                $user['profile_pic'] = $new_name;
                            } else {
                                $message .= " (Image upload failed: Permission denied. Check 'uploads' folder)";
                                $msg_type = "alert-warning";
                            }
                        }
                    }
                } else {
                    $message = "Error updating profile.";
                    $msg_type = "alert-danger";
                }
                $update->close();
                } else {
                    $message = "Database error: Could not prepare update. (Missing 'bio' column?)";
                    $msg_type = "alert-danger";
                }
            }
            $check->close();
        }
    }

    // Delete Profile Picture
    if (isset($_POST['delete_profile_pic'])) {
        if (!empty($user['profile_pic'])) {
            $file_path = __DIR__ . '/uploads/' . $user['profile_pic'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $upd = $conn->prepare("UPDATE admins SET profile_pic=NULL WHERE username=?");
            $upd->bind_param("s", $username);
            if ($upd->execute()) {
                $message = "Profile picture removed successfully!";
                $msg_type = "alert-success";
                $user['profile_pic'] = null;
                logAction($conn, $username, 'Delete Profile Pic', "Removed profile picture");
            }
            $upd->close();
        }
    }

    // Update Password
    if (isset($_POST['update_password'])) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (!empty($new_pass)) {
            if ($new_pass === $confirm_pass) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE admins SET password=? WHERE username=?");
                $update->bind_param("ss", $hashed, $username);
                if ($update->execute()) {
                    $message = "Password changed successfully!";
                    $msg_type = "alert-success";
                    logAction($conn, $username, 'Change Password', "User changed their password");
                } else {
                    $message = "Error changing password.";
                    $msg_type = "alert-danger";
                }
                $update->close();
            } else {
                $message = "New passwords do not match.";
                $msg_type = "alert-danger";
            }
        }
    }

    // Delete Account
    if (isset($_POST['delete_account'])) {
        $del = $conn->prepare("DELETE FROM admins WHERE username=?");
        $del->bind_param("s", $username);
        if ($del->execute()) {
            logAction($conn, $username, 'Delete Account', 'User deleted their own account');
            if (isset($_COOKIE['remember_me'])) {
                setcookie('remember_me', '', time() - 3600, "/admin");
            }
            session_destroy();
            header("Location: login.php");
            exit();
        } else {
            $message = "Error deleting account.";
            $msg_type = "alert-danger";
        }
        $del->close();
    }
}

// Define variables for sidebar (AFTER updates so it reflects changes immediately)
$profile_pic = !empty($user['profile_pic']) ? 'uploads/'.$user['profile_pic'] : 'https://via.placeholder.com/150';
$last_login = !empty($user['last_login']) ? date("M d, Y h:i A", strtotime($user['last_login'])) : "First Login";
$bio = $user['bio'] ?? '';
$user_role = $user['role'] ?? 'editor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - FB Money System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
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
.card h5 { font-weight:600; }
.navbar { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); background: #fff !important; position: sticky; top: 0; z-index: 100; }
/* Dark Mode Styles */
body.dark-mode { background: #121212; color: #e0e0e0; }
body.dark-mode .card { background: #1e1e1e; color: #e0e0e0; }
body.dark-mode .navbar { background: #1e1e1e !important; color: #e0e0e0; }
body.dark-mode .form-control { background: #2d2d2d; border-color: #444; color: #e0e0e0; }
#wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
@media (max-width: 768px) {
    #sidebar-wrapper { margin-left: -250px; position: fixed; z-index: 1000; height: 100%; top: 0; left: 0; }
    #wrapper.toggled #sidebar-wrapper { margin-left: 0; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
    #page-content-wrapper { width: 100%; min-width: 100%; }
    /* Overlay Backdrop */
    #wrapper.toggled #page-content-wrapper::before {
        content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;
    }
}
#page-loader {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: #ffffff; z-index: 9999;
    display: flex; justify-content: center; align-items: center;
    transition: opacity 0.5s ease;
}
body.dark-mode #page-loader { background: #121212; }
</style>
</head>
<body>

<div id="page-loader">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom mb-4">
            <div class="container-fluid">
                <button class="btn btn-primary" id="sidebarToggle">☰ Menu</button>
                <span class="navbar-text ms-auto fw-bold text-primary">
                    Admin Profile
                </span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="card p-4">
                        <h4 class="mb-3">👤 Update Profile</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3 text-center">
                                <img src="<?php echo !empty($user['profile_pic']) ? 'uploads/'.$user['profile_pic'] : 'https://via.placeholder.com/150'; ?>" class="rounded-circle mb-2" width="100" height="100" style="object-fit:cover;">
                                <input type="file" class="form-control" name="profile_pic">
                                <input type="hidden" name="cropped_image" id="cropped_image">
                                <?php if(!empty($user['profile_pic'])): ?>
                                    <button type="submit" name="delete_profile_pic" class="btn btn-sm btn-outline-danger mt-2" onclick="return confirm('Are you sure you want to remove your profile picture?');">Remove Picture</button>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">About Me (Bio)</label>
                                <textarea class="form-control" name="bio" rows="3" placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-4">
                        <h4 class="mb-3">🔒 Change Password</h4>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-danger">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card p-4 border-danger">
                        <h4 class="text-danger mb-3">⚠️ Danger Zone</h4>
                        <p>Once you delete your account, there is no going back. Please be certain.</p>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete Account</button>
                    </div>
                </div>
            </div>
        </div>

        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; FB Money System <?php echo date('Y'); ?></div>
                    <div>
                        <a href="#">Privacy Policy</a>
                        &middot;
                        <a href="#">Terms &amp; Conditions</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crop Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="img-container">
          <img id="imageToCrop" src="" style="max-width: 100%; display: block;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="cropButton">Crop & Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Account Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you absolutely sure you want to delete your account? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST">
            <button type="submit" name="delete_account" class="btn btn-danger">Delete Account</button>
        </form>
      </div>
    </div>
  </div>
</div>

<button id="backToTop" class="btn btn-primary rounded-circle shadow" style="position: fixed; bottom: 20px; right: 20px; display: none; z-index: 1000; width: 50px; height: 50px;">
    <i class="bi bi-arrow-up"></i>
</button>

<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="liveToast" class="toast align-items-center text-white <?php echo str_replace('alert-', 'bg-', $msg_type); ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <?php echo $message; ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('wrapper').classList.toggle('toggled');
});

// Close Sidebar when clicking outside
document.getElementById('page-content-wrapper').addEventListener('click', function(e) {
    if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) {
        document.getElementById('wrapper').classList.remove('toggled');
    }
});

// Close Sidebar
document.getElementById('sidebarClose').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('wrapper').classList.remove('toggled');
});

// Dark Mode Toggle
const toggle = document.getElementById('darkModeToggle');
const body = document.body;
if(localStorage.getItem('darkMode') === 'enabled'){
    body.classList.add('dark-mode');
    toggle.textContent = '☀️';
}
toggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if(body.classList.contains('dark-mode')){
        localStorage.setItem('darkMode', 'enabled');
        toggle.textContent = '☀️';
    } else {
        localStorage.setItem('darkMode', 'disabled');
        toggle.textContent = '🌙';
    }
});

// Cropper Logic
let cropper;
const image = document.getElementById('imageToCrop');
const input = document.querySelector('input[name="profile_pic"]');
const cropModalEl = document.getElementById('cropModal');
const cropModal = new bootstrap.Modal(cropModalEl);

input.addEventListener('change', function (e) {
    const files = e.target.files;
    if (files && files.length > 0) {
        const file = files[0];
        const reader = new FileReader();
        reader.onload = function (e) {
            image.src = e.target.result;
            cropModal.show();
        };
        reader.readAsDataURL(file);
    }
});

cropModalEl.addEventListener('shown.bs.modal', function () {
    cropper = new Cropper(image, {
        aspectRatio: 1,
        viewMode: 1,
    });
});

cropModalEl.addEventListener('hidden.bs.modal', function () {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
});

document.getElementById('cropButton').addEventListener('click', function () {
    const canvas = cropper.getCroppedCanvas({ width: 300, height: 300 });
    const base64data = canvas.toDataURL('image/png');
    document.getElementById('cropped_image').value = base64data;
    document.querySelectorAll('.rounded-circle.mb-2').forEach(img => img.src = base64data); // Update previews
    cropModal.hide();
});

// Back to Top
const backToTop = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        backToTop.style.display = 'block';
    } else {
        backToTop.style.display = 'none';
    }
});
backToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Page Loader
window.addEventListener('load', function() {
    const loader = document.getElementById('page-loader');
    loader.style.opacity = '0';
    setTimeout(() => {
        loader.style.display = 'none';
    }, 500);
});

// Show Toast if message exists
<?php if($message): ?>
const toastEl = document.getElementById('liveToast');
const toast = new bootstrap.Toast(toastEl);
toast.show();
<?php endif; ?>
</script>
</body>
</html>