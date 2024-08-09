<?php
// user_dashboard.php

require 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id='$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Handle Update Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_name = $_POST['name'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Validate current password
    if (password_verify($current_password, $user['password'])) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update_sql = "UPDATE users SET name='$new_name', password='$hashed_password' WHERE id='$user_id'";

        if ($conn->query($update_sql) === TRUE) {
            $_SESSION['flash_message'] = "Profile updated successfully!";
            $_SESSION['flash_type'] = "success";
            $user['name'] = $new_name; // Update the user's name locally
        } else {
            $_SESSION['flash_message'] = "Error updating profile. Please try again.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "Current password is incorrect.";
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Techrave ICT Academy</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 60px;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .container {
            margin-top: 30px;
        }
        .section-header {
            margin-top: 50px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <a class="navbar-brand" href="#">Techrave ICT Academy</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#dashboard">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#courses">Courses</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#announcements">Announcements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#support">Support</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#profileModal">Update Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-danger text-white" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Flash Message -->
    <div class="container">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="flashMessage" class="alert alert-<?= $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['flash_message']; ?>
                <?php unset($_SESSION['flash_message']); ?>
                <?php unset($_SESSION['flash_type']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Dashboard Content -->
    <div class="container">
        <section id="dashboard" class="section-header">
            <h2>Welcome, <?= htmlspecialchars($user['name']); ?></h2>
            <p>Email: <?= htmlspecialchars($user['email']); ?></p>
        </section>

        <!-- Courses Section -->
        <section id="courses" class="section-header">
            <h3>Your Courses</h3>
            <p>Here you can find all the courses you are enrolled in:</p>
            <div class="card-deck">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Introduction to Cybersecurity</h5>
                        <p class="card-text">Learn the basics of cybersecurity and how to protect yourself online.</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Advanced Network Defense</h5>
                        <p class="card-text">Explore advanced techniques to secure networks from attacks.</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Cyber Threat Intelligence</h5>
                        <p class="card-text">Gain insights into cyber threats and how to mitigate them.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Announcements Section -->
        <section id="announcements" class="section-header">
            <h3>Announcements</h3>
            <p>Stay updated with the latest news and announcements:</p>
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-action">New course available: Ethical Hacking 101.</a>
                <a href="#" class="list-group-item list-group-item-action">Update: Scheduled maintenance on August 10th.</a>
                <a href="#" class="list-group-item list-group-item-action">Reminder: Submit assignments before the due date.</a>
            </div>
        </section>

        <!-- Support Section -->
        <section id="support" class="section-header">
            <h3>Support</h3>
            <p>If you need help, please contact our support team:</p>
            <ul class="list-group">
                <li class="list-group-item">Email: support@techraveictacademy.com</li>
                <li class="list-group-item">Phone: +123-456-7890</li>
            </ul>
        </section>
    </div>

    <!-- Profile Update Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">Update Profile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="user_dashboard.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="update_profile">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Flash message timeout
        $(document).ready(function() {
            setTimeout(function() {
                $('#flashMessage').alert('close');
            }, 3000);
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
