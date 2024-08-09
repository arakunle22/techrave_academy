<?php
// login.php

require 'db.php';
session_start();

// Include PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Autoload PHPMailer classes
require 'vendor/autoload.php';

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            $_SESSION['flash_message'] = "Login successful!";
            $_SESSION['flash_type'] = "success";

            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            $_SESSION['flash_message'] = "Incorrect password. Please try again.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "Email not found. Please register first.";
        $_SESSION['flash_type'] = "warning";
    }
}

// Handle Password Reset Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_request'])) {
    $email = $_POST['reset_email'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $reset_code = uniqid(); // Generate a unique reset code
        $_SESSION['reset_code'] = $reset_code;
        $_SESSION['reset_email'] = $email;

        // Send the reset code via email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  // Set the SMTP server to send through
            $mail->SMTPAuth = true;
            $mail->Username = 'info.peacedev@gmail.com';  // SMTP username
            $mail->Password = '@Peacecode22';     // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;  // TCP port to connect to

            // Recipients
            $mail->setFrom('info.peacedev@gmail.com', 'Techrave ICT Academy');
            $mail->addAddress($email);  // Add a recipient

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Dear user,<br><br>Your password reset code is: <strong>$reset_code</strong>.<br>Please use this code to reset your password.<br><br>Thank you,<br>Techrave ICT Academy";

            $mail->send();

            $_SESSION['flash_message'] = "A reset code has been sent to your email.";
            $_SESSION['flash_type'] = "info";

        } catch (Exception $e) {
            $_SESSION['flash_message'] = "Failed to send reset code. Please try again later.";
            $_SESSION['flash_type'] = "danger";
        }

    } else {
        $_SESSION['flash_message'] = "Email not found. Please check your email address.";
        $_SESSION['flash_type'] = "warning";
    }
}

// Handle Password Reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $email = $_SESSION['reset_email'];
    $reset_code = $_POST['reset_code'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

    if ($reset_code === $_SESSION['reset_code']) {
        $sql = "UPDATE users SET password='$new_password' WHERE email='$email'";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['flash_message'] = "Password reset successful! Please login.";
            $_SESSION['flash_type'] = "success";

            // Clear reset session variables
            unset($_SESSION['reset_code']);
            unset($_SESSION['reset_email']);

            header("Location: login.php");
            exit();
        } else {
            $_SESSION['flash_message'] = "Error updating password. Please try again.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "Invalid reset code. Please check your email.";
        $_SESSION['flash_type'] = "danger";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Techrave ICT Academy</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tab-content > .tab-pane {
            display: none;
        }
        .tab-content > .active {
            display: block;
        }
    </style>
    <script>
        function showTab(tabName) {
            var i;
            var x = document.getElementsByClassName("tab-pane");
            for (i = 0; i < x.length; i++) {
                x[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
        }
    </script>
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Login</h2>

        <!-- Display Flash Message -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type']; ?>">
                <?= $_SESSION['flash_message']; ?>
                <?php unset($_SESSION['flash_message']); ?>
                <?php unset($_SESSION['flash_type']); ?>
            </div>
        <?php endif; ?>

        <!-- Tabs for Login and Password Reset -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="#" onclick="showTab('loginTab')">Login</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showTab('resetTab')">Reset Password</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Login Form -->
            <div id="loginTab" class="tab-pane active">
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="login">Login</button>
                </form>
            </div>

            <!-- Password Reset Form -->
            <div id="resetTab" class="tab-pane">
                <!-- Password Reset Request -->
                <form action="login.php" method="POST">
                    <h4>Request Password Reset</h4>
                    <div class="form-group">
                        <label for="reset_email">Email address</label>
                        <input type="email" class="form-control" id="reset_email" name="reset_email" required>
                    </div>
                    <button type="submit" class="btn btn-warning" name="reset_request">Send Reset Code</button>
                </form>

                <!-- Password Reset Verification -->
                <?php if (isset($_SESSION['reset_code'])): ?>
                <form action="login.php" method="POST" class="mt-3">
                    <h4>Reset Password</h4>
                    <div class="form-group">
                        <label for="reset_code">Access Code</label>
                        <input type="text" class="form-control" id="reset_code" name="reset_code" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <button type="submit" class="btn btn-success" name="reset_password">Reset Password</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
