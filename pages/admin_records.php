<?php
session_start();
require_once '../config/db.php';

// Access Control
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /elderly_care/login.php");
    exit();
}

// Check DB Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// --- HANDLE POST ACTIONS (CREATE, UPDATE & DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD CAREGIVER
    if ($action === 'add_caregiver') {
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $shift = trim($_POST['shift'] ?? '');

        if (!empty($name) && !empty($contact) && !empty($shift)) {
            $stmt = $conn->prepare("INSERT INTO caregivers (name, contact, shift) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $contact, $shift);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_records.php?status=cg_added");
            exit();
        }
    }

    // 2. EDIT CAREGIVER
    if ($action === 'edit_caregiver') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $shift = trim($_POST['shift'] ?? '');

        if ($id > 0 && !empty($name) && !empty($contact) && !empty($shift)) {
            try {
                $stmt = $conn->prepare("UPDATE caregivers SET name = ?, contact = ?, shift = ? WHERE caregiver_id = ?");
                $stmt->bind_param("sssi", $name, $contact, $shift, $id);
                $stmt->execute();
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $stmt = $conn->prepare("UPDATE caregivers SET name = ?, contact = ?, shift = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $contact, $shift, $id);
                $stmt->execute();
                $stmt->close();
            }
            header("Location: admin_records.php?status=cg_updated");
            exit();
        }
    }

    // 3. ADD ELDERLY RESIDENT
    if ($action === 'add_resident') {
        $name = trim($_POST['name'] ?? '');
        $age = intval($_POST['age'] ?? 0);
        $condition = trim($_POST['condition'] ?? '');
        $assigned_caregiver_id = intval($_POST['assigned_caregiver_id'] ?? 0);

        if (!empty($name) && $age > 0 && !empty($condition) && $assigned_caregiver_id > 0) {
            $stmt = $conn->prepare("INSERT INTO residents (name, age, medical_condition, assigned_caregiver_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sisi", $name, $age, $condition, $assigned_caregiver_id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_records.php?status=res_added");
            exit();
        }
    }

    // 4. EDIT ELDERLY RESIDENT
    if ($action === 'edit_resident') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $age = intval($_POST['age'] ?? 0);
        $condition = trim($_POST['condition'] ?? '');
        $assigned_caregiver_id = intval($_POST['assigned_caregiver_id'] ?? 0);

        if ($id > 0 && !empty($name) && $age > 0 && !empty($condition) && $assigned_caregiver_id > 0) {
            try {
                $stmt = $conn->prepare("UPDATE residents SET name = ?, age = ?, medical_condition = ?, assigned_caregiver_id = ? WHERE resident_id = ?");
                $stmt->bind_param("sisii", $name, $age, $condition, $assigned_caregiver_id, $id);
                $stmt->execute();
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $stmt = $conn->prepare("UPDATE residents SET name = ?, age = ?, medical_condition = ?, assigned_caregiver_id = ? WHERE id = ?");
                $stmt->bind_param("sisii", $name, $age, $condition, $assigned_caregiver_id, $id);
                $stmt->execute();
                $stmt->close();
            }
            header("Location: admin_records.php?status=res_updated");
            exit();
        }
    }

    // 5. DELETE CAREGIVER
    if ($action === 'delete_caregiver') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM caregivers WHERE caregiver_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $stmt = $conn->prepare("DELETE FROM caregivers WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
            header("Location: admin_records.php?status=cg_deleted");
            exit();
        }
    }

    // 6. DELETE RESIDENT
    if ($action === 'delete_resident') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM residents WHERE resident_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
            header("Location: admin_records.php?status=res_deleted");
            exit();
        }
    }
}

// --- FETCH DATA FOR DIRECTORIES ---
$caregiver_list = [];
$caregiver_result = $conn->query("SELECT * FROM caregivers ORDER BY name ASC");
if ($caregiver_result && $caregiver_result->num_rows > 0) {
    while ($cg = $caregiver_result->fetch_assoc()) {
        $caregiver_list[] = $cg;
    }
}

// Fetch Residents
$resident_query = "SELECT r.*, c.name AS caregiver_name 
                  FROM residents r 
                  LEFT JOIN caregivers c ON r.assigned_caregiver_id = c.caregiver_id 
                  ORDER BY r.resident_id DESC";
$resident_result = $conn->query($resident_query);

if (!$resident_result) {
    $resident_query = "SELECT r.*, c.name AS caregiver_name 
                      FROM residents r 
                      LEFT JOIN caregivers c ON r.assigned_caregiver_id = c.id";
    $resident_result = $conn->query($resident_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Records Management</title>
    <link rel="stylesheet" href="/elderly_care/assets/css/style.css">
</head>
<body class="scrollable-page">
    <div class="actor-container">
        <h2>Staff & Resident Records Management</h2>
        <p><a href="/elderly_care/pages/admin.php">← Back to Dashboard</a></p>

        <?php if (isset($_GET['status'])): ?>
            <p style="color: green; font-weight: bold;">
                <?php 
                    $status = htmlspecialchars($_GET['status']);
                    if ($status === 'cg_added') echo "Caregiver added successfully!";
                    elseif ($status === 'cg_updated') echo "Caregiver updated successfully!";
                    elseif ($status === 'res_added') echo "Resident registered successfully!";
                    elseif ($status === 'res_updated') echo "Resident updated successfully!";
                    elseif ($status === 'cg_deleted') echo "Caregiver record deleted!";
                    elseif ($status === 'res_deleted') echo "Resident record deleted!";
                ?>
            </p>
        <?php endif; ?>

        <div class="admin-grid-two-col">
            <!-- CAREGIVER REGISTRATION -->
            <div class="panel-card">
                <h3>Register Caregiver Staff</h3>
                <form action="admin_records.php" method="POST">
                    <input type="hidden" name="action" value="add_caregiver">
                    <div class="form-field">
                        <label for="cg_name">Staff Name</label>
                        <input type="text" id="cg_name" name="name" required style="width:100%;">
                    </div>
                    <div class="form-field">
                        <label for="cg_contact">Contact</label>
                        <input type="text" id="cg_contact" name="contact" required style="width:100%;">
                    </div>
                    <div class="form-field">
                        <label for="cg_shift">Shift</label>
                        <select id="cg_shift" name="shift" required style="width:100%;">
                            <option value="Day Shift">Day Shift</option>
                            <option value="Night Shift">Night Shift</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-action btn-add">Add Staff</button>
                </form>
            </div>

            <!-- ELDERLY REGISTRATION -->
            <div class="panel-card">
                <h3>Register Elderly Resident</h3>
                <form action="admin_records.php" method="POST">
                    <input type="hidden" name="action" value="add_resident">
                    <div class="form-field">
                        <label for="res_name">Full Name</label>
                        <input type="text" id="res_name" name="name" required style="width:100%;">
                    </div>
                    <div class="form-field">
                        <label for="res_age">Age</label>
                        <input type="number" id="res_age" name="age" required style="width:100%;">
                    </div>
                    <div class="form-field">
                        <label for="res_condition">Condition</label>
                        <input type="text" id="res_condition" name="condition" required style="width:100%;">
                    </div>
                    <div class="form-field">
                        <label for="res_cg">Assign Caregiver</label>
                        <select id="res_cg" name="assigned_caregiver_id" required style="width:100%;">
                            <option value="">-- Choose Staff --</option>
                            <?php foreach ($caregiver_list as $cg): ?>
                                <?php $cg_id = $cg['caregiver_id'] ?? $cg['id']; ?>
                                <option value="<?php echo $cg_id; ?>">
                                    <?php echo htmlspecialchars($cg['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-action btn-add">Save Resident</button>
                </form>
            </div>
        </div>

        <!-- CAREGIVER DIRECTORY -->
        <div class="panel-card">
            <h3>Registered Caregiver Directory</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Shift</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($caregiver_list)): ?>
                        <?php foreach ($caregiver_list as $row): 
                            $row_id = $row['caregiver_id'] ?? $row['id'];
                        ?>
                            <tr>
                                <td>CG-<?php echo $row_id; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                <td><?php echo htmlspecialchars($row['shift']); ?></td>
                                <td>
                                    <button type="button" class="btn-action btn-edit" onclick="openCaregiverModal(<?php echo htmlspecialchars(json_encode([
                                        'id' => $row_id,
                                        'name' => $row['name'],
                                        'contact' => $row['contact'],
                                        'shift' => $row['shift']
                                    ])); ?>)">Edit</button>

                                    <form action="admin_records.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this caregiver?');">
                                        <input type="hidden" name="action" value="delete_caregiver">
                                        <input type="hidden" name="id" value="<?php echo $row_id; ?>">
                                        <button type="submit" class="btn-action btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No caregiver records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ELDERLY DIRECTORY -->
        <div class="panel-card">
            <h3>Active Elderly Profiles</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Resident ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Condition</th>
                        <th>Assigned Caregiver</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resident_result && $resident_result->num_rows > 0): ?>
                        <?php while ($row = $resident_result->fetch_assoc()): ?>
                            <?php 
                                $res_id = $row['resident_id'] ?? $row['id'];
                                $assigned_cg = $row['assigned_caregiver_id'] ?? 0;
                            ?>
                            <tr>
                                <td>ELD-<?php echo sprintf('%02d', $res_id); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['age']); ?></td>
                                <td><?php echo htmlspecialchars($row['medical_condition']); ?></td>
                                <td><?php echo htmlspecialchars($row['caregiver_name'] ?? 'Unassigned'); ?></td>
                                <td>
                                    <button type="button" class="btn-action btn-edit" onclick="openResidentModal(<?php echo htmlspecialchars(json_encode([
                                        'id' => $res_id,
                                        'name' => $row['name'],
                                        'age' => $row['age'],
                                        'condition' => $row['medical_condition'],
                                        'assigned_caregiver_id' => $assigned_cg
                                    ])); ?>)">Edit</button>

                                    <form action="admin_records.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this resident?');">
                                        <input type="hidden" name="action" value="delete_resident">
                                        <input type="hidden" name="id" value="<?php echo $res_id; ?>">
                                        <button type="submit" class="btn-action btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No resident records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div> <!-- END OF .actor-container -->

    <!-- MODALS ARE PLACED OUTSIDE THE MAIN CONTAINER AT THE BOTTOM -->

    <!-- EDIT CAREGIVER MODAL -->
    <div id="editCaregiverModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Caregiver Staff</h3>
                <button type="button" class="close-btn" onclick="closeModal('editCaregiverModal')">&times;</button>
            </div>
            <form action="admin_records.php" method="POST">
                <input type="hidden" name="action" value="edit_caregiver">
                <input type="hidden" name="id" id="edit_cg_id">
                
                <div class="form-field">
                    <label for="edit_cg_name">Staff Name</label>
                    <input type="text" id="edit_cg_name" name="name" required style="width:100%;">
                </div>
                <div class="form-field">
                    <label for="edit_cg_contact">Contact</label>
                    <input type="text" id="edit_cg_contact" name="contact" required style="width:100%;">
                </div>
                <div class="form-field">
                    <label for="edit_cg_shift">Shift</label>
                    <select id="edit_cg_shift" name="shift" required style="width:100%;">
                        <option value="Day Shift">Day Shift</option>
                        <option value="Night Shift">Night Shift</option>
                    </select>
                </div>
                <button type="submit" class="btn-action btn-add">Update Caregiver</button>
            </form>
        </div>
    </div>

    <!-- EDIT RESIDENT MODAL -->
    <div id="editResidentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Elderly Resident</h3>
                <button type="button" class="close-btn" onclick="closeModal('editResidentModal')">&times;</button>
            </div>
            <form action="admin_records.php" method="POST">
                <input type="hidden" name="action" value="edit_resident">
                <input type="hidden" name="id" id="edit_res_id">
                
                <div class="form-field">
                    <label for="edit_res_name">Full Name</label>
                    <input type="text" id="edit_res_name" name="name" required style="width:100%;">
                </div>
                <div class="form-field">
                    <label for="edit_res_age">Age</label>
                    <input type="number" id="edit_res_age" name="age" required style="width:100%;">
                </div>
                <div class="form-field">
                    <label for="edit_res_condition">Medical Condition</label>
                    <input type="text" id="edit_res_condition" name="condition" required style="width:100%;">
                </div>
                <div class="form-field">
                    <label for="edit_res_cg">Assigned Caregiver</label>
                    <select id="edit_res_cg" name="assigned_caregiver_id" required style="width:100%;">
                        <option value="">-- Choose Staff --</option>
                        <?php foreach ($caregiver_list as $cg): ?>
                            <?php $cg_id = $cg['caregiver_id'] ?? $cg['id']; ?>
                            <option value="<?php echo $cg_id; ?>">
                                <?php echo htmlspecialchars($cg['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-action btn-add">Update Resident</button>
            </form>
        </div>
    </div>

    <!-- SIMPLE JS FUNCTIONS -->
    <script>
        function openCaregiverModal(data) {
            document.getElementById('edit_cg_id').value = data.id;
            document.getElementById('edit_cg_name').value = data.name;
            document.getElementById('edit_cg_contact').value = data.contact;
            document.getElementById('edit_cg_shift').value = data.shift;
            document.getElementById('editCaregiverModal').style.display = 'block';
        }

        function openResidentModal(data) {
            document.getElementById('edit_res_id').value = data.id;
            document.getElementById('edit_res_name').value = data.name;
            document.getElementById('edit_res_age').value = data.age;
            document.getElementById('edit_res_condition').value = data.condition;
            document.getElementById('edit_res_cg').value = data.assigned_caregiver_id;
            document.getElementById('editResidentModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    <script src="/elderly_care/assets/js/navbar.js"></script>
</body>
</html>