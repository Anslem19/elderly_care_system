<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Elderly Care System</title>
    <link rel="stylesheet" href="/elderly_care/assets/css/style.css">
</head>
<body>

    <h1>Online Elderly Care Management System</h1>

    <div class="login-card">
        <?php if (isset($_GET['error'])): ?>
            <p style="color: red; text-align: center;">
                <?php 
                    $error = htmlspecialchars($_GET['error']);
                    if ($error === 'invalid_credentials') echo "Invalid username or password!";
                    elseif ($error === 'empty_fields') echo "Please fill in all fields!";
                    else echo "An error occurred. Please try again.";
                ?>
            </p>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">
            </div>

            <input type="submit" value="Login">
        </form>

        <div class="credential-note" style="margin-top: 15px; text-align: center;">
            Default Access: <strong>uoc</strong> / <strong>uoc</strong>
        </div>
    </div>

</body>
</html>