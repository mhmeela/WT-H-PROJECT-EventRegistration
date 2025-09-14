<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'normal') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

// Check if approved column exists
$check_approved_column = $conn->query("SHOW COLUMNS FROM events LIKE 'approved'");
$has_approved_column = ($check_approved_column->num_rows > 0);

// Build the WHERE clause based on available columns
$where_clause = "WHERE e.date >= CURDATE()";
if ($has_approved_column) {
    $where_clause = "WHERE (e.status = 'approved' OR e.approved = 1) AND e.date >= CURDATE()";
} else {
    $where_clause = "WHERE e.status = 'approved' AND e.date >= CURDATE()";
}

// Get available events
$events_result = $conn->query("
    SELECT e.*, u.username as creator_name, 
           COUNT(r.rgid) as current_registrations
    FROM events e 
    LEFT JOIN users u ON e.creator_id = u.id 
    LEFT JOIN registrations r ON e.event_id = r.event_id 
    " . $where_clause . "
    GROUP BY e.event_id 
    ORDER BY e.date ASC
");

// Get user ID from username
$username = $_SESSION['username'];
$user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_id = $user_data['id'];
$user_stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    
    // Check if user is already registered for this event
    $check_stmt = $conn->prepare("SELECT rgid FROM registrations WHERE user_id = ? AND event_id = ?");
    $check_stmt->bind_param("ii", $user_id, $event_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "You are already registered for this event!";
    } else {
        // Register user for the event
        $insert_stmt = $conn->prepare("INSERT INTO registrations (user_id, event_id, status) VALUES (?, ?, 'registered')");
        $insert_stmt->bind_param("ii", $user_id, $event_id);
        
        if ($insert_stmt->execute()) {
            $message = "Successfully registered for the event!";
            
            // Send confirmation notification
            $event_info_stmt = $conn->prepare("SELECT event_name FROM events WHERE event_id = ?");
            $event_info_stmt->bind_param("i", $event_id);
            $event_info_stmt->execute();
            $event_info_result = $event_info_stmt->get_result();
            $event_info = $event_info_result->fetch_assoc();
            $event_info_stmt->close();
            
            $notification_title = "Registration Confirmed";
            $notification_message = "You have successfully registered for the event: " . $event_info['event_name'];
            
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $notif_stmt->bind_param("iss", $user_id, $notification_title, $notification_message);
            $notif_stmt->execute();
            $notif_stmt->close();
        } else {
            $error = "Error registering for event: " . $conn->error;
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register for Events</title>
    <style>
 body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .event-card { 
            border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; 
            background: #f9f9f9; 
        }
        .event-card h3 { margin-top: 0; color: #2c3e50; }
        .event-details { color: #555; margin-bottom: 10px; }
        .event-details div { margin-bottom: 5px; }
        .register-btn { 
            background-color: #4CAF50; color: white; padding: 8px 15px; border: none; 
            border-radius: 4px; cursor: pointer; 
        }
        .register-btn:hover { background-color: #45a049; }
        .register-btn:disabled { 
            background-color: #cccccc; cursor: not-allowed; 
        }
        .message { color: green; margin-bottom: 15px; padding: 10px; background: #e6ffe6; border-radius: 4px; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 4px; }
        .capacity { font-weight: bold; }
        .full { color: #e74c3c; }
        .available { color: #27ae60; }
    </style>
</head>
<body>
    <a href="user_dashboard.php">← Back to Dashboard</a>
    <div class="container">
        <h1>Register for Events</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <h2>Available Events</h2>
        
        <?php if ($events_result && $events_result->num_rows > 0): ?>
            <?php while ($event = $events_result->fetch_assoc()): 
                $is_full = $event['current_registrations'] >= $event['capacity'];
                $spots_remaining = $event['capacity'] - $event['current_registrations'];
            ?>
                <div class="event-card">
                    <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                    
                    <div class="event-details">
                        <div><strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?></div>
                        <div><strong>Date:</strong> <?php echo $event['date']; ?></div>
                        <div><strong>Time:</strong> <?php echo $event['time']; ?></div>
                        <div><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></div>
                        <div><strong>Organizer:</strong> <?php echo htmlspecialchars($event['creator_name']); ?></div>
                        <div class="capacity">
                            <strong>Capacity:</strong> 
                            <span class="<?php echo $is_full ? 'full' : 'available'; ?>">
                                <?php echo $event['current_registrations']; ?> / <?php echo $event['capacity']; ?>
                                <?php if (!$is_full): ?>
                                    (<?php echo $spots_remaining; ?> spots remaining)
                                <?php else: ?>
                                    (FULL)
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <form method="post" action="">
                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <button type="submit" class="register-btn" <?php echo $is_full ? 'disabled' : ''; ?>>
                            <?php echo $is_full ? 'Event Full' : 'Register Now'; ?>
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No available events at the moment. Please check back later!</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>