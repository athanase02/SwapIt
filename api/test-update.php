<?php
session_start();
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';

// Log all received data
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Simulate a logged-in user for testing
if (!isset($_SESSION['user_id'])) {
    // Get first user from database for testing
    $result = $conn->query("SELECT id FROM users LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
    }
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No user found in session or database']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'session_user_id' => $_SESSION['user_id'],
    'post_data' => $_POST
]);
?>
