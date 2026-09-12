<?php
// 1. Initialize session memory
session_start();

// 2. Prevent browser back-button caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Access Control: Restrict to logged-in family users
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'family') {
    header("Location: /elderly_care/login.php");
    exit();
}

require_once '../config/db.php';

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$relative_name = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Family Member';
$status_msg = "";

// --- HANDLE POST ACTIONS (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE: Submit new visit request
    if ($action === 'create_request') {
        $resident_id = intval($_POST['resident_id'] ?? 0);
        $visit_date = trim($_POST['visit_date'] ?? '');
        $notes = trim($_POST['request_notes'] ?? '');

        if ($resident_id > 0 && !empty($visit_date) && !empty($notes)) {
            $request_details = "Visit Date: " . $visit_date . " | Notes: " . $notes;
            
            $stmt = $conn->prepare("INSERT INTO visit_requests (resident_id, relative_name, request_details, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->bind_param("iss", $resident_id, $relative_name, $request_details);
            if ($stmt->execute()) {
                $status_msg = "Visit request submitted successfully!";
            }
            $stmt->close();
        }
    }

    // UPDATE: Modify existing request details
    if ($action === 'update_request') {
        $request_id = intval($_POST['request_id'] ?? 0);
        $request_details = trim($_POST['request_details'] ?? '');

        if ($request_id > 0 && !empty($request_details)) {
            $stmt = $conn->prepare("UPDATE visit_requests SET request_details = ? WHERE request_id = ? AND relative_name = ?");
            $stmt->bind_param("sis", $request_details, $request_id, $relative_name);
            if ($stmt->execute()) {
                $status_msg = "Request updated successfully!";
            }
            $stmt->close();
        }
    }

    // DELETE: Cancel/Delete request
    if ($action === 'delete_request') {
        $request_id = intval($_POST['request_id'] ?? 0);

        if ($request_id > 0) {
            $stmt = $conn->prepare("DELETE FROM visit_requests WHERE request_id = ? AND relative_name = ?");
            $stmt->bind_param("is", $request_id, $relative_name);
            if ($stmt->execute()) {
                $status_msg = "Request cancelled successfully!";
            }
            $stmt->close();
        }
    }
}

// READ: Fetch Residents List for Dropdown
$residents = [];
$res_query = $conn->query("SELECT resident_id, name FROM residents ORDER BY name ASC");
if ($res_query && $res_query->num_rows > 0) {
    while ($r = $res_query->fetch_assoc()) {
        $residents[] = $r;
    }
}

// READ: Fetch Requests logged by this relative (Joined with residents for name display)
$visit_requests = [];
$req_stmt = $conn->prepare("
    SELECT vr.*, r.name AS resident_name 
    FROM visit_requests vr 
    LEFT JOIN residents r ON vr.resident_id = r.resident_id 
    WHERE vr.relative_name = ? 
    ORDER BY vr.request_id DESC
");

if ($req_stmt) {
    $req_stmt->bind_param("s", $relative_name);
    $req_stmt->execute();
    $req_res = $req_stmt->get_result();
    while ($row = $req_res->fetch_assoc()) {
        $visit_requests[] = $row;
    }
    $req_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Family Portal - Patient Monitoring & Visits</title>
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
        <h2>Family Portal: Visit Scheduling & Care Notes</h2>

        <?php if (!empty($status_msg)): ?>
            <p style="color: green; font-weight: bold; padding: 10px; background-color: #e8f8f5; border-radius: 4px;">
                <?php echo htmlspecialchars($status_msg); ?>
            </p>
        <?php endif; ?>

        <!-- SECTION: SUBMIT VISIT REQUEST -->
        <div class="panel-card">
            <h3>Submit Visit Request or Special Care Note</h3>
            <form action="family.php" method="POST">
                <input type="hidden" name="action" value="create_request">
                
                <div class="form-field" style="margin-bottom: 12px;">
                    <label for="resident_select">Select Resident / Relative</label>
                    <select id="resident_select" name="resident_id" required style="width: 100%; max-width: 400px;">
                        <option value="">-- Select Resident --</option>
                        <?php foreach ($residents as $res): ?>
                            <option value="<?php echo $res['resident_id']; ?>">
                                <?php echo htmlspecialchars($res['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field" style="margin-bottom: 12px;">
                    <label for="visit_date">Planned Visit Date</label>
                    <input type="date" id="visit_date" name="visit_date" required>
                </div>

                <div class="form-field" style="margin-bottom: 12px;">
                    <label for="request_notes">Special Notes / Requests</label>
                    <input type="text" id="request_notes" name="request_notes" style="width: 100%; max-width: 400px;" placeholder="e.g., Bringing personal clothing items" required>
                </div>

                <button type="submit" class="btn-action btn-add" style="margin-top: 5px;">Submit Request</button>
            </form>
        </div>

        <!-- SECTION: READ, EDIT & DELETE REQUESTS -->
        <div class="panel-card" style="margin-top: 20px;">
            <h3>Your Submitted Requests History</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Req ID</th>
                            <th>Resident Name</th>
                            <th>Request Details</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($visit_requests)): ?>
                            <?php foreach ($visit_requests as $req): ?>
                                <tr>
                                    <td>REQ-<?php echo sprintf('%03d', $req['request_id']); ?></td>
                                    <td><?php echo htmlspecialchars($req['resident_name'] ?? 'Unassigned'); ?></td>
                                    <td><?php echo htmlspecialchars($req['request_details']); ?></td>
                                    <td>
                                        <strong style="color: <?php echo strtolower($req['status']) === 'approved' ? 'green' : (strtolower($req['status']) === 'rejected' ? 'red' : '#f39c12'); ?>;">
                                            <?php echo htmlspecialchars(ucfirst($req['status'])); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode([
                                            'request_id' => $req['request_id'],
                                            'request_details' => $req['request_details']
                                        ])); ?>)">Edit</button>

                                        <form action="family.php" method="POST" style="display:inline;" onsubmit="return confirm('Cancel this visit request?');">
                                            <input type="hidden" name="action" value="delete_request">
                                            <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                            <button type="submit" class="btn-action btn-delete">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No visit requests submitted yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editReqModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
        <div style="background-color:#fff; margin:10% auto; padding:20px; border-radius:8px; width:400px; max-width:90%;">
            <h3>Edit Visit Request Details</h3>
            <form action="family.php" method="POST">
                <input type="hidden" name="action" value="update_request">
                <input type="hidden" name="request_id" id="edit_request_id">
                
                <div class="form-field" style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px;">Request Details</label>
                    <textarea id="edit_request_details" name="request_details" rows="4" style="width:100%; padding:8px;" required></textarea>
                </div>

                <button type="submit" class="btn-action btn-add">Save Changes</button>
                <button type="button" class="btn-action btn-delete" onclick="closeEditModal()">Close</button>
            </form>
        </div>
    </div>

    <script>
        window.APP_USER = {
            isLoggedIn: true,
            name: "<?php echo htmlspecialchars($relative_name); ?>",
            role: "<?php echo htmlspecialchars($_SESSION['role']); ?>"
        };

        function openEditModal(data) {
            document.getElementById('edit_request_id').value = data.request_id;
            document.getElementById('edit_request_details').value = data.request_details;
            document.getElementById('editReqModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editReqModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('editReqModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    </script>
    <script src="/elderly_care/assets/js/navbar.js?v=2"></script>
</body>
</html>