<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        ul { list-style-type: none; padding: 0; }
        li { border: 1px solid #000; padding: 5px; margin: 5px 0; }
    </style>
</head>
<body>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
<p><a href="logout.php">Logout</a></p>

<h3>Upcoming Events</h3>
<ul>
    <li>Tech Workshop</li>
    <li>Business Seminar</li>
    <li>Anime Cosplay</li>
    <li>Career Conference</li>
    <li>General Meetup</li>
</ul>

</body>
</html>
