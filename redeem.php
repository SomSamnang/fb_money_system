<?php
require_once "config/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$msg_type = "";

// Fetch User Points
$u_stmt = $conn->prepare("SELECT points FROM users WHERE id=?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user_points = $u_stmt->get_result()->fetch_assoc()['points'];
$u_stmt->close();

// Handle Redemption
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_reward'])) {
    $reward_id = (int)$_POST['reward_id'];
    
    // Fetch Reward Details
    $r_stmt = $conn->prepare("SELECT * FROM rewards WHERE id=?");
    $r_stmt->bind_param("i", $reward_id);
    $r_stmt->execute();
    $reward = $r_stmt->get_result()->fetch_assoc();
    $r_stmt->close();

    if ($reward) {
        if ($user_points >= $reward['points_cost']) {
            if ($reward['stock'] == -1 || $reward['stock'] > 0) {
                // Deduct Points
                $conn->query("UPDATE users SET points = points - {$reward['points_cost']} WHERE id=$user_id");
                
                // Create Redemption Record
                $ins = $conn->prepare("INSERT INTO redemptions (user_id, reward_id) VALUES (?, ?)");
                $ins->bind_param("ii", $user_id, $reward_id);
                $ins->execute();
                $ins->close();

                // Decrease Stock if not infinite
                if ($reward['stock'] > 0) {
                    $conn->query("UPDATE rewards SET stock = stock - 1 WHERE id=$reward_id");
                }

                $message = "Redemption successful! Your request is pending approval.";
                $msg_type = "alert-success";
                $user_points -= $reward['points_cost']; // Update local variable
            } else {
                $message = "Sorry, this item is out of stock.";
                $msg_type = "alert-danger";
            }
        } else {
            $message = "You do not have enough points.";
            $msg_type = "alert-danger";
        }
    }
}

// Fetch Rewards
$rewards = $conn->query("SELECT * FROM rewards ORDER BY points_cost ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Rewards - FB Money</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .reward-card { transition: transform 0.2s; border: none; border-radius: 15px; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); overflow: hidden; }
        .reward-card:hover { transform: translateY(-5px); }
        .reward-img { height: 200px; object-fit: cover; width: 100%; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-facebook me-2"></i>FB Money</a>
            <a href="leaderboard.php" class="nav-link text-white mx-3 fw-bold"><i class="bi bi-trophy me-1"></i>Leaderboard</a>
            <a href="redeem.php" class="nav-link text-warning mx-3 fw-bold"><i class="bi bi-gift me-1"></i>Rewards</a>
            <a href="videos.php" class="nav-link text-danger mx-3 fw-bold"><i class="bi bi-play-btn-fill me-1"></i>Videos</a>
            <a href="referrals.php" class="nav-link text-info mx-3 fw-bold"><i class="bi bi-people me-1"></i>Referrals</a>
            <a href="watch_history.php" class="nav-link text-light mx-3 fw-bold"><i class="bi bi-clock-history me-1"></i>History</a>
            <div class="d-flex align-items-center ms-auto">
                <span class="text-warning me-3 fw-bold"><i class="bi bi-coin me-1"></i><?php echo $user_points; ?> Points</span>
                <span class="text-white me-3">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout_user.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Redeem Your Points</h1>
            <p class="text-muted">Exchange your hard-earned points for amazing rewards.</p>
        </div>

        <?php if($message): ?><div class="alert <?php echo $msg_type; ?> text-center mb-4"><?php echo $message; ?></div><?php endif; ?>

        <div class="row g-4">
            <?php if($rewards && $rewards->num_rows > 0): ?>
                <?php while($r = $rewards->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card reward-card h-100">
                        <?php if($r['image']): ?>
                            <img src="admin/uploads/<?php echo htmlspecialchars($r['image']); ?>" class="reward-img" alt="<?php echo htmlspecialchars($r['name']); ?>">
                        <?php else: ?>
                            <div class="reward-img d-flex align-items-center justify-content-center bg-light text-muted"><i class="bi bi-gift display-1"></i></div>
                        <?php endif; ?>
                        <div class="card-body text-center d-flex flex-column">
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($r['name']); ?></h5>
                            <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars($r['description']); ?></p>
                            <h4 class="text-primary fw-bold my-3"><?php echo number_format($r['points_cost']); ?> Points</h4>
                            
                            <form method="POST">
                                <input type="hidden" name="reward_id" value="<?php echo $r['id']; ?>">
                                <?php if($user_points >= $r['points_cost'] && ($r['stock'] == -1 || $r['stock'] > 0)): ?>
                                    <button type="submit" name="redeem_reward" class="btn btn-primary w-100 rounded-pill fw-bold" onclick="return confirm('Redeem this reward for <?php echo $r['points_cost']; ?> points?');">Redeem Now</button>
                                <?php elseif($r['stock'] == 0): ?>
                                    <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" disabled>Out of Stock</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary w-100 rounded-pill fw-bold" disabled>Not Enough Points</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-5">No rewards available at the moment.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>