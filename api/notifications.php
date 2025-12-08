<?php
/**
 * SwapIt Real-Time Notifications API
 * Handles all real-time notifications for user interactions
 * 
 * Features:
 * - New message notifications
 * - Request status updates
 * - Meeting reminders
 * - Transaction alerts
 * - Real-time updates
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');

require_once dirname(__DIR__) . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

class NotificationService {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all notifications for user
     */
    public function getNotifications($userId, $limit = 50, $unreadOnly = false) {
        $sql = "SELECT n.*, 
                       u.full_name as sender_name, 
                       u.avatar_url as sender_avatar
                FROM notifications n
                LEFT JOIN users u ON n.related_id = u.id AND n.related_type = 'user'
                WHERE n.user_id = ?";
        
        if ($unreadOnly) {
            $sql .= " AND n.is_read = 0";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $limit]);
        $notifications = $stmt->fetchAll();
        
        return [
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $this->getUnreadCount($userId)
        ];
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW() 
             WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$notificationId, $userId]);
        
        return ['success' => true];
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW() 
             WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        
        return ['success' => true];
    }
    
    /**
     * Create notification
     */
    public function createNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null, $actionUrl = null) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO notifications (user_id, type, title, message, related_id, related_type, action_url) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $type, $title, $message, $relatedId, $relatedType, $actionUrl]);
            
            return [
                'success' => true,
                'notification_id' => $this->conn->lastInsertId()
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get real-time updates (new messages, requests, etc)
     */
    public function getRealTimeUpdates($userId, $lastCheckTime) {
        $updates = [
            'new_messages' => 0,
            'new_requests' => 0,
            'new_notifications' => 0,
            'status_changes' => []
        ];
        
        // Check for new messages
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM messages 
             WHERE receiver_id = ? AND is_read = 0 AND created_at > ?"
        );
        $stmt->execute([$userId, $lastCheckTime]);
        $updates['new_messages'] = $stmt->fetch()['count'];
        
        // Check for new requests
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM borrow_requests 
             WHERE lender_id = ? AND status = 'pending' AND created_at > ?"
        );
        $stmt->execute([$userId, $lastCheckTime]);
        $updates['new_requests'] = $stmt->fetch()['count'];
        
        // Check for new notifications
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM notifications 
             WHERE user_id = ? AND is_read = 0 AND created_at > ?"
        );
        $stmt->execute([$userId, $lastCheckTime]);
        $updates['new_notifications'] = $stmt->fetch()['count'];
        
        // Check for request status changes
        $stmt = $this->conn->prepare(
            "SELECT br.id, br.status, br.updated_at, i.title as item_title
             FROM borrow_requests br
             JOIN items i ON br.item_id = i.id
             WHERE (br.borrower_id = ? OR br.lender_id = ?) 
             AND br.updated_at > ?
             ORDER BY br.updated_at DESC"
        );
        $stmt->execute([$userId, $userId, $lastCheckTime]);
        $updates['status_changes'] = $stmt->fetchAll();
        
        return [
            'success' => true,
            'updates' => $updates,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Update user online status
     */
    public function updateOnlineStatus($userId, $status = 'online') {
        $stmt = $this->conn->prepare(
            "INSERT INTO online_users (user_id, status, last_seen) 
             VALUES (?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE status = ?, last_seen = NOW()"
        );
        $stmt->execute([$userId, $status, $status]);
        
        return ['success' => true];
    }
    
    /**
     * Get online users
     */
    public function getOnlineUsers($userIds = []) {
        if (empty($userIds)) {
            $stmt = $this->conn->prepare(
                "SELECT u.id, u.full_name, u.avatar_url, ou.status, ou.last_seen
                 FROM online_users ou
                 JOIN users u ON ou.user_id = u.id
                 WHERE ou.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 AND ou.status IN ('online', 'away')"
            );
            $stmt->execute();
        } else {
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $stmt = $this->conn->prepare(
                "SELECT u.id, u.full_name, u.avatar_url, ou.status, ou.last_seen
                 FROM online_users ou
                 JOIN users u ON ou.user_id = u.id
                 WHERE ou.user_id IN ($placeholders)
                 AND ou.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
            );
            $stmt->execute($userIds);
        }
        
        return [
            'success' => true,
            'online_users' => $stmt->fetchAll()
        ];
    }
}

// Initialize service
$notificationService = new NotificationService($conn);

// Handle requests
try {
    switch ($action) {
        case 'get_notifications':
            $limit = min($_GET['limit'] ?? 50, 100);
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $result = $notificationService->getNotifications($userId, $limit, $unreadOnly);
            echo json_encode($result);
            break;
            
        case 'get_unread_count':
            $count = $notificationService->getUnreadCount($userId);
            echo json_encode(['success' => true, 'unread_count' => $count]);
            break;
            
        case 'mark_as_read':
            $notificationId = $_POST['notification_id'] ?? null;
            if (empty($notificationId)) {
                echo json_encode(['success' => false, 'error' => 'Notification ID required']);
                exit;
            }
            $result = $notificationService->markAsRead($notificationId, $userId);
            echo json_encode($result);
            break;
            
        case 'mark_all_read':
        case 'mark_all_as_read':
            $result = $notificationService->markAllAsRead($userId);
            echo json_encode($result);
            break;
            
        case 'get_realtime_updates':
            $lastCheckTime = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));
            $result = $notificationService->getRealTimeUpdates($userId, $lastCheckTime);
            echo json_encode($result);
            break;
            
        case 'update_status':
            $status = $_POST['status'] ?? 'online';
            $result = $notificationService->updateOnlineStatus($userId, $status);
            echo json_encode($result);
            break;
            
        case 'get_online_users':
            $userIds = isset($_GET['user_ids']) ? explode(',', $_GET['user_ids']) : [];
            $result = $notificationService->getOnlineUsers($userIds);
            echo json_encode($result);
            break;
            
        default:
            error_log("Invalid notification action: $action");
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log("Notification database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred. Please try again.',
        'debug' => isset($_GET['debug']) ? $e->getMessage() : null
    ]);
} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again.',
        'debug' => isset($_GET['debug']) ? $e->getMessage() : null
    ]);
}
?>
