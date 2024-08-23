<?php
// user_dashboard.php

require 'db.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Fetch the current user's password from the database
    $sql = $conn->prepare("SELECT password FROM users WHERE id=?");
    $sql->bind_param("i", $user_id);
    $sql->execute();
    $result = $sql->get_result();
    $user = $result->fetch_assoc();

    // Verify the current password
    if (password_verify($current_password, $user['password'])) {
        // Hash the new password
        $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the user's name and password in the database
        $update_sql = $conn->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
        $update_sql->bind_param("ssi", $name, $new_password_hashed, $user_id);

        if ($update_sql->execute()) {
            $_SESSION['flash_message'] = "Profile updated successfully!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error updating profile. Please try again.";
            $_SESSION['flash_type'] = "danger";
        }
        $update_sql->close();
    } else {
        $_SESSION['flash_message'] = "Current password is incorrect.";
        $_SESSION['flash_type'] = "danger";
    }

    header("Location: user_dashboard.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = $conn->prepare("SELECT * FROM users WHERE id=?");
$sql->bind_param("i", $user_id);
$sql->execute();
$result = $sql->get_result();
$user = $result->fetch_assoc();


// Fetch the last update time for announcements
$last_update_query = "SELECT MAX(last_update) as last_update FROM announcements";
$last_update_result = $conn->query($last_update_query);
$current_last_update = $last_update_result->fetch_assoc()['last_update'];

// Store the last known update in the session
if (!isset($_SESSION['last_known_update'])) {
    $_SESSION['last_known_update'] = $current_last_update;
}

// Handle AJAX request for checking last update
if (isset($_GET['check_last_update']) && $_GET['check_last_update'] == '1') {
    $new_announcements = ($current_last_update !== $_SESSION['last_known_update']);
    echo json_encode(['new_announcements' => $new_announcements]);

    // Update session to the latest timestamp
    if ($new_announcements) {
        $_SESSION['last_known_update'] = $current_last_update;
    }
    $conn->close();
    exit();
}

// Check for new announcements
$_SESSION['new_announcements'] = checkForNewAnnouncements($conn);

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = $conn->prepare("SELECT * FROM users WHERE id=?");
$sql->bind_param("i", $user_id);
$sql->execute();
$result = $sql->get_result();
$user = $result->fetch_assoc();

// Fetch announcements and check for new ones
$announcements_sql = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcements_result = $conn->query($announcements_sql);

$announcements = [];
$new_announcements = false;
while ($row = $announcements_result->fetch_assoc()) {
    $announcements[] = $row;
    if (isset($row['is_new']) && $row['is_new']) {
        $new_announcements = true; // Set to true if there's any new announcement
    }
}

// Mark announcements as read after displaying them (optional)
if ($new_announcements) {
    $update_sql = "UPDATE announcements SET is_new = 0 WHERE is_new = 1";
    $conn->query($update_sql);
}

function checkForNewAnnouncements($conn)
{
    // Query to check if there are any new announcements
    $sql = "SELECT COUNT(*) as new_count FROM announcements WHERE is_new = 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['new_count'] > 0; // Returns true if there are new announcements
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Techrave ICT Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

        .card-deck .card {
            min-width: 200px;
        }

        /* Animate */

        #dashboard {
            transition: transform 0.3s ease;
        }

        #dashboard:hover{
            transform: translateX(-10px);
        }

        .card-deck .card {
            transition: transform 0.3s ease;
        }

        .card-deck .card:hover{
            transform: translateY(-10px);
        }

        .announcement-item {
            transition: transform 0.3s ease;
        }

        .announcement-item:hover{
            transform: translateX(-10px);
        }

        .support-item {
            transition: transform 0.3s ease;
        }

        .support-item:hover {
            transform: translateY(-10px);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: #BD567F;">
        <a class="navbar-brand" href="#" style="font-weight: bold; color: #FFFFFF;">
            <i class="fas fa-graduation-cap"></i> Techrave Academy
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto" style="gap: 20px;">
                <li class="nav-item">
                    <a class="nav-link" href="#dashboard" style="color: #FFFFFF;">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#courses" style="color: #FFFFFF;">
                        <i class="fas fa-book"></i> Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#announcements" style="color: #FFFFFF; position: relative;">
                        <i class="fas fa-bullhorn"></i> Announcements
                        <?php if (isset($_SESSION['new_announcements']) && $_SESSION['new_announcements']): ?>
                            <sup
                                style="position: absolute; top: -5px; right: -10px; background-color: red; color: white; font-size: 0.8rem; padding: 2px 5px; border-radius: 50%;">New</sup>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#support" style="color: #FFFFFF;">
                        <i class="fas fa-headset"></i> Support
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#profileModal"
                        style="color: #FFFFFF;">
                        <i class="fas fa-user-edit"></i> Update Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn" href="logout.php" style="background-color: #BC547D; color: #FFFFFF;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Flash Message -->
    <div class="container">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="flashMessage" class="alert alert-<?= $_SESSION['flash_type']; ?> alert-dismissible fade show"
                role="alert">
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
        <section id="dashboard" class="section-header"
            style="background-color: #F9F9F9; padding: 40px 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);">
            <h2 style="color: #BD567F; font-weight: bold; font-size: 2.5rem; margin-bottom: 20px;">
                <i class="fas fa-user-circle"></i> Welcome, <?= htmlspecialchars($user['name']); ?>
            </h2>
            <p style="color: #333; font-size: 1.2rem;">
                <i class="fas fa-envelope"></i> Email: <span
                    style="color: #BD577F;"><?= htmlspecialchars($user['email']); ?></span>
            </p>
        </section>

        <!-- Courses Section -->
        <section id="courses" class="section-header" style="background-color: #F3F3F3; padding: 50px 20px;">
            <h3 style="color: #BD567F; font-weight: bold; font-size: 2rem; text-align: center; margin-bottom: 30px;">
                <i class="fas fa-book-open"></i> Your Courses
            </h3>
            <p style="color: #555; font-size: 1.2rem; text-align: center; margin-bottom: 40px;">
                Here you can find all the courses you are enrolled in:
            </p>
            <div class="card-deck" style="display: flex; gap: 20px;">
                <div class="card" style="border: none; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);">
                    <div class="card-body" style="background-color: #FFFFFF; border-radius: 10px; padding: 20px;">
                        <h5 class="card-title" style="color: #BC547D; font-weight: bold; font-size: 1.5rem;">
                            Introduction to Cybersecurity</h5>
                        <p class="card-text" style="color: #777; font-size: 1rem;">Learn the basics of cybersecurity and
                            how to protect yourself online.</p>
                        <a href="#" class="btn"
                            style="background-color: #BD577F; color: #FFFFFF; border-radius: 5px;">Start Course</a>
                    </div>
                </div>
                <div class="card" style="border: none; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);">
                    <div class="card-body" style="background-color: #FFFFFF; border-radius: 10px; padding: 20px;">
                        <h5 class="card-title" style="color: #BC547D; font-weight: bold; font-size: 1.5rem;">Advanced
                            Network Defense</h5>
                        <p class="card-text" style="color: #777; font-size: 1rem;">Explore advanced techniques to secure
                            networks from attacks.</p>
                        <a href="#" class="btn"
                            style="background-color: #BD577F; color: #FFFFFF; border-radius: 5px;">Continue Course</a>
                    </div>
                </div>
                <div class="card" style="border: none; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);">
                    <div class="card-body" style="background-color: #FFFFFF; border-radius: 10px; padding: 20px;">
                        <h5 class="card-title" style="color: #BC547D; font-weight: bold; font-size: 1.5rem;">Cyber
                            Threat Intelligence</h5>
                        <p class="card-text" style="color: #777; font-size: 1rem;">Gain insights into cyber threats and
                            how to mitigate them.</p>
                        <a href="#" class="btn"
                            style="background-color: #BD577F; color: #FFFFFF; border-radius: 5px;">View Details</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Announcements Section -->
        <section id="announcements" class="section-header"
            style="padding: 40px; border-radius: 8px; background-color: #f9f9f9;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="color: #333333; font-family: 'Helvetica Neue', sans-serif; font-weight: 700;">
                    <i class="fas fa-bullhorn" style="color: #BD577F; margin-right: 10px;"></i> Announcements
                </h3>
                <p style="color: #555555; font-family: 'Arial', sans-serif; font-size: 1.1rem;">
                    Stay updated with the latest news and announcements:
                </p>
            </div>
            <div class="list-group" style="max-width: 800px; margin: 0 auto;">
                <?php foreach ($announcements as $announcement): ?>
                    <a href="#" class="list-group-item announcement-item list-group-item-action"
                        style="background-color: #FFFFFF; border: 1px solid #E0E0E0; border-radius: 8px; margin-bottom: 15px; padding: 20px; color: #333333; position: relative; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: transform 0.2s ease;">
                        <div style="display: flex; align-items: center;">
                            <i class="fas fa-info-circle" style="color: #BD577F; margin-right: 15px;"></i>
                            <div>
                                <strong
                                    style="font-family: 'Helvetica Neue', sans-serif; font-size: 1.2rem; color: #333333;">
                                    <?= htmlspecialchars($announcement['title']); ?>
                                </strong>
                                <p
                                    style="font-family: 'Arial', sans-serif; font-size: 1rem; margin: 5px 0 0; color: #555555;">
                                    <?= htmlspecialchars($announcement['content']); ?>
                                </p>
                            </div>
                        </div>
                        <?php if (isset($announcement['is_new']) && $announcement['is_new']): ?>
                            <span
                                style="position: absolute; top: 10px; right: 10px; background-color: #BC547D; color: #FFFFFF; padding: 5px 10px; border-radius: 12px; font-size: 0.9rem; font-family: 'Helvetica Neue', sans-serif;">
                                New
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Support Section -->
        <section id="support" class="section-header" style="padding: 40px; border-radius: 8px;">
            <div style="text-align: center;">
                <h3
                    style="color: #333333; font-family: 'Helvetica Neue', sans-serif; font-weight: 700; margin-bottom: 20px;">
                    <i class="fas fa-headset" style="color: #BD577F; margin-right: 10px;"></i> Support
                </h3>
                <p style="color: #555555; font-family: 'Arial', sans-serif; font-size: 1.1rem; margin-bottom: 30px;">
                    Need help? Contact our support team:
                </p>
            </div>
            <ul class="list-group" style="max-width: 600px; margin: 0 auto;">
                <li class="list-group-item support-item"
                    style="background-color: #FFFFFF; border: 1px solid #E0E0E0; border-radius: 5px; margin-bottom: 10px; padding: 15px; display: flex; align-items: center;">
                    <i class="fas fa-envelope" style="color: #BD577F; margin-right: 15px;"></i>
                    <span>Email: <a href="mailto:support@techraveictacademy.com"
                            style="color: #333333; text-decoration: none;">support@techraveictacademy.com</a></span>
                </li>
                <li class="list-group-item support-item"
                    style="background-color: #FFFFFF; border: 1px solid #E0E0E0; border-radius: 5px; padding: 15px; display: flex; align-items: center;">
                    <i class="fas fa-phone" style="color: #BD577F; margin-right: 15px;"></i>
                    <span>Phone: <a href="tel:+1234567890"
                            style="color: #333333; text-decoration: none;">+123-456-7890</a></span>
                </li>
            </ul>
        </section>

    </div>

    <!-- Profile Update Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel"
        aria-hidden="true">
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
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password"
                                required>
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Flash message timeout
        $(document).ready(function () {
            setTimeout(function () {
                $('#flashMessage').alert('close');
            }, 3000);
        });

        $(document).ready(function () {
            function checkForNewAnnouncements() {
                $.ajax({
                    url: 'user_dashboard.php?check_last_update=1',
                    method: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        if (data.new_announcements) {
                            window.location.reload(); // Reload the page if there are new announcements
                        }
                    }
                });
            }

            setInterval(checkForNewAnnouncements, 10000); // Check every 10 seconds
        });
    </script>

</body>

</html>

<?php
$conn->close();
?>