<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/db.php';

// Check DB connection (OOP style)
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Modern null-coalescing check
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit();
    }

    // OOP Prepared statement
    $stmt = $conn->prepare("SELECT user_id, username, password, role, full_name FROM users WHERE username = ? LIMIT 1");
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameter ('s' = string)
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    // Fetch result set directly
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Direct password comparison
        if ($password === $row['password']) {
            $_SESSION['user_id']   = $row['user_id'];
            $_SESSION['username']  = $row['username'];
            $_SESSION['role']      = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];

            $stmt->close();

            switch ($row['role']) {
                case 'admin':
                    header("Location: /elderly_care/pages/admin.php");
                    break;
                case 'caregiver':
                    header("Location: /elderly_care/pages/caregiver.php");
                    break;
                case 'family':
                    header("Location: /elderly_care/pages/family.php");
                    break;
                default:
                    header("Location: login.php?error=invalid_role");
            }
            exit();
        } else {
            $stmt->close();
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } else {
        $stmt->close();
        header("Location: login.php?error=invalid_credentials");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>