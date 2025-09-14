<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

// Get user data
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username); // Changed from "i" to "s" for string
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission for updating profile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $emergency_name = $_POST['emergency_name'];
    $emergency_number = $_POST['emergency_number'];
    $emergency_relation = $_POST['emergency_relation'];
    
    // Update using username instead of ID
    $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, gender = ?, blood_group = ?, emergency_name = ?, emergency_number = ?, emergency_relation = ? WHERE username = ?");
    $update_stmt->bind_param("ssssssssss", $first_name, $last_name, $email, $phone, $gender, $blood_group, $emergency_name, $emergency_number, $emergency_relation, $username); // Changed to "s" for username
    
    if ($update_stmt->execute()) {
        $message = "Profile updated successfully!";
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
    $update_stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $user_id = $user['id'];
    
    $delete_registrations = $conn->prepare("DELETE FROM registrations WHERE user_id = ?");
    $delete_registrations->bind_param("i", $user_id);
    $delete_registrations->execute();
    $delete_registrations->close();
    
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $delete_stmt->bind_param("s", $username); // Changed from "i" to "s" for string
    
    if ($delete_stmt->execute()) {
        session_destroy();
        header("Location: login.php?message=Account+deleted+successfully");
        exit();
    } else {
        $error = "Error deleting account: " . $conn->error;
    }
    $delete_stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Profile</title>
    <style>
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }
       .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="tel"], select { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; 
        }
        button { 
            background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; 
            cursor: pointer; margin-right: 10px; 
        }
        button.delete { background-color: #f44336; }
        button:hover { opacity: 0.9; }
        .message { color: green; margin-bottom: 15px; padding: 10px; background: #e6ffe6; border-radius: 4px; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 4px; }
        .section { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        h2 { color: #333; }
        .button-group { margin-top: 20px; }
    </style>
</head>
<body>
   <?php
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
        <h1>Manage Profile</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Personal Information</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small><em>Username cannot be changed</em></small>
                </div>
                
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ($user['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($user['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo ($user['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="blood_group">Blood Group:</label>
                    <input type="text" id="blood_group" name="blood_group" value="<?php echo htmlspecialchars($user['blood_group']); ?>">
                </div>
        </div>
        
        <div class="section">
            <h2>Emergency Contact</h2>
                <div class="form-group">
                    <label for="emergency_name">Emergency Contact Name:</label>
                    <input type="text" id="emergency_name" name="emergency_name" value="<?php echo htmlspecialchars($user['emergency_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="emergency_number">Emergency Contact Number:</label>
                    <input type="tel" id="emergency_number" name="emergency_number" value="<?php echo htmlspecialchars($user['emergency_number']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="emergency_relation">Relationship:</label>
                    <input type="text" id="emergency_relation" name="emergency_relation" value="<?php echo htmlspecialchars($user['emergency_relation']); ?>">
                </div>
                
                <button type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>
        
        <div class="section">
            <h2>Account Management</h2>
            <p><strong>Account Created:</strong> <?php echo $user['created_at']; ?></p>
            <p><strong>Last Updated:</strong> <?php echo $user['updated_at']; ?></p>
            
            <div class="button-group">
                <button onclick="location.href='change_password.php'">Change Password</button>
                
                <form method="post" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                    <button type="submit" name="delete_account" class="delete">Delete Account</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>