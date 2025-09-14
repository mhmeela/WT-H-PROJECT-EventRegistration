<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
<style>
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }
</style>
</head>
<body>
    <div style="text-align: right; padding: 10px;">
        <button class="button logout" onclick="location.href='logout.php'">Logout</button>
    </div>
    
    <h1>Welcome, Admin <?php echo $_SESSION['username']; ?></h1>
    
    <!-- Common Features -->
    <h2>Common Features</h2>
    <button class="button" onclick="location.href='change_password.php'">Change Password</button>
    <button class="button" onclick="location.href='profile.php'">Manage Profile</button>

    <!-- Admin Specific Features -->
    <h2>Admin Features</h2>
    <button class="button" onclick="location.href='manage_events.php'">Manage All Events</button>
    <h2>User Management</h2>
<button class="button" onclick="location.href='view_users.php'">View All Users</button>
<button class="button" onclick="location.href='update_users.php'">Update Users</button>
<button class="button" onclick="location.href='delete_users.php'">Delete Users</button>
 <button class="button" onclick="location.href='view_statistics.php'">View Statistics</button>
</body>
</html>
