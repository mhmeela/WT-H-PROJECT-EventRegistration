<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'normal') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

// Get user ID
$username = $_SESSION['username'];
$user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_id = $user_data['id'];
$user_stmt->close();

// DEBUG: Check what events the user is registered for
$debug_query = $conn->query("
    SELECT e.event_id, e.event_name, e.date, e.date < CURDATE() as is_past
    FROM events e 
    JOIN registrations r ON e.event_id = r.event_id 
    WHERE r.user_id = $user_id 
    ORDER BY e.date DESC
");

echo "<!-- DEBUG: User ID: $user_id -->";
echo "<!-- DEBUG: Current date: " . date('Y-m-d') . " -->";
echo "<!-- DEBUG: User's registered events -->";
while ($debug = $debug_query->fetch_assoc()) {
    echo "<!-- Event: {$debug['event_name']} | Date: {$debug['date']} | Is Past: {$debug['is_past']} -->";
}

// Get events the user has registered for (TEMPORARY: include future events for testing)
$events_result = $conn->query("
    SELECT e.event_id, e.event_name, e.date 
    FROM events e 
    JOIN registrations r ON e.event_id = r.event_id 
    WHERE r.user_id = $user_id 
    AND (e.date < CURDATE() OR e.date LIKE '2025%')  -- Include 2025 events for testing
    ORDER BY e.date DESC
");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $rating = $_POST['rating'];
    $feedback = trim($_POST['feedback']);
    
    if (empty($event_id)) {
        $error = "Please select an event.";
    } elseif (empty($rating)) {
        $error = "Please provide a rating.";
    } else {
        // Verify the event exists and user is registered
        $verify_stmt = $conn->prepare("SELECT e.event_id FROM events e JOIN registrations r ON e.event_id = r.event_id WHERE r.user_id = ? AND e.event_id = ?");
        $verify_stmt->bind_param("ii", $user_id, $event_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            $error = "Invalid event selection.";
        } else {
            // Check if user already submitted feedback for this event
            $check_stmt = $conn->prepare("SELECT rgid FROM registrations WHERE user_id = ? AND event_id = ? AND (feedback IS NOT NULL OR rating IS NOT NULL)");
            $check_stmt->bind_param("ii", $user_id, $event_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "You have already submitted feedback for this event.";
            } else {
                // Update registration with feedback and rating
                $update_stmt = $conn->prepare("UPDATE registrations SET rating = ?, feedback = ? WHERE user_id = ? AND event_id = ?");
                $update_stmt->bind_param("isii", $rating, $feedback, $user_id, $event_id);
                
                if ($update_stmt->execute()) {
                    $message = "Thank you for your feedback!";
                    
                    // Send confirmation notification
                    $event_info_stmt = $conn->prepare("SELECT event_name FROM events WHERE event_id = ?");
                    $event_info_stmt->bind_param("i", $event_id);
                    $event_info_stmt->execute();
                    $event_info_result = $event_info_stmt->get_result();
                    $event_info = $event_info_result->fetch_assoc();
                    
                    $notification_title = "Feedback Submitted";
                    $notification_message = "Thank you for providing feedback for: " . $event_info['event_name'];
                    
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                    $notif_stmt->bind_param("iss", $user_id, $notification_title, $notification_message);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                    $event_info_stmt->close();
                } else {
                    $error = "Error submitting feedback: " . $conn->error;
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
        $verify_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Provide Feedback</title>
    <style>
 body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { 
            background-color: #4CAF50; color: white; padding: 10px 15px; border: none; 
            border-radius: 4px; cursor: pointer; margin-top: 10px;
        }
        button:hover { background-color: #45a049; }
        .message { color: green; padding: 10px; background: #e6ffe6; border-radius: 4px; margin-bottom: 15px; }
        .error { color: red; padding: 10px; background: #ffe6e6; border-radius: 4px; margin-bottom: 15px; }
        .rating { display: flex; gap: 5px; margin: 10px 0; }
        .rating input { display: none; }
        .rating label { 
            font-size: 32px; cursor: pointer; color: #ccc; 
            transition: color 0.2s; margin: 0; 
        }
        .rating input:checked ~ label { color: #ffc107; }
        .rating label:hover,
        .rating label:hover ~ label { color: #ffc107; }
        .rating-value { 
            margin-left: 15px; font-weight: bold; color: #ffc107; 
            font-size: 18px; vertical-align: middle;
        }
        .event-card { 
            background: #f9f9f9; padding: 15px; border-radius: 5px; 
            border-left: 4px solid #4CAF50; margin: 10px 0;
        }
        .debug-info { 
            background: #ffe; padding: 10px; margin: 10px 0; border-radius: 5px; 
            border: 1px solid #dd0; font-size: 12px; 
        }
    </style>
</head>
<body>
    <a href="user_dashboard.php">← Back to Dashboard</a>
    <div class="container">
        <h1>Provide Feedback for Events</h1>
        
        <!-- Debug information -->
        <div class="debug-info">
            <strong>Debug Info:</strong> View page source to see detailed debug information about your registered events.
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($events_result->num_rows > 0): ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="event_id">Select Event:</label>
                    <select id="event_id" name="event_id" required>
                        <option value="">-- Choose an Event --</option>
                        <?php while ($event = $events_result->fetch_assoc()): 
                            $event_date = new DateTime($event['date']);
                            $is_future = $event_date > new DateTime();
                        ?>
                            <option value="<?php echo $event['event_id']; ?>">
                                <?php echo htmlspecialchars($event['event_name']); ?> 
                                (<?php echo $is_future ? 'Scheduled: ' : 'Attended: '; ?><?php echo $event['date']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Rating: <span id="rating-value" class="rating-value">0 stars</span></label>
                    <div class="rating">
                        <input type="radio" id="star5" name="rating" value="5" required>
                        <label for="star5" title="5 stars">⭐</label>
                        
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4" title="4 stars">⭐</label>
                        
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3" title="3 stars">⭐</label>
                        
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2" title="2 stars">⭐</label>
                        
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1" title="1 star">⭐</label>
                    </div>
                    <small>Click on the stars to rate (1 star = Poor, 5 stars = Excellent)</small>
                </div>
                
                <div class="form-group">
                    <label for="feedback">Your Feedback:</label>
                    <textarea id="feedback" name="feedback" rows="4" 
                              placeholder="Share your experience, what you enjoyed, suggestions for improvement..."></textarea>
                </div>
                
                <button type="submit">Submit Feedback</button>
            </form>
        <?php else: ?>
            <div class="event-card">
                <h3>No Events Available for Feedback</h3>
                <p>You haven't registered for any events yet.</p>
                <p>Register for events first, then come back here to provide feedback after attending them.</p>
                <a href="events.php" style="color: #4CAF50; text-decoration: none;">← Browse events to register</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Star rating functionality
        const ratingInputs = document.querySelectorAll('.rating input');
        const ratingValue = document.getElementById('rating-value');
        
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingValue.textContent = this.value + ' stars';
                
                // Update star colors
                const stars = document.querySelectorAll('.rating label');
                stars.forEach(star => {
                    star.style.color = '#ccc';
                });
                
                // Color stars up to the selected one
                let current = this;
                while (current) {
                    const label = document.querySelector('label[for="' + current.id + '"]');
                    if (label) {
                        label.style.color = '#ffc107';
                    }
                    current = current.previousElementSibling;
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>