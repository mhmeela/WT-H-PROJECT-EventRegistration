<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'creator') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

// Get current creator's events
$username = $_SESSION['username'];
$creator_events = $conn->query("
    SELECT e.event_id, e.event_name 
    FROM events e 
    JOIN users u ON e.creator_id = u.id 
    WHERE u.username = '$username'
");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $update_message = $_POST['message'];
    
    if (empty($event_id) || empty($update_message)) {
        $error = "Please select an event and enter a message.";
    } else {
        $users_result = $conn->query("
            SELECT DISTINCT u.id 
            FROM users u 
            JOIN registrations r ON u.id = r.user_id 
            WHERE r.event_id = $event_id
        ");
        
       // Send notification to each registered user
while ($user = $users_result->fetch_assoc()) {
    $user_id = $user['id'];
    $title = "Update for: " . $event['event_name'];
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $update_message);
    
    if ($stmt->execute()) {
        $notification_count++;
    }
    $stmt->close();
}
        
        $message = "Update sent to $notification_count registered users!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Event Updates</title>
    <style>    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { 
            background-color: #4CAF50; color: white; padding: 10px 15px; border: none; 
            border-radius: 4px; cursor: pointer; 
        }
        .message { color: green; margin-bottom: 15px; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <a href="creator_dashboard.php">← Back to Dashboard</a>
    <div class="container">
        <h1>Send Event Updates</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="event_id">Select Event:</label>
                <select id="event_id" name="event_id" required>
                    <option value="">-- Select an Event --</option>
                    <?php while ($event = $creator_events->fetch_assoc()): ?>
                        <option value="<?php echo $event['event_id']; ?>">
                            <?php echo htmlspecialchars($event['event_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message">Update Message:</label>
                <textarea id="message" name="message" rows="5" required 
                          placeholder="Enter your event update message here..."></textarea>
            </div>
            
            <button type="submit">Send Update to Registered Users</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>