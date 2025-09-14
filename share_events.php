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

// Get all normal users (except current user)
$current_username = $_SESSION['username'];
$users_result = $conn->query("
    SELECT username, email 
    FROM users 
    WHERE username != '$current_username' 
    AND user_type = 'normal'
    ORDER BY username
");

// Store users in an array for reuse
$users = [];
while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $recipient_username = trim($_POST['recipient_username']);
    $personal_message = trim($_POST['message'] ?? '');
    
    if (empty($event_id)) {
        $error = "Please select an event to share.";
    } elseif (empty($recipient_username)) {
        $error = "Please select a user to share with.";
    } else {
        // Check if recipient exists
        $recipient_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND user_type = 'normal'");
        $recipient_stmt->bind_param("s", $recipient_username);
        $recipient_stmt->execute();
        $recipient_result = $recipient_stmt->get_result();
        
        if ($recipient_result->num_rows === 0) {
            $error = "User '$recipient_username' not found or not a normal user.";
        } else {
            $recipient = $recipient_result->fetch_assoc();
            $recipient_id = $recipient['id'];
            
            // Get event details
            $event_stmt = $conn->prepare("SELECT event_name FROM events WHERE event_id = ?");
            $event_stmt->bind_param("i", $event_id);
            $event_stmt->execute();
            $event_result = $event_stmt->get_result();
            $event = $event_result->fetch_assoc();
            
            $event_name = $event['event_name'];
            $sender_username = $_SESSION['username'];
            
            // Create notification message
            $notification_message = "📨 **Event Shared with You**\n\n";
            $notification_message .= "**User:** $sender_username\n";
            $notification_message .= "**Event:** $event_name\n\n";
            
            if (!empty($personal_message)) {
                $notification_message .= "**Message from $sender_username:**\n";
                $notification_message .= "$personal_message\n\n";
            }
            
            $notification_message .= "Check out the event in the events section!";
            
            // Send notification
            $title = "Event Shared: $event_name";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $notif_stmt->bind_param("iss", $recipient_id, $title, $notification_message);
            
            if ($notif_stmt->execute()) {
                $message = "Event shared successfully with $recipient_username!";
            } else {
                $error = "Error sharing event: " . $conn->error;
            }
            
            $notif_stmt->close();
            $event_stmt->close();
        }
        $recipient_stmt->close();
    }
}

// Get available events - FIXED: Use status column instead of approved
$events_result = $conn->query("
    SELECT e.* 
    FROM events e 
    WHERE e.status = 'approved'
    AND e.date >= CURDATE()
    ORDER BY e.date ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Share Event Links</title>
    <style>
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }
       .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { 
            background-color: #4CAF50; color: white; padding: 10px 15px; border: none; 
            border-radius: 4px; cursor: pointer; margin-top: 10px;
        }
        button:hover { background-color: #45a049; }
        .message { color: green; padding: 10px; background: #e6ffe6; border-radius: 4px; margin-bottom: 15px; }
        .error { color: red; padding: 10px; background: #ffe6e6; border-radius: 4px; margin-bottom: 15px; }
        .event-info { 
            background: #f0f8ff; padding: 10px; border-radius: 5px; margin: 10px 0; 
            border-left: 4px solid #2196F3; 
        }
        .user-badge {
            display: inline-block;
            background: #e3f2fd;
            padding: 5px 10px;
            border-radius: 15px;
            margin: 5px 5px 5px 0;
            font-size: 14px;
        }
        .search-container { position: relative; }
        .user-list {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
            display: none;
            position: absolute;
            width: 100%;
            background: white;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .user-item {
            padding: 8px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .user-item:hover {
            background-color: #f5f5f5;
        }
        .user-item:last-child {
            border-bottom: none;
        }
        .user-email {
            font-size: 12px;
            color: #666;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <a href="user_dashboard.php">← Back to Dashboard</a>
    <div class="container">
        <h1>Share Event Links</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="event_id">Select Event to Share:</label>
                <select id="event_id" name="event_id" required onchange="showEventInfo()">
                    <option value="">-- Choose an Event --</option>
                    <?php while ($event = $events_result->fetch_assoc()): ?>
                        <option value="<?php echo $event['event_id']; ?>" 
                                data-name="<?php echo htmlspecialchars($event['event_name']); ?>"
                                data-date="<?php echo $event['date']; ?>"
                                data-location="<?php echo htmlspecialchars($event['location']); ?>">
                            <?php echo htmlspecialchars($event['event_name']); ?> 
                            (<?php echo $event['date']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div id="event-preview" class="event-info" style="display: none;">
                <strong>Selected Event:</strong>
                <div id="event-details"></div>
            </div>
            
            <div class="form-group">
                <label for="recipient_search">Share With (Search Users):</label>
                <div class="search-container">
                    <input type="text" id="recipient_search" placeholder="Type to search users..." 
                           onkeyup="filterUsers()" onfocus="showUserList()">
                    <div id="user-list" class="user-list">
                        <?php foreach ($users as $user): ?>
                            <div class="user-item" data-username="<?php echo $user['username']; ?>">
                                <?php echo $user['username']; ?>
                                <span class="user-email">(<?php echo $user['email']; ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="recipient_username" name="recipient_username" required>
                </div>
                
                <div id="selected-user" style="margin-top: 10px; display: none;">
                    <strong>Selected User:</strong>
                    <div id="user-display" class="user-badge">
                        <span id="selected-username"></span>
                        <span style="margin-left: 8px; cursor: pointer;" onclick="clearSelection()">×</span>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="message">Personal Message (optional):</label>
                <textarea id="message" name="message" rows="3" placeholder="Add a personal message..."></textarea>
            </div>
            
            <button type="submit">📨 Share Event</button>
        </form>
    </div>

    <script>
        function showEventInfo() {
            const eventSelect = document.getElementById('event_id');
            const eventPreview = document.getElementById('event-preview');
            const eventDetails = document.getElementById('event-details');
            
            if (eventSelect.value) {
                const selectedOption = eventSelect.options[eventSelect.selectedIndex];
                const eventName = selectedOption.getAttribute('data-name');
                const eventDate = selectedOption.getAttribute('data-date');
                const eventLocation = selectedOption.getAttribute('data-location');
                
                eventDetails.innerHTML = `
                    <strong>${eventName}</strong><br>
                    Date: ${eventDate}<br>
                    Location: ${eventLocation}
                `;
                eventPreview.style.display = 'block';
            } else {
                eventPreview.style.display = 'none';
            }
        }
        
        function showUserList() {
            document.getElementById('user-list').style.display = 'block';
        }
        
        function filterUsers() {
            const search = document.getElementById('recipient_search').value.toLowerCase();
            const userItems = document.querySelectorAll('.user-item');
            
            userItems.forEach(item => {
                const username = item.getAttribute('data-username').toLowerCase();
                if (username.includes(search)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        document.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', function() {
                const username = this.getAttribute('data-username');
                document.getElementById('recipient_search').value = '';
                document.getElementById('recipient_username').value = username;
                document.getElementById('selected-username').textContent = username;
                document.getElementById('selected-user').style.display = 'block';
                document.getElementById('user-list').style.display = 'none';
            });
        });
        
        function clearSelection() {
            document.getElementById('recipient_username').value = '';
            document.getElementById('selected-user').style.display = 'none';
            document.getElementById('user-list').style.display = 'block';
        }
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                document.getElementById('user-list').style.display = 'none';
            }
        });
        
        // Initial check
        showEventInfo();
    </script>
</body>
</html>
<?php $conn->close(); ?>