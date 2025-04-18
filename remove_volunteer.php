<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$db = new mysqli('localhost', 'root', '', 'volunteer_management_system');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $volunteer_id = $_POST['volunteer_id'];
    
    // Check if the event belongs to the admin
    $check_stmt = $db->prepare("SELECT admin_id FROM events WHERE event_id = ?");
    $check_stmt->bind_param("i", $event_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 1) {
        $event = $result->fetch_assoc();
        
        if ($event['admin_id'] == $_SESSION['admin_id']) {
            // Remove volunteer from event
            $delete_stmt = $db->prepare("DELETE FROM event_volunteers WHERE event_id = ? AND volunteer_id = ?");
            $delete_stmt->bind_param("ii", $event_id, $volunteer_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success_message'] = "Volunteer removed from event successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to remove volunteer from event.";
            }
        }
    }
}

// Redirect back to the event page
header("Location: view_event.php?id=$event_id");
exit();
?>