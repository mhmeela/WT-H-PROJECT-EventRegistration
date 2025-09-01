<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";   
$password = "";      
$dbname = "blablabla"; 

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Login processing
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $inputUsername = mysqli_real_escape_string($conn, $_POST['username']);
    $inputPassword = mysqli_real_escape_string($conn, $_POST['password']);

    // Query the database for the user
    $sql = "SELECT * FROM users WHERE username = '$inputUsername' AND password = MD5('$inputPassword')";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type']; // admin/organizer/attendee
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid username or password";
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>
</head>
<body>
<h2>Login</h2>

<?php if (isset($error)): ?>
<p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post">
    <div>
        <label>Username:</label>
        <input type="text" name="username" required>
    </div>
    <div>
        <label>Password:</label>
        <input type="password" name="password" required>
    </div>
    <div>
        <input type="submit" value="Login">
    </div>
</form>

<p>Don't have an account? 
    <a href="signup.php">Sign Up here</a>
</p>

</body>
</html>
