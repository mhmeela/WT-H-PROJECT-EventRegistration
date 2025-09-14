<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'creator') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Creator Dashboard</title>
    <style>
     body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }
        .button { 
            padding: 10px 15px; 
            margin: 5px; 
            background: #4CAF50; 
            color: white; 
            border: none; 
            cursor: pointer; 
            border-radius: 4px; 
        }
        .button:hover { background: #45a049; }
        .logout { background: #f44336; }
        .logout:hover { background: #da190b; }
    </style>
</head>
<body>
    <div style="text-align: right; padding: 10px;">
        <button class="button logout" onclick="location.href='logout.php'">Logout</button>
    </div>
    
    <h1>Welcome, Event Creator <?php echo $_SESSION['username']; ?></h1>
    
    <!-- Common Features -->
    <h2>Common Features</h2>
    <button class="button" onclick="location.href='change_password.php'">Change Password</button>
    <button class="button" onclick="location.href='profile.php'">Manage Profile</button>

    <!-- Event Creator Specific Features -->
    <h2>Event Creator Features</h2>
    <button class="button" onclick="location.href='create_event.php'">Create New Event</button>
    <button class="button" onclick="location.href='manage_my_events.php'">Manage My Events</button>
    <button class="button" onclick="location.href='send_updates.php'">Send Event Updates</button>
</body>
</html>
