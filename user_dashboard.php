<?php
require 'db.php';
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Add validation and existing profile update logic here
}

// Fetch user data
function fetchUserData($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Fetch announcements
function fetchAnnouncements($conn) {
    $sql = "SELECT * FROM announcements ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $announcements = [];
    $new_announcements = false;

    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
        if (isset($row['is_new']) && $row['is_new']) {
            $new_announcements = true;
        }
    }

    if ($new_announcements) {
        $conn->query("UPDATE announcements SET is_new = 0 WHERE is_new = 1");
    }

    return $announcements;
}

// Check for new announcements
function hasNewAnnouncements($conn) {
    $sql = "SELECT COUNT(*) as new_count FROM announcements WHERE is_new = 1";
    $result = $conn->query($sql);
    return $result && $result->fetch_assoc()['new_count'] > 0;
}

// Fetch data
$user_id = $_SESSION['user_id'];
$user = fetchUserData($conn, $user_id);
$announcements = fetchAnnouncements($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Techrave ICT Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #fff; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .section-header { margin: 2rem 0 1rem; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .card:hover { transform: translateY(-5px); }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
        <a class="navbar-brand" href="#"> <img src="images/Techrave - transparent3.png" alt="About Techrave ICT Academy" class="img-fluid rounded " width="200" height="350"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="#announcements">Announcements</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="flashMessage" class="alert alert-<?= $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['flash_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <section id="dashboard" class="section-header">
    <div class="card shadow-sm p-4 mb-4 bg-white rounded">
        <div class="card-body text-center">
            <h2 class="card-title display-6">Welcome, <?= htmlspecialchars($user['name']); ?>!</h2>
            <p class="card-text text-muted">Email: <strong><?= htmlspecialchars($user['email']); ?></strong></p>
            <div class="mt-3 d-flex flex-column flex-md-row justify-content-center align-items-center">
                <a href="#courses" class="btn btn-outline-primary mb-2 mb-md-0 me-0 me-md-2">
                    <i class="fas fa-book me-1"></i> View Courses
                </a>
                <a href="#profileModal" class="btn btn-outline-success" data-bs-toggle="modal">
                    <i class="fas fa-user-edit me-1"></i> Update Profile
                </a>
            </div>
        </div>
    </div>
</section>




        <section id="announcements" class="section-header">
    <h3>Announcements</h3>
    <?php if (empty($announcements)): ?>
        <div class="alert alert-info" role="alert">
            No announcements yet.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $announcement): ?>
                        <tr>
                            <td><?= htmlspecialchars($announcement['title']); ?></td>
                            <td><?= htmlspecialchars($announcement['content']); ?></td>
                            <td><?= htmlspecialchars(date("F d, Y", strtotime($announcement['created_at']))); ?></td>
                            <td>
                                <?php if ($announcement['is_new']): ?>
                                    <span class="badge bg-primary">New</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Read</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<footer id="support" class="bg-light py-4 border-top">
    <div class="container text-center">
        <h4 class="mb-3">Support</h4>
        <p class="mb-1">
            <i class="fas fa-envelope me-2 text-primary"></i>
            <strong>Email:</strong> <a href="mailto:support@techrave.com" class="text-dark text-decoration-none">support@techrave.com</a>
        </p>
        <p class="mb-0">
            <i class="fas fa-phone me-2 text-success"></i>
            <strong>Phone:</strong> <a href="tel:+1234567890" class="text-dark text-decoration-none">+123-456-7890</a>
        </p>
    </div>
</footer>



    </div>

    <!-- Profile Update Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="user_dashboard.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">Update Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="update_profile">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => document.querySelector('#flashMessage')?.remove(), 3000);
        });

        // Check for new announcements every 10 seconds
        setInterval(() => {
            fetch('user_dashboard.php?check_last_update=1')
                .then(response => response.json())
                .then(data => { if (data.new_announcements) location.reload(); });
        }, 10000);
    </script>
</body>
</html>
