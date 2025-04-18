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

// Get certificate ID from URL
$certificate_id = isset($_GET['id']) ? $db->real_escape_string($_GET['id']) : '';

if (empty($certificate_id)) {
    header('Location: certificates.php');
    exit();
}

// Get certificate details to verify ownership
$certificate = $db->query("
    SELECT e.admin_id 
    FROM certificates c
    JOIN events e ON c.event_id = e.event_id
    WHERE c.certificate_id = '$certificate_id'
")->fetch_assoc();

if (!$certificate || $certificate['admin_id'] != $_SESSION['admin_id']) {
    header('Location: certificates.php');
    exit();
}

// Delete the certificate
$db->query("DELETE FROM certificates WHERE certificate_id = '$certificate_id'");

// Update participation record
$db->query("
    UPDATE volunteer_participation 
    SET certificate_generated = 0 
    WHERE event_id = (SELECT event_id FROM certificates WHERE certificate_id = '$certificate_id')
    AND volunteer_id = (SELECT volunteer_id FROM certificates WHERE certificate_id = '$certificate_id')
");

$_SESSION['success_message'] = "Certificate deleted successfully!";
header("Location: view_certificates.php?event_id=" . $certificate['event_id']);
exit();
?>