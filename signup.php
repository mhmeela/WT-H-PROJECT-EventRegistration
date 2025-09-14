<?php
session_start();
ob_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_type = $_POST['user_type'];
    $username = $_POST['username'];
    $password = $_POST['password']; // Store plain text password
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $emergency_name = $_POST['emergency_name'];
    $emergency_number = $_POST['emergency_number'];
    $emergency_relation = $_POST['emergency_relation'];
    
    $check_sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        $error_message = "Username or email already exists. Please choose different ones.";
    } else {
        $sql = "INSERT INTO users (user_type, username, password, email, first_name, last_name, phone, gender, blood_group, emergency_name, emergency_number, emergency_relation) 
                VALUES ('$user_type', '$username', '$password', '$email', '$first_name', '$last_name', '$phone', '$gender', '$blood_group', '$emergency_name', '$emergency_number', '$emergency_relation')";
        
        if ($conn->query($sql) === TRUE) {
            $success_message = "Registration successful! Redirecting to login page...";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 2000);
            </script>";
        } else {
            $error_message = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
        <link rel="stylesheet" type="text/css" href="signup.css">

</head>
<body>
    <div class="container">
        <h2>Create an Account</h2>
        
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php endif; ?>
        
        <form method="post" action="">
            <div>
                <label for="user_type">Register as: *</label>
                <select id="user_type" name="user_type" required>
                    <option value="">-- Select Role --</option>
                    <option value="normal">Normal User</option>
                    <option value="creator">Event Creator</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            
            <h3>Account Information</h3>
            
            <div>
                <label for="username">Username: *</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div>
                <label for="password">Password: *</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div>
                <label for="email">Email: *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <h3>Personal Information</h3>
            
            <div>
                <label for="first_name">First Name: *</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div>
                <label for="last_name">Last Name: *</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            
            <div>
                <label for="phone">Phone Number: *</label>
                <input type="tel" id="phone" name="phone" required>
            </div>

            <div>
                <label>Gender: *</label>
                <input type="radio" id="male" name="gender" value="male" required> <label for="male">Male</label>
                <input type="radio" id="female" name="gender" value="female"> <label for="female">Female</label>
                <input type="radio" id="other" name="gender" value="other"> <label for="other">Other</label>
            </div>

            <div>
                <label for="blood-group">Blood Group:</label>
                <select id="blood-group" name="blood_group">
                    <option value="">--Select--</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                </select>
            </div>

            <h3>Emergency Contact</h3>

            <div>
                <label for="emergency-name">Emergency Contact Name: *</label>
                <input type="text" id="emergency-name" name="emergency_name" required placeholder="Enter full name">
            </div>

            <div>
                <label for="emergency-number">Emergency Contact Number: *</label>
                <input type="tel" id="emergency-number" name="emergency_number" required placeholder="Enter phone number">
            </div>

            <div>
                <label for="emergency-relation">Relationship: *</label>
                <input type="text" id="emergency-relation" name="emergency_relation" required placeholder="E.g., Parent, Sibling, Friend">
            </div>

            <div>
                <input type="submit" value="Sign Up">
            </div>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
    <script src="signup.js"></script>
</body>
</html>