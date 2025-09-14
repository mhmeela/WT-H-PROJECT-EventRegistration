<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle delete action
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    $sql = "DELETE FROM users WHERE id = $user_id";
    
    if ($conn->query($sql) === TRUE) {
        $message = "User deleted successfully";
    } else {
        $error = "Error deleting user: " . $conn->error;
    }
}

// Get all users
$sql = "SELECT id, username, email, user_type, first_name, last_name FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Users</title>
    <style> body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }</style>
</head>
<body>
    <a href="admin_dashboard.php">Back to Dashboard</a>
    <h1>Delete Users</h1>
    
    <?php if (isset($message)) echo "<p>$message</p>"; ?>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Name</th>
            <th>User Type</th>
            <th>Action</th>
        </tr>
        <?php while($user = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $user['id']; ?></td>
            <td><?php echo $user['username']; ?></td>
            <td><?php echo $user['email']; ?></td>
            <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
            <td><?php echo $user['user_type']; ?></td>
            <td>
                <a href="delete_users.php?delete_user=<?php echo $user['id']; ?>" 
                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
<?php $conn->close(); ?>