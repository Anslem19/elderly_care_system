<?php
// 1. Initialize session memory
session_start();

// 2. Prevent browser back-button caching (Security layer)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache");                                  // HTTP 1.0
header("Expires: 0");                                         // Proxies

// 3. Security Check: Protect page from direct URL access and restrict to Admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /elderly_care/login.php");
    exit();
}

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$role = $_SESSION['role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Home</title>
    <link rel="stylesheet" href="/elderly_care/assets/css/style.css">
    
    <!-- Reload page if restored from browser back-button cache -->
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
        <h2>Administrative Control Center</h2>
        <p style="color: #7f8c8d; margin-bottom: 30px;">
            Welcome back, <strong><?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></strong>. Select a management module below to proceed.
        </p>

        <!-- 3-COLUMN NAVIGATION CARD MATRIX -->
        <div class="func-grid">
            
            <!-- MODULE 1 -->
            <div class="panel-card" style="text-align: center; padding: 30px 20px;">
                <div style="font-size: 40px; margin-bottom: 15px;">📋</div>
                <h3>Records Management</h3>
                <p style="font-size: 13px; color: #7f8c8d; min-height: 40px;">Register new elderly residents, manage caregiver staff profiles, and view active directories.</p>
                <a href="/elderly_care/pages/admin_records.php" class="btn-action btn-add" style="display: inline-block; text-decoration: none; margin-top: 15px; width: 80%;">Open Records</a>
            </div>

            <!-- MODULE 2 -->
            <div class="panel-card" style="text-align: center; padding: 30px 20px;">
                <div style="font-size: 40px; margin-bottom: 15px;">⏳</div>
                <h3>Care & Medical Scheduling</h3>
                <p style="font-size: 13px; color: #7f8c8d; min-height: 40px;">Allocate daily medication tasks, assign caregiver duties, and track clinical doctor appointments.</p>
                <a href="/elderly_care/pages/admin_schedules.php" class="btn-action btn-edit" style="display: inline-block; text-decoration: none; margin-top: 15px; width: 80%; background-color: #3498db;">Open Schedules</a>
            </div>

            <!-- MODULE 3 -->
            <div class="panel-card" style="text-align: center; padding: 30px 20px;">
                <div style="font-size: 40px; margin-bottom: 15px;">🔔</div>
                <h3>Family Requests</h3>
                <p style="font-size: 13px; color: #7f8c8d; min-height: 40px;">Review, approve, or decline incoming visit requests and custom care notes from relatives.</p>
                <a href="/elderly_care/pages/admin_requests.php" class="btn-action btn-delete" style="display: inline-block; text-decoration: none; margin-top: 15px; width: 80%; background-color: #e67e22;">Review Requests</a>
            </div>

        </div>

        <!-- QUICK SYSTEM SUMMARY -->
        <div class="panel-card" style="margin-top: 30px;">
            <h3>Facility Overview Snapshot</h3>
            <div style="display: flex; justify-content: space-around; padding: 15px 0; text-align: center;">
                <div>
                    <h2 style="margin: 0; color: #2c3e50;">24</h2>
                    <small style="color: #7f8c8d;">Active Residents</small>
                </div>
                <div style="border-left: 1px solid #e0e0e0;"></div>
                <div>
                    <h2 style="margin: 0; color: #2c3e50;">8</h2>
                    <small style="color: #7f8c8d;">Staff On Duty</small>
                </div>
                <div style="border-left: 1px solid #e0e0e0;"></div>
                <div>
                    <h2 style="margin: 0; color: #f39c12;">1</h2>
                    <small style="color: #7f8c8d;">Pending Request</small>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Pass real PHP session status to navbar.js
        window.APP_USER = {
            isLoggedIn: true,
            name: "<?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>",
            role: "<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"
        };
    </script>

    <!-- Includes JS Navbar injection -->
    <script src="/elderly_care/assets/js/navbar.js"></script>

</body>
</html>