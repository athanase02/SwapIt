<?php
/**
 * Test Messages API - Debug what's being returned
 */

session_start();
header('Content-Type: application/json');

// Set test user session
$_SESSION['user_id'] = 1; // Use first user ID

require_once __DIR__ . '/../config/db.php';

$results = [
    'test' => 'messages_api',
    'user_id' => $_SESSION['user_id'],
    'conversations' => [],
    'error' => null
];

try {
    // Get conversations
    $stmt = $conn->prepare("
        SELECT c.*, 
               u1.full_name as user1_name,
               u2.full_name as user2_name
        FROM conversations c
        JOIN users u1 ON c.user1_id = u1.id
        JOIN users u2 ON c.user2_id = u2.id
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY c.last_message_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results['conversations'] = $conversations;
    $results['conversation_count'] = count($conversations);
    
    // Get messages for first conversation
    if (!empty($conversations)) {
        $convId = $conversations[0]['id'];
        
        $stmt = $conn->prepare("
            SELECT m.*, 
                   sender.full_name as sender_name
            FROM messages m
            JOIN users sender ON m.sender_id = sender.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$convId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results['sample_messages'] = $messages;
        $results['message_count'] = count($messages);
    }

} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
