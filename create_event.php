<?php
session_start();
if (!isset($_SESSION['username']) || ($_SESSION['user_type'] !== 'creator' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

// Get user ID from username
$username = $_SESSION['username'];
$user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$creator_id = $user_data['id'];
$user_stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_name = $_POST['event_name'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = $_POST['location'];
    $capacity = $_POST['capacity'];
    $status = "pending"; // Default status for new events
    
    // Validate inputs
    if (empty($event_name) || empty($date) || empty($location) || empty($capacity)) {
        $error = "Please fill in all required fields.";
    } else {
        // Insert new event
        $stmt = $conn->prepare("INSERT INTO events (event_name, description, date, time, location, capacity, creator_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiis", $event_name, $description, $date, $time, $location, $capacity, $creator_id, $status);
        
        if ($stmt->execute()) {
            $message = "Event created successfully! It will be reviewed by an administrator.";
            // Clear form fields
            $_POST = array();
        } else {
            $error = "Error creating event: " . $conn->error;
        }
        $stmt->close();
    }
}

// Determine dashboard link based on user type
$dashboard_link = "dashboard.php";
if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'admin': $dashboard_link = "admin_dashboard.php"; break;
        case 'creator': $dashboard_link = "creator_dashboard.php"; break;
        case 'normal': $dashboard_link = "user_dashboard.php"; break;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create New Event</title>
    <style>
 body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; 
            box-sizing: border-box; 
        }
        button { 
            background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; 
            cursor: pointer; margin-right: 10px; 
        }
        button:hover { opacity: 0.9; }
        .message { color: green; margin-bottom: 15px; padding: 10px; background: #e6ffe6; border-radius: 4px; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 4px; }
    </style>
</head>
<body>
    <a href="<?php echo $dashboard_link; ?>">← Back to Dashboard</a>
    <div class="container">
        <h1>Create New Event</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="event_name">Event Name *</label>
                <input type="text" id="event_name" name="event_name" value="<?php echo isset($_POST['event_name']) ? htmlspecialchars($_POST['event_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="date">Date *</label>
                <input type="date" id="date" name="date" value="<?php echo isset($_POST['date']) ? $_POST['date'] : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="time">Time</label>
                <input type="time" id="time" name="time" value="<?php echo isset($_POST['time']) ? $_POST['time'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="capacity">Capacity *</label>
                <input type="number" id="capacity" name="capacity" min="1" value="<?php echo isset($_POST['capacity']) ? $_POST['capacity'] : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <p><small>Note: All events require admin approval before being published.</small></p>
            </div>
            
            <button type="submit">Create Event</button>
            <button type="reset">Clear Form</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>