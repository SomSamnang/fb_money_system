<?php
require_once 'auth.php';
require_once "../config/db.php";

// Handle Actions
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])){
    $task = trim($_POST['task']);
    $priority = $_POST['priority'] ?? 'Medium';
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    if(!empty($task)){
        $stmt = $conn->prepare("INSERT INTO todos (username, task, priority, due_date) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $_SESSION['admin'], $task, $priority, $due_date);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error adding task: " . $conn->error);
        }
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_task'])){
    $id = (int)$_POST['task_id'];
    $task = trim($_POST['task']);
    $priority = $_POST['priority'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    
    if(!empty($task)){
        $stmt = $conn->prepare("UPDATE todos SET task=?, priority=?, due_date=? WHERE id=? AND username=?");
        if ($stmt) {
            $stmt->bind_param("sssis", $task, $priority, $due_date, $id, $_SESSION['admin']);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error updating task: " . $conn->error);
        }
    }
    $redirect_filter = isset($_GET['filter']) ? "?filter=" . urlencode($_GET['filter']) : "";
    header("Location: do_done.php" . $redirect_filter);
    exit();
}

// Handle Reorder
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reorder_tasks'])){
    $order = $_POST['order'];
    if(is_array($order)){
        foreach($order as $pos => $id){
            $conn->query("UPDATE todos SET position=" . (int)$pos . " WHERE id=" . (int)$id);
        }
    }
    exit('success');
}

// Handle Add Comment
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])){
    $task_id = (int)$_POST['task_id'];
    $comment = trim($_POST['comment']);
    if(!empty($comment)){
        $stmt = $conn->prepare("INSERT INTO task_comments (task_id, username, comment) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $task_id, $_SESSION['admin'], $comment);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Error adding comment: " . $conn->error);
        }
    }
    $redirect_filter = isset($_GET['filter']) ? "?filter=" . urlencode($_GET['filter']) : "";
    header("Location: do_done.php" . $redirect_filter);
    exit();
}

// Handle Get Comments
if(isset($_GET['action']) && $_GET['action'] == 'get_comments' && isset($_GET['task_id'])){
    $task_id = (int)$_GET['task_id'];
    $stmt = $conn->prepare("SELECT * FROM task_comments WHERE task_id=? ORDER BY created_at ASC");
    if ($stmt) {
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $comments = [];
        while($row = $res->fetch_assoc()){
            $comments[] = [
                'username' => htmlspecialchars($row['username']),
                'comment' => nl2br(htmlspecialchars($row['comment'])),
                'created_at' => date('M d, H:i', strtotime($row['created_at']))
            ];
        }
        echo json_encode($comments);
    } else {
        echo json_encode(['error' => $conn->error]);
    }
    exit();
}

// Handle Get Calendar Events
if(isset($_GET['action']) && $_GET['action'] == 'get_events'){
    $filter_sql = (isset($_GET['filter']) && $_GET['filter'] == 'mine') ? " AND username = '" . $conn->real_escape_string($_SESSION['admin']) . "'" : "";
    $result = $conn->query("SELECT id, task, due_date, priority, status FROM todos WHERE due_date IS NOT NULL $filter_sql");
    $events = [];
    while($row = $result->fetch_assoc()){
        if($row['status'] == 'done') { $color = '#198754'; }
        else {
            switch($row['priority']) {
                case 'High': $color = '#dc3545'; break;
                case 'Low': $color = '#0dcaf0'; break;
                default: $color = '#ffc107'; break;
            }
        }
        $events[] = [
            'id' => $row['id'],
            'title' => $row['task'],
            'start' => $row['due_date'],
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => ($row['priority'] == 'Medium' && $row['status'] != 'done') ? '#000' : '#fff',
            'extendedProps' => ['priority' => $row['priority']]
        ];
    }
    echo json_encode($events);
    exit();
}

if(isset($_GET['action']) && isset($_GET['id'])){
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if($action == 'done'){
        $conn->query("UPDATE todos SET status='done' WHERE id=$id");
    } elseif($action == 'delete'){
        $conn->query("DELETE FROM todos WHERE id=$id");
        $conn->query("DELETE FROM task_comments WHERE task_id=$id");
    } elseif($action == 'undo'){
        $conn->query("UPDATE todos SET status='pending' WHERE id=$id");
    }
    $redirect_filter = isset($_GET['filter']) ? "?filter=" . urlencode($_GET['filter']) : "";
    header("Location: do_done.php" . $redirect_filter);
    exit();
}

if(isset($_GET['action']) && $_GET['action'] == 'export'){
    $filter_sql = (isset($_GET['filter']) && $_GET['filter'] == 'mine') ? " AND username = '" . $conn->real_escape_string($_SESSION['admin']) . "'" : "";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="tasks_export_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Username', 'Task', 'Status', 'Created At', 'Priority', 'Due Date']);
    $query = "SELECT id, username, task, status, created_at, priority, due_date FROM todos WHERE 1 $filter_sql ORDER BY created_at DESC";
    $result = $conn->query($query);
    while($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

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
$profile_pic = !empty($u_row['profile_pic']) ? "uploads/" . $u_row['profile_pic'] : "https://via.placeholder.com/150";
$last_login = !empty($u_row['last_login']) ? date("M d, Y h:i A", strtotime($u_row['last_login'])) : "First Login";
$bio = $u_row['bio'] ?? '';
$user_role = $u_row['role'] ?? 'editor';

// Calculate Progress
$filter_sql = (isset($_GET['filter']) && $_GET['filter'] == 'mine') ? " AND username = '" . $conn->real_escape_string($_SESSION['admin']) . "'" : "";
$total_res = $conn->query("SELECT COUNT(*) FROM todos WHERE 1 $filter_sql");
$total_tasks = $total_res ? $total_res->fetch_row()[0] : 0;

$done_res = $conn->query("SELECT COUNT(*) FROM todos WHERE status='done' $filter_sql");
$completed_tasks = $done_res ? $done_res->fetch_row()[0] : 0;

$progress = ($total_tasks > 0) ? round(($completed_tasks / $total_tasks) * 100) : 0;

// Check for tasks due today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) FROM todos WHERE username=? AND due_date=? AND status='pending'");
if ($stmt) {
    $stmt->bind_param("ss", $_SESSION['admin'], $today);
    $stmt->execute();
    $due_today_count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
} else {
    $due_today_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Do Done - FB Money System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
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
                <span class="navbar-text ms-auto fw-bold text-primary">Do Done</span>
                <button class="btn btn-sm btn-outline-secondary ms-3" id="darkModeToggle">🌙</button>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <?php if($due_today_count > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Reminder:</strong> You have <strong><?php echo $due_today_count; ?></strong> task(s) due today!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark mb-1">✅ Do Done List</h2>
                    <p class="text-muted mb-0">Manage your tasks and productivity.</p>
                </div>
            </div>
            
            <!-- Add Task -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
                <div class="card-body p-4">
                <form method="POST" class="d-flex gap-2">
                    <input type="text" name="task" class="form-control form-control-lg bg-light border-0" placeholder="What needs to be done?" required>
                    <select name="priority" class="form-select form-select-lg bg-light border-0" style="max-width: 140px;">
                        <option value="High">High</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="Low">Low</option>
                    </select>
                    <input type="date" name="due_date" class="form-control form-control-lg bg-light border-0" style="max-width: 180px;" title="Due Date">
                    <button type="submit" name="add_task" class="btn btn-primary btn-lg shadow-sm fw-bold px-4"><i class="bi bi-plus-lg"></i></button>
                </form>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" id="viewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#listView" type="button" role="tab">List View</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendarView" type="button" role="tab">Calendar View</button>
                </li>
            </ul>

            <div class="tab-content" id="viewTabsContent">
                <div class="tab-pane fade show active" id="listView" role="tabpanel">
            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold text-secondary">Task Completion</span>
                    <span class="fw-bold text-success"><?php echo $progress; ?>%</span>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar bg-success progress-bar-striped" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $progress; ?>%</div>
                </div>
            </div>

        <div class="d-flex justify-content-end mb-3">
            <div class="btn-group">
                <a href="do_done.php" class="btn btn-outline-secondary <?php echo !isset($_GET['filter']) ? 'active' : ''; ?>">All Tasks</a>
                <a href="do_done.php?filter=mine" class="btn btn-outline-secondary <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'mine') ? 'active' : ''; ?>">My Tasks</a>
            </div>
            <a href="?action=export<?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?>" class="btn btn-success ms-2"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
        </div>

            <div class="row g-4">
                <!-- To Do Column -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="card-header text-dark fw-bold p-3" style="background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); border:none;"><i class="bi bi-list-task me-2"></i>To Do (Pending)</div>
                        <ul class="list-group list-group-flush" id="pendingList">
                            <?php 
                        $pending = $conn->query("SELECT * FROM todos WHERE status='pending' $filter_sql ORDER BY position ASC, created_at DESC");
                            if($pending && $pending->num_rows > 0):
                                while($row = $pending->fetch_assoc()): 
                                    $is_overdue = !empty($row['due_date']) && strtotime($row['due_date']) < strtotime(date('Y-m-d'));
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $is_overdue ? 'list-group-item-danger' : ''; ?>" data-id="<?php echo $row['id']; ?>" style="cursor: move;">
                                <div>
                                    <?php 
                                    $p = $row['priority'] ?? 'Medium';
                                    $badge = ($p == 'High') ? 'bg-danger' : (($p == 'Medium') ? 'bg-warning text-dark' : 'bg-info text-dark');
                                    ?>
                                    <span class="badge <?php echo $badge; ?> me-1"><?php echo $p; ?></span>
                                    <?php if(!empty($row['due_date'])): ?>
                                    <span class="badge <?php echo $is_overdue ? 'bg-danger' : 'bg-light text-dark border'; ?> me-1" title="<?php echo $is_overdue ? 'Overdue' : 'Due Date'; ?>"><i class="bi bi-calendar"></i> <?php echo date('M d', strtotime($row['due_date'])); ?></span>
                                    <?php endif; ?>
                                    <strong><?php echo htmlspecialchars($row['task']); ?></strong><br>
                                    <small class="text-muted">By: <?php echo htmlspecialchars($row['username']); ?> • <?php echo date('M d, H:i', strtotime($row['created_at'])); ?></small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                <a href="?action=done&id=<?php echo $row['id']; ?><?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?>" class="btn btn-success" title="Mark Done"><i class="bi bi-check-lg"></i></a>
                                <button type="button" class="btn btn-info text-white" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-task="<?php echo htmlspecialchars($row['task']); ?>" 
                                    onclick="openCommentsModal(this)" title="Comments">
                                    <i class="bi bi-chat-dots"></i>
                                </button>
                                <button type="button" class="btn btn-primary" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-task="<?php echo htmlspecialchars($row['task']); ?>" 
                                    data-priority="<?php echo $row['priority']; ?>" 
                                    data-due="<?php echo $row['due_date']; ?>" 
                                    onclick="openEditModal(this)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="?action=delete&id=<?php echo $row['id']; ?><?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Delete this task?');"><i class="bi bi-trash"></i></a>
                                </div>
                            </li>
                            <?php endwhile; else: ?>
                            <li class="list-group-item text-center text-muted">No pending tasks.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Done Column -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="card-header text-white fw-bold p-3" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border:none;"><i class="bi bi-check2-circle me-2"></i>Done (Completed)</div>
                        <ul class="list-group list-group-flush" id="doneList">
                            <?php 
                        $done = $conn->query("SELECT * FROM todos WHERE status='done' $filter_sql ORDER BY position ASC, created_at DESC LIMIT 20");
                            if($done && $done->num_rows > 0):
                                while($row = $done->fetch_assoc()): 
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                <div class="text-decoration-line-through text-muted">
                                    <?php 
                                    $p = $row['priority'] ?? 'Medium';
                                    $badge = ($p == 'High') ? 'bg-danger' : (($p == 'Medium') ? 'bg-warning text-dark' : 'bg-info text-dark');
                                    ?>
                                    <span class="badge <?php echo $badge; ?> me-1"><?php echo $p; ?></span>
                                    <?php if(!empty($row['due_date'])): ?>
                                    <span class="badge bg-light text-dark border me-1" title="Due Date"><i class="bi bi-calendar"></i> <?php echo date('M d', strtotime($row['due_date'])); ?></span>
                                    <?php endif; ?>
                                    <strong><?php echo htmlspecialchars($row['task']); ?></strong><br>
                                    <small>By: <?php echo htmlspecialchars($row['username']); ?></small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                <a href="?action=undo&id=<?php echo $row['id']; ?><?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?>" class="btn btn-secondary" title="Undo"><i class="bi bi-arrow-counterclockwise"></i></a>
                                <button type="button" class="btn btn-info text-white" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-task="<?php echo htmlspecialchars($row['task']); ?>" 
                                    onclick="openCommentsModal(this)" title="Comments">
                                    <i class="bi bi-chat-dots"></i>
                                </button>
                                <button type="button" class="btn btn-primary" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-task="<?php echo htmlspecialchars($row['task']); ?>" 
                                    data-priority="<?php echo $row['priority']; ?>" 
                                    data-due="<?php echo $row['due_date']; ?>" 
                                    onclick="openEditModal(this)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="?action=delete&id=<?php echo $row['id']; ?><?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Delete this task?');"><i class="bi bi-trash"></i></a>
                                </div>
                            </li>
                            <?php endwhile; else: ?>
                            <li class="list-group-item text-center text-muted">No completed tasks yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
                </div>
                <div class="tab-pane fade" id="calendarView" role="tabpanel">
                    <div class="card p-3 shadow-sm">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="task_id" id="editTaskId">
            <div class="mb-3">
                <label class="form-label">Task</label>
                <input type="text" name="task" id="editTaskName" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Priority</label>
                <select name="priority" id="editTaskPriority" class="form-select">
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" id="editTaskDueDate" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_task" class="btn btn-primary">Save Changes</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Comments Modal -->
<div class="modal fade" id="commentsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Comments: <span id="commentTaskTitle"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="commentsList" class="mb-3" style="max-height: 300px; overflow-y: auto;"></div>
        <form method="POST">
            <input type="hidden" name="task_id" id="commentTaskId">
            <div class="input-group">
                <input type="text" name="comment" class="form-control" placeholder="Write a comment..." required>
                <button type="submit" name="add_comment" class="btn btn-primary">Post</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); document.getElementById('wrapper').classList.toggle('toggled'); });
document.getElementById('page-content-wrapper').addEventListener('click', function(e) { if (document.getElementById('wrapper').classList.contains('toggled') && window.innerWidth <= 768) { document.getElementById('wrapper').classList.remove('toggled'); } });
document.getElementById('sidebarClose').addEventListener('click', function(e) { e.preventDefault(); document.getElementById('wrapper').classList.remove('toggled'); });
const toggle = document.getElementById('darkModeToggle'); const body = document.body; if(localStorage.getItem('darkMode') === 'enabled'){ body.classList.add('dark-mode'); toggle.textContent = '☀️'; } toggle.addEventListener('click', () => { body.classList.toggle('dark-mode'); localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'enabled' : 'disabled'); toggle.textContent = body.classList.contains('dark-mode') ? '☀️' : '🌙'; });

function openEditModal(btn) {
    document.getElementById('editTaskId').value = btn.getAttribute('data-id');
    document.getElementById('editTaskName').value = btn.getAttribute('data-task');
    document.getElementById('editTaskPriority').value = btn.getAttribute('data-priority');
    document.getElementById('editTaskDueDate').value = btn.getAttribute('data-due');
    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
}

function openCommentsModal(btn) {
    const taskId = btn.getAttribute('data-id');
    const taskTitle = btn.getAttribute('data-task');
    
    document.getElementById('commentTaskId').value = taskId;
    document.getElementById('commentTaskTitle').textContent = taskTitle;
    
    const list = document.getElementById('commentsList');
    list.innerHTML = '<div class="text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Loading...</div>';
    
    new bootstrap.Modal(document.getElementById('commentsModal')).show();
    
    fetch('do_done.php?action=get_comments&task_id=' + taskId)
        .then(response => response.json())
        .then(data => {
            list.innerHTML = '';
            if(data.length > 0){
                data.forEach(c => {
                    list.innerHTML += `
                        <div class="mb-2 border-bottom pb-2">
                            <div class="d-flex justify-content-between">
                                <strong class="small">${c.username}</strong>
                                <span class="text-muted small" style="font-size:0.75rem">${c.created_at}</span>
                            </div>
                            <div class="small">${c.comment}</div>
                        </div>
                    `;
                });
            } else {
                list.innerHTML = '<div class="text-center text-muted small">No comments yet.</div>';
            }
        });
}

// Drag and Drop Logic
const pendingList = document.getElementById('pendingList');
if(pendingList){
    new Sortable(pendingList, {
        animation: 150,
        ghostClass: 'bg-light',
        onEnd: function() {
            let order = [];
            document.querySelectorAll('#pendingList li').forEach((el, index) => {
                order.push(el.getAttribute('data-id'));
            });
            fetch('do_done.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'reorder_tasks=1&' + order.map((id, i) => `order[${i}]=${id}`).join('&')
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        events: 'do_done.php?action=get_events<?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?>',
        height: 650,
        eventClick: function(info) {
            document.getElementById('editTaskId').value = info.event.id;
            document.getElementById('editTaskName').value = info.event.title;
            document.getElementById('editTaskPriority').value = info.event.extendedProps.priority;
            document.getElementById('editTaskDueDate').value = info.event.startStr;
            new bootstrap.Modal(document.getElementById('editTaskModal')).show();
        }
    });
    
    var tabEl = document.querySelector('button[data-bs-target="#calendarView"]');
    tabEl.addEventListener('shown.bs.tab', function (event) {
        calendar.render();
    });

    var activeTab = localStorage.getItem('activeDoDoneTab');
    if(activeTab){
        var tabTrigger = new bootstrap.Tab(document.querySelector(activeTab));
        tabTrigger.show();
    }
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => localStorage.setItem('activeDoDoneTab', '#' + event.target.id));
    });
});
</script>
</body>
</html>