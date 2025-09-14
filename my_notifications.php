<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get user's notifications by joining with users table
$username = $_SESSION['username'];
$notifications = $conn->query("
    SELECT n.* 
    FROM notifications n 
    JOIN users u ON n.user_id = u.id 
    WHERE u.username = '$username' 
    ORDER BY n.notif_id DESC
");


<!DOCTYPE html>
<html>
<head>
    <title>My Notifications</title>
    <style>
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }
        .container { max-width: 800px; margin: 0 auto; }
        .notification { 
            border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; 
            border-radius: 5px; background: #f9f9f9; 
        }
        .notification h3 { margin-top: 0; color: #333; }
        .notification .date { color: #777; font-size: 0.9em; }
    </style>
</head>
<body>
    <a href="user_dashboard.php">← Back to Dashboard</a>
    <div class="container">
        <h1>My Notifications</h1>
        
        <?php if ($notifications->num_rows > 0): ?>
            <?php while ($notif = $notifications->fetch_assoc()): ?>
                <div class="notification">
                    <h3><?php echo htmlspecialchars($notif['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($notif['message'])); ?></p>
                    <?php if (isset($notif['created_at'])): ?>
                        <div class="date">Received: <?php echo $notif['created_at']; ?></div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You don't have any notifications yet.</p>
            <p>Notifications will appear here when event creators send updates about events you've registered for.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>