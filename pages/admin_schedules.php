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

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD APPOINTMENT
    if ($action === 'add_appointment') {
        $resident_id = intval($_POST['resident_id'] ?? 0);
        $doctor = trim($_POST['doctor_name'] ?? '');
        $date_time = $_POST['appointment_datetime'] ?? '';
        $purpose = trim($_POST['purpose'] ?? '');

        if ($resident_id > 0 && !empty($doctor) && !empty($date_time)) {
            $stmt = $conn->prepare("INSERT INTO appointments (resident_id, doctor_name, appointment_datetime, purpose) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $resident_id, $doctor, $date_time, $purpose);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_schedules.php?status=apt_added");
            exit();
        }
    }

    // EDIT APPOINTMENT
    if ($action === 'edit_appointment') {
        $id = intval($_POST['id'] ?? 0);
        $resident_id = intval($_POST['resident_id'] ?? 0);
        $doctor = trim($_POST['doctor_name'] ?? '');
        $date_time = $_POST['appointment_datetime'] ?? '';
        $purpose = trim($_POST['purpose'] ?? '');

        if ($id > 0 && $resident_id > 0 && !empty($doctor) && !empty($date_time)) {
            $stmt = $conn->prepare("UPDATE appointments SET resident_id = ?, doctor_name = ?, appointment_datetime = ?, purpose = ? WHERE appointment_id = ?");
            $stmt->bind_param("isssi", $resident_id, $doctor, $date_time, $purpose, $id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_schedules.php?status=apt_updated");
            exit();
        }
    }

    // DELETE APPOINTMENT
    if ($action === 'delete_appointment') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_schedules.php?status=apt_deleted");
            exit();
        }
    }

// ADD CARE TASK / SCHEDULE
    if ($action === 'add_task') {
        $resident_id = intval($_POST['resident_id'] ?? 0);
        $caregiver_id = intval($_POST['caregiver_id'] ?? 0);
        $task_details = trim($_POST['task_details'] ?? '');
        $schedule_time = $_POST['schedule_time'] ?? '';

        if ($resident_id > 0 && !empty($task_details) && !empty($schedule_time)) {
            
            // Ensure caregiver exists in `caregivers` table if selected
            if ($caregiver_id > 0) {
                $cg_check = $conn->prepare("INSERT IGNORE INTO caregivers (caregiver_id) VALUES (?)");
                $cg_check->bind_param("i", $caregiver_id);
                $cg_check->execute();
                $cg_check->close();
            } else {
                $caregiver_id = NULL; // Handle unassigned caregivers
            }

            $stmt = $conn->prepare("INSERT INTO task_schedules (resident_id, caregiver_id, task_details, scheduled_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $resident_id, $caregiver_id, $task_details, $schedule_time);
            $stmt->execute();
            $stmt->close();

            header("Location: admin_schedules.php?status=task_added");
            exit();
        }
    }


    // EDIT CARE TASK / SCHEDULE
    if ($action === 'edit_task') {
        $id = intval($_POST['id'] ?? 0);
        $resident_id = intval($_POST['resident_id'] ?? 0);
        $caregiver_id = intval($_POST['caregiver_id'] ?? 0);
        $task_details = trim($_POST['task_details'] ?? '');
        $schedule_time = $_POST['scheduled_time'] ?? '';

        if ($id > 0 && $resident_id > 0 && !empty($task_details) && !empty($schedule_time)) {
            $stmt = $conn->prepare("UPDATE schedules SET resident_id = ?, caregiver_id = ?, task_details = ?, schedule_time = ? WHERE schedule_id = ?");
            $stmt->bind_param("iissi", $resident_id, $caregiver_id, $task_details, $schedule_time, $id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_schedules.php?status=task_updated");
            exit();
        }
    }

    // DELETE CARE TASK / SCHEDULE
    if ($action === 'delete_task') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM task_schedules WHERE schedule_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_schedules.php?status=task_deleted");
            exit();
        }
    }
}

// Fetch Residents List for Dropdowns
$residents = [];
$res_query = $conn->query("SELECT resident_id, name FROM residents ORDER BY name ASC");
if ($res_query && $res_query->num_rows > 0) {
    while ($r = $res_query->fetch_assoc()) {
        $residents[] = $r;
    }
}

// Fetch Caregivers List for Dropdowns
$caregivers = [];
$cg_query = $conn->query("SELECT user_id, full_name AS name FROM users WHERE role = 'caregiver' ORDER BY full_name ASC");
if ($cg_query && $cg_query->num_rows > 0) {
    while ($c = $cg_query->fetch_assoc()) {
        $caregivers[] = $c;
    }
}

// Fetch Appointments Directory
$appointments = [];
$apt_query = "SELECT a.*, r.name AS resident_name 
             FROM appointments a 
             LEFT JOIN residents r ON a.resident_id = r.resident_id 
             ORDER BY a.appointment_datetime ASC";
$apt_result = $conn->query($apt_query);
if ($apt_result && $apt_result->num_rows > 0) {
    while ($apt = $apt_result->fetch_assoc()) {
        $appointments[] = $apt;
    }
}

// Fetch Schedules / Daily Care Tasks Directory
$schedules = [];
$sch_query = "SELECT s.*, r.name AS resident_name, u.full_name AS caregiver_name 
             FROM task_schedules s 
             LEFT JOIN residents r ON s.resident_id = r.resident_id 
             LEFT JOIN users u ON s.caregiver_id = u.user_id 
             ORDER BY s.scheduled_time ASC";
$sch_result = $conn->query($sch_query);
if ($sch_result && $sch_result->num_rows > 0) {
    while ($sch = $sch_result->fetch_assoc()) {
        $schedules[] = $sch;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Schedules Management</title>
    <link rel="stylesheet" href="/elderly_care/assets/css/style.css">
</head>
<body class="scrollable-page">
    <div class="actor-container">
        <h2>Medication, Care & Medical Scheduling</h2>
        <p><a href="/elderly_care/pages/admin.php">← Back to Dashboard</a></p>

        <?php if (isset($_GET['status'])): ?>
            <p style="color: green; font-weight: bold;">
                <?php 
                    $status = htmlspecialchars($_GET['status']);
                    if ($status === 'apt_added') echo "Appointment scheduled successfully!";
                    elseif ($status === 'apt_updated') echo "Appointment updated successfully!";
                    elseif ($status === 'apt_deleted') echo "Appointment deleted!";
                    elseif ($status === 'task_added') echo "Care task allocated successfully!";
                    elseif ($status === 'task_updated') echo "Care task updated successfully!";
                    elseif ($status === 'task_deleted') echo "Care task deleted!";
                ?>
            </p>
        <?php endif; ?>

        <!-- SECTION 1: MEDICAL APPOINTMENTS -->
        <div class="admin-grid-two-col">
            <!-- CREATE APPOINTMENT FORM -->
            <div class="panel-card">
                <h3>Schedule Medical Appointment</h3>
                <form action="admin_schedules.php" method="POST">
                    <input type="hidden" name="action" value="add_appointment">
                    
                    <div class="form-field">
                        <label for="res_id">Resident</label>
                        <select id="res_id" name="resident_id" required style="width:100%;">
                            <option value="">-- Choose Resident --</option>
                            <?php foreach ($residents as $res): ?>
                                <option value="<?php echo $res['resident_id']; ?>">
                                    <?php echo htmlspecialchars($res['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="doctor_name">Doctor / Clinic</label>
                        <input type="text" id="doctor_name" name="doctor_name" required style="width:100%;">
                    </div>

                    <div class="form-field">
                        <label for="appointment_datetime">Date & Time</label>
                        <input type="datetime-local" id="appointment_datetime" name="appointment_datetime" required style="width:100%;">
                    </div>

                    <div class="form-field">
                        <label for="purpose">Purpose</label>
                        <input type="text" id="purpose" name="purpose" style="width:100%;">
                    </div>

                    <button type="submit" class="btn-action btn-add" style="margin-top: 10px;">Schedule Appointment</button>
                </form>
            </div>

            <!-- ALLOCATE CARE TASK FORM -->
            <div class="panel-card">
                <h3>Allocate Daily Care Task & Medication</h3>
                <form action="admin_schedules.php" method="POST">
                    <input type="hidden" name="action" value="add_task">
                    
                    <div class="form-field">
                        <label for="task_res_id">Resident</label>
                        <select id="task_res_id" name="resident_id" required style="width:100%;">
                            <option value="">-- Choose Resident --</option>
                            <?php foreach ($residents as $res): ?>
                                <option value="<?php echo $res['resident_id']; ?>">
                                    <?php echo htmlspecialchars($res['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="caregiver_id">Caregiver</label>
                        <select id="caregiver_id" name="caregiver_id" style="width:100%;">
                            <option value="">-- Choose Caregiver --</option>
                            <?php foreach ($caregivers as $cg): ?>
                                <option value="<?php echo $cg['user_id']; ?>">
                                    <?php echo htmlspecialchars($cg['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="task_details">Task Details</label>
                        <input type="text" id="task_details" name="task_details" placeholder="e.g. Metformin 500mg" required style="width:100%;">
                    </div>

                    <div class="form-field">
                        <label for="schedule_time">Time</label>
                        <input type="time" id="schedule_time" name="schedule_time" required style="width:100%;">
                    </div>

                    <button type="submit" class="btn-action btn-add" style="margin-top: 10px;">Allocate Care Task</button>
                </form>
            </div>
        </div>

        <!-- APPOINTMENTS TABLE -->
        <div class="panel-card" style="margin-top: 20px;">
            <h3>Medical Appointment Schedule</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Resident</th>
                            <th>Doctor / Clinic</th>
                            <th>Date & Time</th>
                            <th>Purpose</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($appointments)): ?>
                            <?php foreach ($appointments as $row): ?>
                                <tr>
                                    <td>APT-<?php echo sprintf('%03d', $row['appointment_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['resident_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d | h:i A', strtotime($row['appointment_datetime']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td>
                                        <button type="button" class="btn-action btn-edit" onclick="openAptModal(<?php echo htmlspecialchars(json_encode([
                                            'id' => $row['appointment_id'],
                                            'resident_id' => $row['resident_id'],
                                            'doctor_name' => $row['doctor_name'],
                                            'appointment_datetime' => $row['appointment_datetime'],
                                            'purpose' => $row['purpose']
                                        ])); ?>)">Edit</button>

                                        <form action="admin_schedules.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this appointment?');">
                                            <input type="hidden" name="action" value="delete_appointment">
                                            <input type="hidden" name="id" value="<?php echo $row['appointment_id']; ?>">
                                            <button type="submit" class="btn-action btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No appointments scheduled.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CARE TASKS TABLE -->
        <div class="panel-card" style="margin-top: 20px;">
            <h3>Daily Care Tasks & Medication Schedule</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Schedule ID</th>
                            <th>Resident</th>
                            <th>Caregiver</th>
                            <th>Task Details</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($schedules)): ?>
                            <?php foreach ($schedules as $sch): ?>
                                <tr>
                                    <td>SCH-<?php echo sprintf('%03d', $sch['schedule_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sch['resident_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($sch['caregiver_name'] ?? 'Unassigned'); ?></td>
                                    <td><?php echo htmlspecialchars($sch['task_details']); ?></td>
                                    <td><?php echo htmlspecialchars(date('h:i A', strtotime($sch['scheduled_time']))); ?></td>
                                    <td>
                                        <button type="button" class="btn-action btn-edit" onclick="openTaskModal(<?php echo htmlspecialchars(json_encode([
                                            'id' => $sch['schedule_id'],
                                            'resident_id' => $sch['resident_id'],
                                            'caregiver_id' => $sch['caregiver_id'],
                                            'task_details' => $sch['task_details'],
                                            'schedule_time' => $sch['scheduled_time']
                                        ])); ?>)">Edit</button>

                                        <form action="admin_schedules.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this task allocation?');">
                                            <input type="hidden" name="action" value="delete_task">
                                            <input type="hidden" name="id" value="<?php echo $sch['schedule_id']; ?>">
                                            <button type="submit" class="btn-action btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No daily care tasks or medications allocated.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div> <!-- END OF .actor-container -->

    <!-- EDIT APPOINTMENT MODAL -->
    <div id="editAptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Appointment</h3>
                <button type="button" class="close-btn" onclick="closeModal('editAptModal')">&times;</button>
            </div>
            <form action="admin_schedules.php" method="POST">
                <input type="hidden" name="action" value="edit_appointment">
                <input type="hidden" name="id" id="edit_apt_id">
                
                <div class="form-field">
                    <label for="edit_res_id">Resident</label>
                    <select id="edit_res_id" name="resident_id" required style="width:100%;">
                        <option value="">-- Choose Resident --</option>
                        <?php foreach ($residents as $res): ?>
                            <option value="<?php echo $res['resident_id']; ?>">
                                <?php echo htmlspecialchars($res['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-field">
                    <label for="edit_doctor_name">Doctor / Clinic</label>
                    <input type="text" id="edit_doctor_name" name="doctor_name" required style="width:100%;">
                </div>

                <div class="form-field">
                    <label for="edit_appointment_datetime">Date & Time</label>
                    <input type="datetime-local" id="edit_appointment_datetime" name="appointment_datetime" required style="width:100%;">
                </div>

                <div class="form-field">
                    <label for="edit_purpose">Purpose</label>
                    <input type="text" id="edit_purpose" name="purpose" style="width:100%;">
                </div>

                <button type="submit" class="btn-action btn-add">Update Appointment</button>
            </form>
        </div>
    </div>

    <!-- EDIT CARE TASK MODAL -->
    <div id="editTaskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Daily Care Task</h3>
                <button type="button" class="close-btn" onclick="closeModal('editTaskModal')">&times;</button>
            </div>
            <form action="admin_schedules.php" method="POST">
                <input type="hidden" name="action" value="edit_task">
                <input type="hidden" name="id" id="edit_task_id">
                
                <div class="form-field">
                    <label for="edit_task_res_id">Resident</label>
                    <select id="edit_task_res_id" name="resident_id" required style="width:100%;">
                        <option value="">-- Choose Resident --</option>
                        <?php foreach ($residents as $res): ?>
                            <option value="<?php echo $res['resident_id']; ?>">
                                <?php echo htmlspecialchars($res['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="edit_caregiver_id">Caregiver</label>
                    <select id="edit_caregiver_id" name="caregiver_id" style="width:100%;">
                        <option value="">-- Choose Caregiver --</option>
                        <?php foreach ($caregivers as $cg): ?>
                            <option value="<?php echo $cg['user_id']; ?>">
                                <?php echo htmlspecialchars($cg['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="edit_task_details">Task Details</label>
                    <input type="text" id="edit_task_details" name="task_details" required style="width:100%;">
                </div>

                <div class="form-field">
                    <label for="edit_schedule_time">Time</label>
                    <input type="time" id="edit_schedule_time" name="schedule_time" required style="width:100%;">
                </div>

                <button type="submit" class="btn-action btn-add">Update Task</button>
            </form>
        </div>
    </div>

    <script>
        function openAptModal(data) {
            document.getElementById('edit_apt_id').value = data.id;
            document.getElementById('edit_res_id').value = data.resident_id;
            document.getElementById('edit_doctor_name').value = data.doctor_name;
            document.getElementById('edit_appointment_datetime').value = data.appointment_datetime.replace(' ', 'T');
            document.getElementById('edit_purpose').value = data.purpose;
            document.getElementById('editAptModal').style.display = 'block';
        }

        function openTaskModal(data) {
            document.getElementById('edit_task_id').value = data.id;
            document.getElementById('edit_task_res_id').value = data.resident_id;
            document.getElementById('edit_caregiver_id').value = data.caregiver_id;
            document.getElementById('edit_task_details').value = data.task_details;
            document.getElementById('edit_schedule_time').value = data.schedule_time;
            document.getElementById('editTaskModal').style.display = 'block';
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