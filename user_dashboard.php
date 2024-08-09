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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Techrave ICT Academy</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Welcome, <?= htmlspecialchars($user['name']); ?></h2>
        <p>Email: <?= htmlspecialchars($user['email']); ?></p>
        <p>Your current courses:</p>
        <ul>
            <li>Introduction to Cybersecurity</li>
            <li>Advanced Network Defense</li>
            <li>Cyber Threat Intelligence</li>
        </ul>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>
