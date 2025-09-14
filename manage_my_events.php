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


// Handle event deletion
if (isset($_GET['delete'])) {
    $event_id = $_GET['delete'];
    
    // Verify that the event belongs to the current user
    $verify_stmt = $conn->prepare("SELECT event_id FROM events WHERE event_id = ? AND creator_id = ?");
    $verify_stmt->bind_param("ii", $event_id, $creator_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        // First delete related registrations
        $delete_registrations = $conn->prepare("DELETE FROM registrations WHERE event_id = ?");
        $delete_registrations->bind_param("i", $event_id);
        $delete_registrations->execute();
        $delete_registrations->close();
        
        // Then delete the event
        $delete_stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        $delete_stmt->bind_param("i", $event_id);
        
        if ($delete_stmt->execute()) {
            $message = "Event deleted successfully!";
        } else {
            $error = "Error deleting event: " . $conn->error;
        }
        $delete_stmt->close();
    } else {
        $error = "You can only delete your own events.";
    }
    $verify_stmt->close();
}

// Handle event editing form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_event'])) {
    $event_id = $_POST['event_id'];
    $event_name = $_POST['event_name'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = $_POST['location'];
    $capacity = $_POST['capacity'];
    
    // Verify that the event belongs to the current user
    $verify_stmt = $conn->prepare("SELECT event_id FROM events WHERE event_id = ? AND creator_id = ?");
    $verify_stmt->bind_param("ii", $event_id, $creator_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE events SET event_name = ?, description = ?, date = ?, time = ?, location = ?, capacity = ? WHERE event_id = ?");
        $update_stmt->bind_param("sssssii", $event_name, $description, $date, $time, $location, $capacity, $event_id);
        
        if ($update_stmt->execute()) {
            $message = "Event updated successfully!";
        } else {
            $error = "Error updating event: " . $conn->error;
        }
        $update_stmt->close();
    } else {
        $error = "You can only edit your own events.";
    }
    $verify_stmt->close();
}

// Check if we're showing the edit form for a specific event
$edit_event_id = isset($_GET['edit']) ? $_GET['edit'] : null;
$event_to_edit = null;

if ($edit_event_id) {
    // Verify that the event belongs to the current user
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $edit_event_id, $creator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event_to_edit = $result->fetch_assoc();
    $stmt->close();
    
    if (!$event_to_edit) {
        $error = "Event not found or you don't have permission to edit it.";
        $edit_event_id = null;
    }
}

// Get current user's events
$events_result = $conn->query("
    SELECT e.*, COUNT(r.rgid) as registrations_count
    FROM events e 
    LEFT JOIN registrations r ON e.event_id = r.event_id 
    WHERE e.creator_id = $creator_id
    GROUP BY e.event_id 
    ORDER BY e.date DESC, e.event_id DESC
");

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
    <title>Manage My Events</title>
    <style>
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f9f9f9; }
        .button { 
            padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; 
            text-decoration: none; display: inline-block; margin: 2px; font-size: 14px;
        }
        .edit { background-color: #2196F3; color: white; }
        .delete { background-color: #f44336; color: white; }
        .save { background-color: #4CAF50; color: white; padding: 10px 20px; }
        .cancel { background-color: #9e9e9e; color: white; padding: 10px 20px; }
        .button:hover { opacity: 0.8; }
        .message { color: green; margin-bottom: 15px; padding: 10px; background: #e6ffe6; border-radius: 4px; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 4px; }
        .form-container { max-width: 600px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; 
            box-sizing: border-box; 
        }
        .form-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .approved { background-color: #e6ffe6; }
        .pending { background-color: #fff9e6; }
    </style>
</head>
<body>
    <a href="<?php echo $dashboard_link; ?>">← Back to Dashboard</a>
    <div class="container">
        <h1>Manage My Events</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($event_to_edit): ?>
            <!-- Edit Event Form -->
            <div class="form-container">
                <h2>Edit Event: <?php echo htmlspecialchars($event_to_edit['event_name']); ?></h2>
                <form method="post" action="manage_my_events.php">
                    <input type="hidden" name="event_id" value="<?php echo $event_to_edit['event_id']; ?>">
                    <input type="hidden" name="edit_event" value="1">
                    
                    <div class="form-group">
                        <label for="event_name">Event Name:</label>
                        <input type="text" id="event_name" name="event_name" value="<?php echo htmlspecialchars($event_to_edit['event_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3" required><?php echo htmlspecialchars($event_to_edit['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" value="<?php echo $event_to_edit['date']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">Time:</label>
                        <input type="time" id="time" name="time" value="<?php echo $event_to_edit['time']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event_to_edit['location']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="capacity">Capacity:</label>
                        <input type="number" id="capacity" name="capacity" min="1" value="<?php echo $event_to_edit['capacity']; ?>" required>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="button save">Save Changes</button>
                        <a href="manage_my_events.php" class="button cancel">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Events List -->
            <h2>My Events</h2>
         <?php if ($events_result && $events_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Event ID</th>
                        <th>Event Name</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Registrations</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                                   <tbody>
                    <?php while ($event = $events_result->fetch_assoc()): 
                        $status_text = isset($event['status']) ? ucfirst($event['status']) : 'Pending';
                        $status_class = '';
                        if (isset($event['status'])) {
                            if ($event['status'] == 'approved') $status_class = 'approved';
                            if ($event['status'] == 'rejected') $status_class = 'rejected';
                            if ($event['status'] == 'pending') $status_class = 'pending';
                        }
                    ?>
                    <tr class="<?php echo $status_class; ?>">
                        <td><?php echo $event['event_id']; ?></td>
                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($event['description']); ?></td>
                        <td><?php echo $event['date']; ?></td>
                        <td><?php echo $event['time']; ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td><?php echo $event['capacity']; ?></td>
                        <td><?php echo $event['registrations_count']; ?></td>
                        <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                        <td>
                            <a href="?edit=<?php echo $event['event_id']; ?>" class="button edit">Edit</a>
                            <a href="?delete=<?php echo $event['event_id']; ?>" class="button delete" onclick="return confirm('Are you sure you want to delete this event? All registrations will also be deleted.');">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>You haven't created any events yet. <a href="create_event.php">Create your first event</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>