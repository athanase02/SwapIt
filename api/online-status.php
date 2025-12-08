<?php
/**
 * SwapIt Online Status API
 * Real-time user online status tracking
 * 
 * Features:
 * - Track user online status
 * - Update last activity time
 * - Get list of online users
 * - Real-time presence system
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

class OnlineStatusService {
    private $conn;
    private $onlineThreshold = 60; // 60 seconds
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Update user's online status
     */
    public function updateStatus($userId) {
        try {
            // Update or create status record
            $stmt = $this->conn->prepare(
                "INSERT INTO user_online_status (user_id, last_activity_at) 
                 VALUES (?, NOW()) 
                 ON DUPLICATE KEY UPDATE last_activity_at = NOW()"
            );
            $stmt->execute([$userId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Update status error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get list of online users
     */
    public function getOnlineUsers() {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.full_name, u.avatar_url, uos.last_activity_at
             FROM user_online_status uos
             JOIN users u ON uos.user_id = u.id
             WHERE uos.last_activity_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
             ORDER BY u.full_name"
        );
        $stmt->execute([$this->onlineThreshold]);
        $users = $stmt->fetchAll();
        
        return [
            'success' => true,
            'online_users' => $users,
            'count' => count($users)
        ];
    }
    
    /**
     * Check if specific user is online
     */
    public function isUserOnline($targetUserId) {
        $stmt = $this->conn->prepare(
            "SELECT last_activity_at >= DATE_SUB(NOW(), INTERVAL ? SECOND) as is_online
             FROM user_online_status
             WHERE user_id = ?"
        );
        $stmt->execute([$this->onlineThreshold, $targetUserId]);
        $result = $stmt->fetch();
        
        return [
            'success' => true,
            'is_online' => $result ? (bool)$result['is_online'] : false
        ];
    }
    
    /**
     * Get online status for multiple users
     */
    public function getMultipleUserStatus($userIds) {
        if (empty($userIds)) {
            return ['success' => true, 'statuses' => []];
        }
        
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        $params = array_merge([$this->onlineThreshold], $userIds);
        
        $stmt = $this->conn->prepare(
            "SELECT user_id, 
                    last_activity_at >= DATE_SUB(NOW(), INTERVAL ? SECOND) as is_online,
                    last_activity_at
             FROM user_online_status
             WHERE user_id IN ($placeholders)"
        );
        $stmt->execute($params);
        $statuses = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'success' => true,
            'statuses' => $statuses
        ];
    }
}

// Initialize service
$statusService = new OnlineStatusService($conn);

// Handle requests
try {
    switch ($action) {
        case 'update':
            // Update current user's online status
            $result = $statusService->updateStatus($userId);
            echo json_encode($result);
            break;
            
        case 'get_online_users':
            // Get all online users
            $result = $statusService->getOnlineUsers();
            echo json_encode($result);
            break;
            
        case 'check_user':
            // Check if specific user is online
            $targetUserId = $_GET['user_id'] ?? null;
            if (!$targetUserId) {
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }
            
            $result = $statusService->isUserOnline($targetUserId);
            echo json_encode($result);
            break;
            
        case 'check_multiple':
            // Check status for multiple users
            $userIdsParam = $_GET['user_ids'] ?? $_POST['user_ids'] ?? '';
            $userIds = is_array($userIdsParam) ? $userIdsParam : explode(',', $userIdsParam);
            $userIds = array_filter(array_map('intval', $userIds));
            
            $result = $statusService->getMultipleUserStatus($userIds);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Online status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again.'
    ]);
}
