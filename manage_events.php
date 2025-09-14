<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

// Handle event approval
if (isset($_GET['approve'])) {
    $event_id = $_GET['approve'];
    $update_stmt = $conn->prepare("UPDATE events SET status = 'approved', approved = 1 WHERE event_id = ?");
    $update_stmt->bind_param("i", $event_id);
    
    if ($update_stmt->execute()) {
        $message = "Event approved successfully!";
    } else {
        $error = "Error approving event: " . $conn->error;
    }
    $update_stmt->close();
}

// Handle event rejection
if (isset($_GET['reject'])) {
    $event_id = $_GET['reject'];
    $update_stmt = $conn->prepare("UPDATE events SET status = 'rejected' WHERE event_id = ?");
    $update_stmt->bind_param("i", $event_id);
    
    if ($update_stmt->execute()) {
        $message = "Event rejected successfully!";
    } else {
        $error = "Error rejecting event: " . $conn->error;
    }
    $update_stmt->close();
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
    
    $update_stmt = $conn->prepare("UPDATE events SET event_name = ?, description = ?, date = ?, time = ?, location = ?, capacity = ? WHERE event_id = ?");
    $update_stmt->bind_param("sssssii", $event_name, $description, $date, $time, $location, $capacity, $event_id);
    
    if ($update_stmt->execute()) {
        $message = "Event updated successfully!";
    } else {
        $error = "Error updating event: " . $conn->error;
    }
    $update_stmt->close();
}

// Check if we're showing the edit form for a specific event
$edit_event_id = isset($_GET['edit']) ? $_GET['edit'] : null;
$event_to_edit = null;

if ($edit_event_id) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $edit_event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event_to_edit = $result->fetch_assoc();
    $stmt->close();
}

// Get all events with creator information
$events_result = $conn->query("
    SELECT e.*, u.username as creator_name, 
           COUNT(r.rgid) as registrations_count
    FROM events e 
    LEFT JOIN users u ON e.creator_id = u.id 
    LEFT JOIN registrations r ON e.event_id = r.event_id 
    GROUP BY e.event_id 
    ORDER BY e.date DESC, e.event_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage All Events</title>
    <style>
 body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
       
    }        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; position: sticky; top: 0; }
        tr:hover { background-color: #f9f9f9; }
        .button { 
            padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; 
            text-decoration: none; display: inline-block; margin: 2px; font-size: 14px;
        }
        .approved { background-color: #e6ffe6; color: #2e7d32; font-weight: bold; }
.pending { background-color: #fff9e6; color: #f57c00; font-weight: bold; }
.rejected { background-color: #ffe6e6; color: #d32f2f; font-weight: bold; }
.reject { background-color: #ff9800; color: white; }
        .approve { background-color: #4CAF50; color: white; }
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
    </style>
</head>
<body>
    <a href="admin_dashboard.php">← Back to Dashboard</a>
    <div class="container">
        <h1>Manage All Events</h1>
        
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
                <form method="post" action="manage_events.php">
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
                        <a href="manage_events.php" class="button cancel">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Events List -->
            <h2>All Events</h2>
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
                        <th>Creator</th>
                        <?php if ($has_approved_column): ?>
                        <th>Status</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
$status_text = isset($event['status']) ? ucfirst($event['status']) : 'Pending';
$status_class = '';
if ($event['status'] == 'approved') $status_class = 'approved';
if ($event['status'] == 'rejected') $status_class = 'rejected';
if ($event['status'] == 'pending') $status_class = 'pending';
?>
<td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                    <tr>
                        <td><?php echo $event['event_id']; ?></td>
                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($event['description']); ?></td>
                        <td><?php echo $event['date']; ?></td>
                        <td><?php echo $event['time']; ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td><?php echo $event['capacity']; ?></td>
                        <td><?php echo $event['registrations_count']; ?></td>
                        <td><?php echo htmlspecialchars($event['creator_name']); ?></td>
                        <?php if ($has_approved_column): ?>
                        <td><?php echo $status_text; ?></td>
                        <?php endif; ?>
                       <td>
    <?php if ($event['status'] == 'pending'): ?>
        <a href="?approve=<?php echo $event['event_id']; ?>" class="button approve">Approve</a>
        <a href="?reject=<?php echo $event['event_id']; ?>" class="button reject" style="background-color: #ff9800;">Reject</a>
    <?php elseif ($event['status'] == 'rejected'): ?>
        <a href="?approve=<?php echo $event['event_id']; ?>" class="button approve">Approve</a>
    <?php endif; ?>
    <a href="?edit=<?php echo $event['event_id']; ?>" class="button edit">Edit</a>
    <a href="?delete=<?php echo $event['event_id']; ?>" class="button delete" onclick="return confirm('Are you sure you want to delete this event? All registrations will also be deleted.');">Delete</a>
</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No events found.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>