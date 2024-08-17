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

            $_SESSION['flash_message'] = "Login successful! Redirecting to your dashboard...";
            $_SESSION['flash_type'] = "success";

            // JavaScript for redirecting after a delay
            echo "<script>
                    setTimeout(function() {
                        window.location.href = '" . ($user['role'] == 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php') . "';
                    }, 3000); // 3 seconds
                  </script>";
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
            // Server settings for Mailtrap
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io';  // Mailtrap SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = '882bdda7cca6ad';  // Mailtrap SMTP username
            $mail->Password = 'bf79df64cf2651';  // Mailtrap SMTP password
            $mail->Port = 2525;  // Mailtrap SMTP port

            // Recipients
            $mail->setFrom('noreply@techrave.com', 'Techrave ICT Academy');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="styles/style.css" />

    <style>
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

        .form__input {
            width: 100%;
            border: 0px solid transparent;
            border-radius: 0;
            border-bottom: 1px solid #aaa;
            padding: 1em .5em .5em;
            padding-left: 2em;
            outline: none;
            margin: 1em auto;
            transition: all .5s ease;
        }

        .form__input:focus {
            border-bottom-color: #BD567F;
            box-shadow: 0 0 5px rgba(189, 87, 127, .4);
            border-radius: 4px;
        }

        .btn-form {
            transition: all .5s ease;
            width: 100%;
            padding: 10px;
            border-radius: 30px;
            color: #fff;
            font-weight: 600;
            background-color: #BC557D;
            border: none;
            margin-top: 1.5em;
        }

        .btn-form:hover,
        .btn-form:focus {
            background-color: #BD577F;
            color: #fff;
        }

        .nav-tabs .nav-link {
            color: #BD577F;
            font-weight: 600;
        }

        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: #BD577F;
            border-color: #BD577F;
        }

        .alert {
            color: #BD577F;
            background-color: #F8E3E8;
            border-color: #F1C4CF;
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
                font-size: 2.5rem !important;
            }

            .company_title {
                font-size: 1.25rem;
            }
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

        // Hide flash message after 3 seconds
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(function () {
                var alert = document.querySelector('.alert');
                if (alert) {
                    alert.style.display = 'none';
                }
            }, 3000); // 3 seconds
        });
    </script>
</head>

<body>
    <nav class="navbar bg-light">
        <div class="container">
            <a href="index.html" class="navbar-brand" style="color: #BD577F;">Techrave ICT Academy</a>
        </div>
    </nav>

    <div class="container">
        <div class="row main-content">
        <div class="col-md-4 text-center company__info">
                <span class="company__logo">
                    <i class="fas fa-graduation-cap fa-2x"></i>
                </span>
                <h4 class="company_title">Techrave ICT Academy</h4>
            </div>

            <div class="col-md-8 login_form">
                <div class="d-flex justify-content-end">
                    <a href="index.html" class="text-decoration-none text-muted">
                        <i class="fas fa-home fa-2x"></i>
                    </a>
                </div>

                <!-- Display Flash Message -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type']; ?>">
                        <?= $_SESSION['flash_message']; ?>
                        <?php unset($_SESSION['flash_message']); ?>
                        <?php unset($_SESSION['flash_type']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tabs for Login and Password Reset -->
                <ul class="nav nav-tabs mt-3 mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" onclick="showTab('loginTab')">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showTab('resetTab')" style="cursor: pointer;">Reset Password</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Login Form -->
                    <div id="loginTab" class="tab-pane active">
                        <form action="login.php" method="POST">
                            <div class="form-group">
                                <input type="email" placeholder="Email" class="form-control form__input" id="email"
                                    name="email" required>
                            </div>
                            <div class="form-group">
                                <input type="password" placeholder="Password" class="form-control form__input"
                                    id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-form" name="login">Login</button>

                            <div class="row my-3">
                                <p>Don't have an account? <a href="register.php" style="color: #BD577F;">Register Here</a></p>
                            </div>
                        </form>
                    </div>

                    <!-- Password Reset Form -->
                    <div id="resetTab" class="tab-pane">
                        <!-- Password Reset Request -->
                        <form action="login.php" method="POST">
                            <h5 class="mt-2">Request Password Reset</h5>
                            <div class="form-group">
                                <input type="email" placeholder="Email" class="form-control form__input"
                                    id="reset_email" name="reset_email" required>
                            </div>
                            <button type="submit" class="btn btn-form mb-5" style="background-color: #BD577F;" name="reset_request">Send
                                Reset Code</button>
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
                                    <input type="password" class="form-control" id="new_password" name="new_password"
                                        required>
                                </div>
                                <button type="submit" class="btn btn-form" name="reset_password" style="background-color: #BC557D;">Reset
                                    Password</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
</body>

</html>
