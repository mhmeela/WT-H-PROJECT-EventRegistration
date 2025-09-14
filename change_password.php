<?php
session_start();
// Check if user is logged in using username session variable
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_SESSION['username'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Get current password from database using username
        $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($stored_password);
        $stmt->fetch();
        $stmt->close();
        
        // Debug output
        echo "<!-- DEBUG: Username: $username -->";
        echo "<!-- DEBUG: Input password: $current_password -->";
        echo "<!-- DEBUG: Stored password: $stored_password -->";
        echo "<!-- DEBUG: Comparison: " . ($current_password === $stored_password ? 'MATCH' : 'NO MATCH') . " -->";
        
        // Verify current password (plain text comparison)
        if ($current_password === $stored_password) {
            // Store new password as plain text
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $update_stmt->bind_param("ss", $new_password, $username);
            
            if ($update_stmt->execute()) {
                $message = "Password changed successfully!";
            } else {
                $error = "Error updating password: " . $conn->error;
            }
            $update_stmt->close();
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
 body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }        .container { max-width: 400px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .message { color: green; margin-bottom: 15px; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php
// Determine which dashboard to link to based on user type
$dashboard_link = "dashboard.php"; // Default fallback

if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'admin':
            $dashboard_link = "admin_dashboard.php";
            break;
        case 'creator':
            $dashboard_link = "creator_dashboard.php";
            break;
        case 'normal':
            $dashboard_link = "user_dashboard.php";
            break;
        default:
            $dashboard_link = "dashboard.php";
    }
}
?>
<a href="<?php echo $dashboard_link; ?>">← Back to Dashboard</a>
    <div class="container">
        <h1>Change Password</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
            </div>
            
            <button type="submit">Change Password</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>