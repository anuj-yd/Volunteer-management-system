<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

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

// Get certificate ID
$certificate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($certificate_id <= 0) {
    die("Invalid certificate ID");
}

// Get certificate details
$certificate = $db->query("
    SELECT c.*, v.first_name, v.last_name, e.event_name, e.event_date, e.location
    FROM certificates c
    JOIN volunteers v ON c.volunteer_id = v.volunteer_id
    JOIN events e ON c.event_id = e.event_id
    WHERE c.certificate_id = $certificate_id AND e.admin_id = {$_SESSION['admin_id']}
")->fetch_assoc();

if (!$certificate) {
    die("Certificate not found or you don't have permission to view it");
}

// Output the certificate content
echo $certificate['certificate_content'];

$db->close();
?>