<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

// Redirect based on user type
switch ($_SESSION['user_type']) {
    case 'admin':
        header("Location: admin_dashboard.php");
        break;
    case 'creator':
        header("Location: creator_dashboard.php");
        break;
    case 'normal':
        header("Location: user_dashboard.php");
        break;
    default:
        header("Location: login.php");
        break;
}
exit();
?>
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
            <link rel="stylesheet" type="text/css" href="dashboard.css">

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
