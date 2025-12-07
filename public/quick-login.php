<?php
/**
 * Quick Login for Testing
 * Logs in as the first user in the database
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

try {
    // Get first user
    $stmt = $conn->query("SELECT id, email, full_name FROM users ORDER BY id ASC LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['authenticated'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged in successfully',
            'user' => $user,
            'redirect' => '/SwapIt/public/pages/messages.html'
        ], JSON_PRETTY_PRINT);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No users found in database'
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
