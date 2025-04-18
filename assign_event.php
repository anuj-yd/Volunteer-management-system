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
    // Validate and sanitize inputs
    $volunteer_id = filter_input(INPUT_POST, 'volunteer_id', FILTER_VALIDATE_INT);
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    
    // Validate inputs
    if ($volunteer_id === false || $volunteer_id <= 0 || $event_id === false || $event_id <= 0) {
        $_SESSION['error_message'] = "Invalid volunteer or event selection";
        header("Location: volunteers.php");
        exit();
    }
    
    // Check if assignment already exists
    $check_stmt = $db->prepare("SELECT id FROM event_volunteers WHERE event_id = ? AND volunteer_id = ?");
    $check_stmt->bind_param("ii", $event_id, $volunteer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error_message'] = "This volunteer is already assigned to the selected event";
    } else {
        // Create new assignment
        $insert_stmt = $db->prepare("INSERT INTO event_volunteers (event_id, volunteer_id, registration_date) VALUES (?, ?, NOW())");
        $insert_stmt->bind_param("ii", $event_id, $volunteer_id);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success_message'] = "Volunteer successfully assigned to event!";
        } else {
            $_SESSION['error_message'] = "Error assigning volunteer: " . $db->error;
        }
    }
}

header("Location: volunteers.php");
exit();
?>