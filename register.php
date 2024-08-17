<?php
// register.php

require 'db.php';
session_start();

// Define an access code for admin registration
$accessCode = "1111"; // Change this to a secure code

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
        $code = $_POST['access_code'];
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <link rel="stylesheet" href="styles/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

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
            var countdown = 3; // Countdown timer in seconds
            var countdownElement = document.getElementById("countdown");
            var interval = setInterval(function () {
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

    <style>
        body {
            background-color: #f8f9fa;
        }

        .main-content {
            max-width: 900px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            margin: 5em auto;
            display: flex;
            overflow: hidden;
        }

        .company__info {
            background-color: #BD577F;
            padding: 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
        }

        .company__info h2 {
            font-size: 2.5em;
        }

        .company_title {
            font-size: 1.5em;
            margin-top: 15px;
        }

        .login_form {
            background-color: #fff;
            padding: 40px;
            flex: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .btn-form {
            width: 100%;
            padding: 10px;
            border-radius: 30px;
            background-color: #BC557D;
            color: #fff;
            border: none;
            font-weight: 600;
            margin-top: 1.5em;
            transition: background-color 0.3s, color 0.3s;
        }

        .btn-form:hover,
        .btn-form:focus {
            background-color: #BD577F;
            color: #fff;
        }

        .text-muted {
            font-size: 0.9rem;
        }

        .company__logo {
            font-size: 4rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #BD567F;
            box-shadow: 0 0 0 0.25rem rgba(189, 87, 127, 0.25);
        }

        @media screen and (max-width: 768px) {
            .main-content {
                flex-direction: column;
                align-items: stretch;
            }

            .company__info {
                border-radius: 15px 15px 0 0;
                padding: 20px;
            }

            .login_form {
                border-radius: 0 0 15px 15px;
                padding: 20px;
            }

            .company__logo {
                font-size: 2.5rem;
            }

            .company_title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a href="index.html" class="navbar-brand" style="color: #BD577F;">Techrave ICT Academy</a>
        </div>
    </nav>

    <div class="container">
        <div class="row main-content">
            <div class="col-md-4 text-center company__info">
                <span class="company__logo">
                    <i class="fas fa-graduation-cap"></i>
                </span>
                <h4 class="company_title">Techrave ICT Academy</h4>
            </div>

            <div class="col-md-8 login_form">
                <div class="d-flex justify-content-end">
                    <a href="index.html" class="text-decoration-none text-muted">
                        <i class="fas fa-home fa-2x" style="color: #BD577F;"></i>
                    </a>
                </div>

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

                <h2 class="text-center" style="color: #BD577F;">Registration</h2>

                <form action="register.php" method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                        <label for="name">Name</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" placeholder="name@example.com" name="email"
                            required>
                        <label for="email">Email address</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" placeholder="Password" name="password"
                            required>
                        <label for="password">Password</label>
                    </div>

                    <div class="form-floating mb-3">
                        <select class="form-select" id="role" name="role" onchange="toggleAccessCode()" required>
                            <option selected>Select role</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        <label for="role">Role</label>
                    </div>

                    <div class="form-floating mb-3" id="accessCodeField" style="display: none;">
                        <input type="text" class="form-control" id="access_code" placeholder="Access Code" name="access_code">
                        <label for="access_code">Access Code</label>
                    </div>

                    <button type="submit" class="btn btn-form">Register</button>
                </form>

                <p class="text-center text-muted my-3">Already have an account? <a href="login.php"
                        class="fw-bold text-body" style="color: #BD577F;"><u>Login here</u></a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"
        integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
        integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous">
    </script>
</body>

</html>
