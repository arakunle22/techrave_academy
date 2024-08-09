<?php
// register.php

require 'db.php';
session_start();

// Define an access code for admin registration
$accessCode = "1234"; // Change this to a secure code

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    // Check if the email already exists in the database
    $checkEmailSql = "SELECT * FROM users WHERE email='$email'";
    $emailResult = $conn->query($checkEmailSql);

    if ($emailResult->num_rows > 0) {
        $_SESSION['flash_message'] = "Email already in use! Please choose another.";
        $_SESSION['flash_type'] = "warning"; // Set the flash message type
        header("Location: register.php");
        exit();
    }

    if ($role === 'admin') {
        $code = $_POST['code'];
        if ($code !== $accessCode) {
            $_SESSION['flash_message'] = "Invalid access code!";
            $_SESSION['flash_type'] = "warning";
            header("Location: register.php");
            exit();
        }
    }

    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['flash_message'] = "Registration successful! You will be redirected to login in a moment.";
        $_SESSION['flash_type'] = "success"; // Success message type
        $_SESSION['redirect_to_login'] = true; // Set a flag for redirection
    } else {
        $_SESSION['flash_message'] = "Error: Could not complete registration. Please try again.";
        $_SESSION['flash_type'] = "danger";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration | Techrave ICT Academy</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toggleAccessCode() {
            var role = document.getElementById("role").value;
            var accessCodeField = document.getElementById("accessCodeField");
            if (role === "admin") {
                accessCodeField.style.display = "block";
            } else {
                accessCodeField.style.display = "none";
            }
        }

        // Function to handle redirection with countdown
        function redirectToLoginWithDelay() {
            var countdown = 5; // Countdown timer in seconds
            var countdownElement = document.getElementById("countdown");
            var interval = setInterval(function() {
                countdown--;
                countdownElement.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(interval);
                    window.location.href = "login.php"; // Redirect to login page
                }
            }, 1000); // Interval set to 1000 milliseconds (1 second)
        }

        // Check if redirection flag is set
        <?php if (isset($_SESSION['redirect_to_login']) && $_SESSION['redirect_to_login'] === true): ?>
            window.onload = redirectToLoginWithDelay; // Start countdown on page load
        <?php endif; ?>
    </script>
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Registration</h2>

        <!-- Display Flash Message -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type']; ?>">
                <?= $_SESSION['flash_message']; ?>
                <?php unset($_SESSION['flash_message']); ?>
                <?php unset($_SESSION['flash_type']); ?>
            </div>

            <!-- Display countdown only if redirect flag is set -->
            <?php if (isset($_SESSION['redirect_to_login']) && $_SESSION['redirect_to_login'] === true): ?>
                <p>You will be redirected to the login page in <span id="countdown">5</span> seconds...</p>
                <?php unset($_SESSION['redirect_to_login']); ?> <!-- Clear redirect flag -->
            <?php endif; ?>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select class="form-control" id="role" name="role" onchange="toggleAccessCode()" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group" id="accessCodeField" style="display: none;">
                <label for="code">Access Code</label>
                <input type="text" class="form-control" id="code" name="code">
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    </div>
</body>
</html>
