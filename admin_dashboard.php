<?php
// Include the existing PHP code here (lines 3-127 from the original file)
// This includes session management, database connections, and handling form submissions

// Existing PHP code starts here
require 'db.php';
session_start();

// Check if the logged-in user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Announcement Creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_announcement'])) {
    $title = $_POST['announcement_title'];
    $content = $_POST['announcement_content'];

    $stmt = $conn->prepare("INSERT INTO announcements (title, content, is_new, last_update) VALUES (?, ?, 1, NOW())");
    $stmt->bind_param("ss", $title, $content);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Announcement created successfully!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error creating announcement. Please try again.";
        $_SESSION['flash_type'] = "danger";
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Announcement Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_announcement'])) {
    $announcement_id = $_POST['announcement_id'];
    $title = $_POST['announcement_title'];
    $content = $_POST['announcement_content'];

    $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, is_new = 1, last_update = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $announcement_id);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Announcement updated successfully!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error updating announcement. Please try again.";
        $_SESSION['flash_type'] = "danger";
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Announcement Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_announcement'])) {
    $announcement_id = $_POST['announcement_id'];

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $announcement_id);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Announcement deleted successfully!";
        $_SESSION['flash_type'] = "success";

        // Update the last_update timestamp to notify users of deletions
        $conn->query("UPDATE announcements SET last_update = NOW() ORDER BY last_update DESC LIMIT 1");
    } else {
        $_SESSION['flash_message'] = "Error deleting announcement. Please try again.";
        $_SESSION['flash_type'] = "danger";
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle user deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "User deleted successfully!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error deleting user. Please try again.";
        $_SESSION['flash_type'] = "danger";
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle admin detail update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_admin'])) {
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_POST['admin_name'];
    $new_password = !empty($_POST['admin_password']) ? password_hash($_POST['admin_password'], PASSWORD_BCRYPT) : null;

    if ($new_password) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $admin_name, $new_password, $admin_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $admin_name, $admin_id);
    }

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Admin details updated successfully!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error updating admin details. Please try again.";
        $_SESSION['flash_type'] = "danger";
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle user editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $user_id = $_POST['edit_user_id'];
    $user_name = $_POST['edit_user_name'];
    $user_email = $_POST['edit_user_email'];
    $user_role = $_POST['edit_user_role'];

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $user_name, $user_email, $user_role, $user_id);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "User details updated successfully!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error updating user details. Please try again.";
        $_SESSION['flash_type'] = "danger";
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Fetch all announcements
$announcements = [];
$announcement_sql = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcement_result = $conn->query($announcement_sql);
if ($announcement_result) {
    while ($row = $announcement_result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Fetch user statistics
$total_users = $conn->query("SELECT COUNT(*) AS total_users FROM users")->fetch_assoc()['total_users'];
$active_users = $conn->query("SELECT COUNT(*) AS active_users FROM users WHERE status='active'")->fetch_assoc()['active_users'];
$inactive_users = $conn->query("SELECT COUNT(*) AS inactive_users FROM users WHERE status='inactive'")->fetch_assoc()['inactive_users'];
$new_registrations_this_month = $conn->query("SELECT COUNT(*) AS new_registrations FROM users WHERE MONTH(registration_date) = MONTH(CURDATE()) AND YEAR(registration_date) = YEAR(CURDATE())")->fetch_assoc()['new_registrations'];
$new_registrations_today = $conn->query("SELECT COUNT(*) AS new_registrations_today FROM users WHERE DATE(registration_date) = CURDATE()")->fetch_assoc()['new_registrations_today'];

// Fetch all users for display with search functionality
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $search_query = " WHERE name LIKE ? OR email LIKE ?";
}
$sql = "SELECT * FROM users" . $search_query;
$stmt = $conn->prepare($sql);

if ($search_query) {
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();
// Existing PHP code ends here
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Techrave ICT Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --background-color: #ecf0f1;
            --text-color: #34495e;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }
        .navbar {
            background-color: var(--primary-color);
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .table {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background-color: var(--secondary-color);
            color: white;
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Techrave ICT Academy</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#updateAdminModal">
                            <i class="fas fa-user-cog"></i> Update Admin Details
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#announcements">
                            <i class="fas fa-bullhorn"></i> Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        <?php endif; ?>

        <h1 class="text-center mb-4">Admin Dashboard</h1>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Total Users</h5>
                        <p class="card-text display-4"><?= $total_users; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-check"></i> Active Users</h5>
                        <p class="card-text display-4"><?= $active_users; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-clock"></i> Inactive Users</h5>
                        <p class="card-text display-4"><?= $inactive_users; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt"></i> New Registrations This Month</h5>
                        <p class="card-text display-4"><?= $new_registrations_this_month; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-day"></i> New Registrations Today</h5>
                        <p class="card-text display-4"><?= $new_registrations_today; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mb-3">User Management</h2>
        <div class="card mb-4">
            <div class="card-body">
                <form class="mb-3" method="GET" action="admin_dashboard.php">
                    <div class="input-group">
                        <input type="search" class="form-control" placeholder="Search users by name or email" name="search" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']); ?></td>
                                    <td><?= htmlspecialchars($row['email']); ?></td>
                                    <td><?= htmlspecialchars($row['role']); ?></td>
                                    <td><span class="badge bg-<?= $row['status'] == 'active' ? 'success' : 'warning' ?>"><?= htmlspecialchars($row['status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $row['id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= $row['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash-alt"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit User Modal -->
                                <div class="modal fade" id="editUserModal<?= $row['id']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?= $row['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editUserModalLabel<?= $row['id']; ?>">Edit User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="admin_dashboard.php">
                                                <div class="modal-body">
                                                    <input type="hidden" name="edit_user_id" value="<?= $row['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="editUserName<?= $row['id']; ?>" class="form-label">Name</label>
                                                        <input type="text" class="form-control" id="editUserName<?= $row['id']; ?>" name="edit_user_name" value="<?= htmlspecialchars($row['name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="editUserEmail<?= $row['id']; ?>" class="form-label">Email</label>
                                                        <input type="email" class="form-control" id="editUserEmail<?= $row['id']; ?>" name="edit_user_email" value="<?= htmlspecialchars($row['email']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="editUserRole<?= $row['id']; ?>" class="form-label">Role</label>
                                                        <select class="form-select" id="editUserRole<?= $row['id']; ?>" name="edit_user_role">
                                                            <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            <option value="user" <?= $row['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="edit_user" class="btn btn-primary">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <h2 id="announcements" class="mb-3">Announcements</h2>
        <div class="card mb-4">
            <div class="card-body">
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal"><i class="fas fa-plus"></i> Create New Announcement</button>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Content</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $announcement): ?>
                                <tr>
                                    <td><?= htmlspecialchars($announcement['title']); ?></td>
                                    <td><?= htmlspecialchars($announcement['content']); ?></td>
                                    <td><?= $announcement['created_at']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editAnnouncementModal<?= $announcement['id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="announcement_id" value="<?= $announcement['id']; ?>">
                                            <button type="submit" name="delete_announcement" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this announcement?');"><i class="fas fa-trash-alt"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Announcement Modal -->
                                <div class="modal fade" id="editAnnouncementModal<?= $announcement['id']; ?>" tabindex="-1" aria-labelledby="editAnnouncementModalLabel<?= $announcement['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editAnnouncementModalLabel<?= $announcement['id']; ?>">Edit Announcement</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="admin_dashboard.php">
                                                <div class="modal-body">
                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="announcementTitle<?= $announcement['id']; ?>" class="form-label">Title</label>
                                                        <input type="text" class="form-control" id="announcementTitle<?= $announcement['id']; ?>" name="announcement_title" value="<?= htmlspecialchars($announcement['title']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="announcementContent<?= $announcement['id']; ?>" class="form-label">Content</label>
                                                        <textarea class="form-control" id="announcementContent<?= $announcement['id']; ?>" name="announcement_content" rows="4" required><?= htmlspecialchars($announcement['content']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_announcement" class="btn btn-primary">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Announcement Modal -->
    <div class="modal fade" id="createAnnouncementModal" tabindex="-1" aria-labelledby="createAnnouncementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAnnouncementModalLabel">Create Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="admin_dashboard.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="announcementTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="announcementTitle" name="announcement_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="announcementContent" class="form-label">Content</label>
                            <textarea class="form-control" id="announcementContent" name="announcement_content" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="create_announcement" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Admin Modal -->
    <div class="modal fade" id="updateAdminModal" tabindex="-1" aria-labelledby="updateAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateAdminModalLabel">Update Admin Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="admin_dashboard.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="adminName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="adminName" name="admin_name" value="<?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">New Password (leave blank to keep current password)</label>
                            <input type="password" class="form-control" id="adminPassword" name="admin_password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_admin" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Flash message timeout
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var flashMessage = document.querySelector('.alert');
                if (flashMessage) {
                    var bsAlert = new bootstrap.Alert(flashMessage);
                    bsAlert.close();
                }
            }, 5000);
        });
    </script>
</body>
</html>