<?php
session_start();
require_once '../config/db.php';

// Access Control
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /elderly_care/login.php");
    exit();
}

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// --- HANDLE POST ACTIONS (APPROVE / DECLINE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = intval($_POST['request_id'] ?? 0);

    if ($request_id > 0) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE visit_requests SET status = 'Approved' WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_requests.php?msg=approved");
            exit();
        } elseif ($action === 'decline') {
            $stmt = $conn->prepare("UPDATE visit_requests SET status = 'Declined' WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_requests.php?msg=declined");
            exit();
        }
    }
}

// Fetch Visit Requests from Database
$requests = [];
$query = "SELECT v.*, r.name AS resident_name 
          FROM visit_requests v 
          LEFT JOIN residents r ON v.resident_id = r.resident_id 
          ORDER BY v.request_id DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Family Requests</title>
    <link rel="stylesheet" href="/elderly_care/assets/css/style.css">
</head>
<body class="scrollable-page">
    <div class="actor-container">
        <h2>Family Visit Approvals</h2>
        <p><a href="/elderly_care/pages/admin.php">← Back to Dashboard</a></p>

        <?php if (isset($_GET['msg'])): ?>
            <p style="color: green; font-weight: bold;">
                <?php 
                    $msg = htmlspecialchars($_GET['msg']);
                    if ($msg === 'approved') echo "Visit request approved successfully!";
                    elseif ($msg === 'declined') echo "Visit request declined!";
                ?>
            </p>
        <?php endif; ?>

        <div class="panel-card">
            <h3>Pending Family Visit Requests & Care Notes</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Resident</th>
                        <th>Relative Name</th>
                        <th>Request Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td>REQ-<?php echo sprintf('%03d', $req['request_id']); ?></td>
                                <td><?php echo htmlspecialchars($req['resident_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($req['relative_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['request_details']); ?></td>
                                <td>
                                    <?php 
                                        $status = $req['status'] ?? 'Pending';
                                        if (strtolower($status) === 'approved') {
                                            echo '<span style="color: #2ecc71; font-weight: bold;">Approved</span>';
                                        } elseif (strtolower($status) === 'declined') {
                                            echo '<span style="color: #e74c3c; font-weight: bold;">Declined</span>';
                                        } else {
                                            echo '<span style="color: #f39c12; font-weight: bold;">' . htmlspecialchars($status) . '</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php if (strtolower($req['status'] ?? 'pending') === 'pending'): ?>
                                        <form action="admin_requests.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn-action btn-add" style="background-color: #2ecc71; padding: 6px 12px; font-size: 12px;">Approve</button>
                                        </form>

                                        <form action="admin_requests.php" method="POST" style="display:inline;" onsubmit="return confirm('Decline this request?');">
                                            <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit" class="btn-action btn-delete">Decline</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-size: 12px;">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No visit requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="/elderly_care/assets/js/navbar.js"></script>
</body>
</html>