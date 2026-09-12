<?php
// 1. Start PHP Session
session_start();

// 2. HTTP Cache Control (Prevents browser back-button access after logout)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache");                                   // HTTP 1.0
header("Expires: 0");                                         // Proxies

// 3. Role-Based Guard: Restrict access strictly to Caregiver role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caregiver') {
    header("Location: /elderly_care/login.php");
    exit();
}

require_once '../config/db.php';

$caregiver_id = $_SESSION['user_id'];
$status_msg = "";

// --- UPDATE TASK STATUS ONLY ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'complete_task') {
        $schedule_id = intval($_POST['schedule_id'] ?? 0);

        if ($schedule_id > 0) {
            $stmt = $conn->prepare("UPDATE task_schedules SET status = 'Completed' WHERE schedule_id = ? AND caregiver_id = ?");
            $stmt->bind_param("ii", $schedule_id, $caregiver_id);
            if ($stmt->execute()) {
                $status_msg = "Task marked as completed successfully!";
            }
            $stmt->close();
        }
    }
}

// FETCH TODAY'S ASSIGNED TASKS (SCHEDULED BY ADMIN)
$tasks = [];
$task_stmt = $conn->prepare("SELECT s.schedule_id, s.task_details, s.scheduled_time, s.status, r.name AS resident_name FROM task_schedules s LEFT JOIN residents r ON s.resident_id = r.resident_id WHERE s.caregiver_id = ? ORDER BY s.scheduled_time ASC");
$task_stmt->bind_param("i", $caregiver_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();
if ($task_result && $task_result->num_rows > 0) {
    while ($t = $task_result->fetch_assoc()) {
        $tasks[] = $t;
    }
}
$task_stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Caregiver Task Manager</title>
    <link rel="stylesheet" href="/elderly_care/assets/css/style.css">
    
    <script>
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</head>
<body class="scrollable-page">

    <div class="actor-container">
        <h2>Assigned Care Tasks & Schedule</h2>

        <?php if (!empty($status_msg)): ?>
            <p style="color: green; font-weight: bold; padding: 10px; background-color: #e8f8f5; border-radius: 4px;">
                <?php echo htmlspecialchars($status_msg); ?>
            </p>
        <?php endif; ?>

        <!-- ASSIGNED TASKS TABLE -->
        <div class="panel-card">
            <h3>Today's Schedule (Assigned by Admin)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Scheduled Time</th>
                        <th>Resident Name</th>
                        <th>Task / Medication Details</th>
                        <th>Current Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                            <?php $is_completed = (strtolower($task['status']) === 'completed'); ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('h:i A', strtotime($task['scheduled_time']))); ?></td>
                                <td><?php echo htmlspecialchars($task['resident_name'] ?? 'Unassigned'); ?></td>
                                <td><?php echo htmlspecialchars($task['task_details']); ?></td>
                                <td style="color: <?php echo $is_completed ? 'green' : '#f39c12'; ?>; font-weight: bold;">
                                    <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                                </td>
                                <td>
                                    <?php if (!$is_completed): ?>
                                        <form action="" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="complete_task">
                                            <input type="hidden" name="schedule_id" value="<?php echo $task['schedule_id']; ?>">
                                            <button type="submit" class="btn-action btn-add" style="background-color: #2ecc71;">Mark as Done</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-size: 13px; font-weight: bold;">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No care tasks scheduled for you today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Pass real PHP session state to navbar.js -->
    <script>
        window.APP_USER = {
            isLoggedIn: true,
            name: "<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Caregiver'); ?>",
            role: "<?php echo htmlspecialchars($_SESSION['role']); ?>"
        };
    </script>

    <!-- Unified Navbar Script with Cache-Busting -->
    <script src="/elderly_care/assets/js/navbar.js?v=2"></script>
</body>
</html>