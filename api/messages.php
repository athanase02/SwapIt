<?php
/**
 * SwapIt Messaging API
 * Real-time messaging between users for item borrowing/lending coordination
 * 
 * Features:
 * - Send messages between users
 * - Get conversation history
 * - Real-time message polling
 * - Mark messages as read
 * 
 * @author SwapIt Team - Core messaging architecture
 * @version 1.0
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');

require_once dirname(__DIR__) . '/config/db.php';

// Security: Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

/**
 * Security Logger
 */
class MessageLogger {
    private static $logFile = __DIR__ . '/../logs/messages.log';
    
    public static function log($event, $message, $context = []) {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $userId = $_SESSION['user_id'] ?? 'guest';
        
        $logEntry = sprintf(
            "[%s] USER: %s | EVENT: %s | MESSAGE: %s | CONTEXT: %s\n",
            $timestamp,
            $userId,
            strtoupper($event),
            $message,
            json_encode($context)
        );
        
        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Messaging Service
 */
class MessagingService {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get or create conversation between two users
     */
    public function getOrCreateConversation($user1Id, $user2Id, $itemId = null) {
        // Ensure consistent ordering of user IDs
        if ($user1Id > $user2Id) {
            $temp = $user1Id;
            $user1Id = $user2Id;
            $user2Id = $temp;
        }
        
        // Check if conversation exists
        $stmt = $this->conn->prepare(
            "SELECT id FROM conversations 
             WHERE user1_id = ? AND user2_id = ?"
        );
        $stmt->execute([$user1Id, $user2Id]);
        $conversation = $stmt->fetch();
        
        if ($conversation) {
            return $conversation['id'];
        }
        
        // Create new conversation
        $stmt = $this->conn->prepare(
            "INSERT INTO conversations (user1_id, user2_id, item_id) 
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$user1Id, $user2Id, $itemId]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Send a message
     */
    public function sendMessage($senderId, $receiverId, $messageText, $itemId = null) {
        $conversationId = $this->getOrCreateConversation($senderId, $receiverId, $itemId);
        
        $stmt = $this->conn->prepare(
            "INSERT INTO messages (conversation_id, sender_id, receiver_id, message_text, item_id) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$conversationId, $senderId, $receiverId, $messageText, $itemId]);
        
        // Update conversation last message time
        $stmt = $this->conn->prepare(
            "UPDATE conversations SET last_message_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$conversationId]);
        
        // Create notification for receiver
        $this->createNotification($receiverId, 'new_message', 'New Message', 
            'You have a new message', $conversationId);
        
        MessageLogger::log('message_sent', 'Message sent successfully', [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId
        ]);
        
        return [
            'success' => true,
            'message_id' => $this->conn->lastInsertId(),
            'conversation_id' => $conversationId
        ];
    }
    
    /**
     * Get conversation messages
     */
    public function getMessages($conversationId, $userId, $limit = 50, $offset = 0) {
        // Verify user is part of conversation
        $stmt = $this->conn->prepare(
            "SELECT user1_id, user2_id FROM conversations WHERE id = ?"
        );
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch();
        
        if (!$conversation || 
            ($conversation['user1_id'] != $userId && $conversation['user2_id'] != $userId)) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }
        
        // Get messages
        $stmt = $this->conn->prepare(
            "SELECT m.*, 
                    sender.full_name as sender_name, 
                    sender.avatar_url as sender_avatar,
                    receiver.full_name as receiver_name
             FROM messages m
             JOIN users sender ON m.sender_id = sender.id
             JOIN users receiver ON m.receiver_id = receiver.id
             WHERE m.conversation_id = ?
             ORDER BY m.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$conversationId, $limit, $offset]);
        $messages = $stmt->fetchAll();
        
        // Mark unread messages as read
        $this->markMessagesAsRead($conversationId, $userId);
        
        return [
            'success' => true,
            'messages' => array_reverse($messages)
        ];
    }
    
    /**
     * Get all conversations for a user
     */
    public function getUserConversations($userId) {
        $stmt = $this->conn->prepare(
            "SELECT c.*, 
                    CASE 
                        WHEN c.user1_id = ? THEN u2.full_name
                        ELSE u1.full_name
                    END as other_user_name,
                    CASE 
                        WHEN c.user1_id = ? THEN u2.avatar_url
                        ELSE u1.avatar_url
                    END as other_user_avatar,
                    CASE 
                        WHEN c.user1_id = ? THEN c.user2_id
                        ELSE c.user1_id
                    END as other_user_id,
                    (SELECT COUNT(*) FROM messages 
                     WHERE conversation_id = c.id 
                       AND receiver_id = ? 
                       AND is_read = 0) as unread_count,
                    (SELECT message_text FROM messages 
                     WHERE conversation_id = c.id 
                     ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages 
                     WHERE conversation_id = c.id 
                     ORDER BY created_at DESC LIMIT 1) as last_message_time
             FROM conversations c
             JOIN users u1 ON c.user1_id = u1.id
             JOIN users u2 ON c.user2_id = u2.id
             WHERE c.user1_id = ? OR c.user2_id = ?
             ORDER BY c.last_message_at DESC"
        );
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
        $conversations = $stmt->fetchAll();
        
        return [
            'success' => true,
            'conversations' => $conversations
        ];
    }
    
    /**
     * Mark messages as read
     */
    public function markMessagesAsRead($conversationId, $userId) {
        $stmt = $this->conn->prepare(
            "UPDATE messages 
             SET is_read = 1, read_at = NOW() 
             WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0"
        );
        return $stmt->execute([$conversationId, $userId]);
    }
    
    /**
     * Get unread message count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM messages 
             WHERE receiver_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return [
            'success' => true,
            'unread_count' => $result['count']
        ];
    }
    
    /**
     * Create notification
     */
    private function createNotification($userId, $type, $title, $message, $relatedId = null) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO notifications (user_id, type, title, message, related_id) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $type, $title, $message, $relatedId]);
        } catch (Exception $e) {
            error_log("Notification creation failed: " . $e->getMessage());
        }
    }
}

// Initialize service
$messagingService = new MessagingService($conn);

// Handle requests
try {
    switch ($action) {
        case 'send_message':
            $receiverId = $_POST['receiver_id'] ?? null;
            $messageText = trim($_POST['message'] ?? '');
            $itemId = $_POST['item_id'] ?? null;
            
            if (empty($receiverId) || empty($messageText)) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            
            if (strlen($messageText) > 5000) {
                echo json_encode(['success' => false, 'error' => 'Message too long (max 5000 characters)']);
                exit;
            }
            
            $result = $messagingService->sendMessage($userId, $receiverId, $messageText, $itemId);
            echo json_encode($result);
            break;
            
        case 'get_messages':
            $conversationId = $_GET['conversation_id'] ?? null;
            $limit = min($_GET['limit'] ?? 50, 100);
            $offset = $_GET['offset'] ?? 0;
            
            if (empty($conversationId)) {
                echo json_encode(['success' => false, 'error' => 'Conversation ID required']);
                exit;
            }
            
            $result = $messagingService->getMessages($conversationId, $userId, $limit, $offset);
            echo json_encode($result);
            break;
            
        case 'get_conversations':
            $result = $messagingService->getUserConversations($userId);
            echo json_encode($result);
            break;
            
        case 'mark_as_read':
            $conversationId = $_POST['conversation_id'] ?? null;
            
            if (empty($conversationId)) {
                echo json_encode(['success' => false, 'error' => 'Conversation ID required']);
                exit;
            }
            
            $messagingService->markMessagesAsRead($conversationId, $userId);
            echo json_encode(['success' => true]);
            break;
            
        case 'get_unread_count':
            $result = $messagingService->getUnreadCount($userId);
            echo json_encode($result);
            break;
            
        case 'start_conversation':
            $otherUserId = $_POST['user_id'] ?? null;
            $itemId = $_POST['item_id'] ?? null;
            
            if (empty($otherUserId)) {
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }
            
            $conversationId = $messagingService->getOrCreateConversation($userId, $otherUserId, $itemId);
            echo json_encode(['success' => true, 'conversation_id' => $conversationId]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    MessageLogger::log('error', 'Exception occurred: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again.'
    ]);
}
?>
