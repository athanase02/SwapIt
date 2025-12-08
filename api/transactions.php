<?php
/**
 * SwapIt Transaction Management API
 * Handles transaction confirmations, pickup/return tracking, and history
 * @version 1.0
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

class TransactionService {
    private $conn;
    private $userId;
    
    public function __construct($connection, $userId) {
        $this->conn = $connection;
        $this->userId = $userId;
    }
    
    /**
     * Confirm item pickup - borrower confirms they received the item
     */
    public function confirmPickup($requestId) {
        try {
            // Get request details
            $stmt = $this->conn->prepare("
                SELECT br.*, u1.username as borrower_name, u2.username as lender_name,
                       i.title as item_title
                FROM borrow_requests br
                JOIN users u1 ON br.borrower_id = u1.id
                JOIN users u2 ON br.lender_id = u2.id
                JOIN items i ON br.item_id = i.id
                WHERE br.id = ? AND br.status = 'scheduled'
            ");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            
            if (!$request) {
                return ['success' => false, 'error' => 'Request not found or not scheduled'];
            }
            
            // Only borrower or lender can confirm
            if ($this->userId != $request['borrower_id'] && $this->userId != $request['lender_id']) {
                return ['success' => false, 'error' => 'Unauthorized'];
            }
            
            // Update request status
            $stmt = $this->conn->prepare("
                UPDATE borrow_requests 
                SET status = 'active', 
                    start_date = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            
            // Create transaction record
            $stmt = $this->conn->prepare("
                INSERT INTO transaction_history 
                (request_id, borrower_id, lender_id, item_id, action_type, performed_by, notes, created_at)
                VALUES (?, ?, ?, ?, 'pickup_confirmed', ?, 'Item successfully picked up', NOW())
            ");
            $stmt->bind_param("iiiii", 
                $requestId, 
                $request['borrower_id'], 
                $request['lender_id'], 
                $request['item_id'],
                $this->userId
            );
            $stmt->execute();
            
            // Send notifications
            $notifUserId = ($this->userId == $request['borrower_id']) 
                ? $request['lender_id'] 
                : $request['borrower_id'];
            
            $this->sendNotification(
                $notifUserId,
                'Item Pickup Confirmed',
                $request['item_title'] . ' has been picked up',
                'pickup_confirmed',
                '/pages/requests.html?id=' . $requestId
            );
            
            // Log activity
            $this->logActivity('pickup_confirmed', $requestId);
            
            return [
                'success' => true, 
                'message' => 'Pickup confirmed successfully',
                'request' => $request
            ];
            
        } catch (Exception $e) {
            error_log("Error confirming pickup: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to confirm pickup'];
        }
    }
    
    /**
     * Confirm item return - lender confirms they received the item back
     */
    public function confirmReturn($requestId, $condition = 'good') {
        try {
            // Get request details
            $stmt = $this->conn->prepare("
                SELECT br.*, u1.username as borrower_name, u2.username as lender_name,
                       i.title as item_title
                FROM borrow_requests br
                JOIN users u1 ON br.borrower_id = u1.id
                JOIN users u2 ON br.lender_id = u2.id
                JOIN items i ON br.item_id = i.id
                WHERE br.id = ? AND br.status = 'active'
            ");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            
            if (!$request) {
                return ['success' => false, 'error' => 'Request not found or not active'];
            }
            
            // Only borrower or lender can confirm
            if ($this->userId != $request['borrower_id'] && $this->userId != $request['lender_id']) {
                return ['success' => false, 'error' => 'Unauthorized'];
            }
            
            // Update request status
            $stmt = $this->conn->prepare("
                UPDATE borrow_requests 
                SET status = 'completed', 
                    end_date = NOW(),
                    return_condition = ?
                WHERE id = ?
            ");
            $stmt->bind_param("si", $condition, $requestId);
            $stmt->execute();
            
            // Create transaction record
            $notes = "Item returned in " . $condition . " condition";
            $stmt = $this->conn->prepare("
                INSERT INTO transaction_history 
                (request_id, borrower_id, lender_id, item_id, action_type, performed_by, notes, created_at)
                VALUES (?, ?, ?, ?, 'return_confirmed', ?, ?, NOW())
            ");
            $stmt->bind_param("iiiiss", 
                $requestId, 
                $request['borrower_id'], 
                $request['lender_id'], 
                $request['item_id'],
                $this->userId,
                $notes
            );
            $stmt->execute();
            
            // Send notifications
            $notifUserId = ($this->userId == $request['borrower_id']) 
                ? $request['lender_id'] 
                : $request['borrower_id'];
            
            $this->sendNotification(
                $notifUserId,
                'Item Return Confirmed',
                $request['item_title'] . ' has been returned',
                'return_confirmed',
                '/pages/requests.html?id=' . $requestId
            );
            
            // Log activity
            $this->logActivity('return_confirmed', $requestId);
            
            return [
                'success' => true, 
                'message' => 'Return confirmed successfully',
                'request' => $request
            ];
            
        } catch (Exception $e) {
            error_log("Error confirming return: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to confirm return'];
        }
    }
    
    /**
     * Get transaction history for a request
     */
    public function getTransactionHistory($requestId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT th.*, u.username as performed_by_name, u.avatar as performed_by_avatar
                FROM transaction_history th
                JOIN users u ON th.performed_by = u.id
                WHERE th.request_id = ?
                ORDER BY th.created_at ASC
            ");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            return ['success' => true, 'history' => $history];
            
        } catch (Exception $e) {
            error_log("Error getting transaction history: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to get history'];
        }
    }
    
    /**
     * Get all transactions for current user
     */
    public function getUserTransactions($status = 'all') {
        try {
            $sql = "
                SELECT DISTINCT th.*, 
                       br.status as request_status,
                       i.title as item_title, i.image_url as item_image,
                       u1.username as borrower_name, u1.avatar as borrower_avatar,
                       u2.username as lender_name, u2.avatar as lender_avatar,
                       CASE 
                           WHEN br.borrower_id = ? THEN 'borrower'
                           WHEN br.lender_id = ? THEN 'lender'
                       END as user_role
                FROM transaction_history th
                JOIN borrow_requests br ON th.request_id = br.id
                JOIN items i ON th.item_id = i.id
                JOIN users u1 ON br.borrower_id = u1.id
                JOIN users u2 ON br.lender_id = u2.id
                WHERE (br.borrower_id = ? OR br.lender_id = ?)
            ";
            
            if ($status != 'all') {
                $sql .= " AND br.status = ?";
            }
            
            $sql .= " ORDER BY th.created_at DESC LIMIT 100";
            
            $stmt = $this->conn->prepare($sql);
            
            if ($status != 'all') {
                $stmt->bind_param("iiiis", $this->userId, $this->userId, $this->userId, $this->userId, $status);
            } else {
                $stmt->bind_param("iiii", $this->userId, $this->userId, $this->userId, $this->userId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            
            return ['success' => true, 'transactions' => $transactions];
            
        } catch (Exception $e) {
            error_log("Error getting user transactions: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to get transactions'];
        }
    }
    
    /**
     * Report an issue with a transaction
     */
    public function reportIssue($requestId, $issueType, $description) {
        try {
            // Get request details
            $stmt = $this->conn->prepare("
                SELECT br.*, i.title as item_title
                FROM borrow_requests br
                JOIN items i ON br.item_id = i.id
                WHERE br.id = ?
            ");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            
            if (!$request) {
                return ['success' => false, 'error' => 'Request not found'];
            }
            
            // Check authorization
            if ($this->userId != $request['borrower_id'] && $this->userId != $request['lender_id']) {
                return ['success' => false, 'error' => 'Unauthorized'];
            }
            
            // Create issue record
            $notes = "Issue: " . $issueType . " - " . $description;
            $stmt = $this->conn->prepare("
                INSERT INTO transaction_history 
                (request_id, borrower_id, lender_id, item_id, action_type, performed_by, notes, created_at)
                VALUES (?, ?, ?, ?, 'issue_reported', ?, ?, NOW())
            ");
            $stmt->bind_param("iiiiss", 
                $requestId, 
                $request['borrower_id'], 
                $request['lender_id'], 
                $request['item_id'],
                $this->userId,
                $notes
            );
            $stmt->execute();
            
            // Notify the other party
            $notifUserId = ($this->userId == $request['borrower_id']) 
                ? $request['lender_id'] 
                : $request['borrower_id'];
            
            $this->sendNotification(
                $notifUserId,
                'Transaction Issue Reported',
                'An issue was reported for ' . $request['item_title'],
                'issue_reported',
                '/pages/requests.html?id=' . $requestId
            );
            
            return ['success' => true, 'message' => 'Issue reported successfully'];
            
        } catch (Exception $e) {
            error_log("Error reporting issue: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to report issue'];
        }
    }
    
    /**
     * Send notification helper
     */
    private function sendNotification($userId, $title, $message, $type, $actionUrl) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, action_url, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("issss", $userId, $title, $message, $type, $actionUrl);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error sending notification: " . $e->getMessage());
        }
    }
    
    /**
     * Log user activity
     */
    private function logActivity($actionType, $requestId) {
        try {
            $details = json_encode(['request_id' => $requestId]);
            $stmt = $this->conn->prepare("
                INSERT INTO user_activities (user_id, activity_type, activity_details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iss", $this->userId, $actionType, $details);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}

// Initialize service
$service = new TransactionService($conn, $userId);

// Handle actions
switch ($action) {
    case 'confirm_pickup':
        $requestId = $_POST['request_id'] ?? 0;
        if (!$requestId) {
            echo json_encode(['success' => false, 'error' => 'Request ID required']);
            break;
        }
        $result = $service->confirmPickup($requestId);
        echo json_encode($result);
        break;
        
    case 'confirm_return':
        $requestId = $_POST['request_id'] ?? 0;
        $condition = $_POST['condition'] ?? 'good';
        if (!$requestId) {
            echo json_encode(['success' => false, 'error' => 'Request ID required']);
            break;
        }
        $result = $service->confirmReturn($requestId, $condition);
        echo json_encode($result);
        break;
        
    case 'get_history':
        $requestId = $_GET['request_id'] ?? 0;
        if (!$requestId) {
            echo json_encode(['success' => false, 'error' => 'Request ID required']);
            break;
        }
        $result = $service->getTransactionHistory($requestId);
        echo json_encode($result);
        break;
        
    case 'get_my_transactions':
        $status = $_GET['status'] ?? 'all';
        $result = $service->getUserTransactions($status);
        echo json_encode($result);
        break;
        
    case 'report_issue':
        $requestId = $_POST['request_id'] ?? 0;
        $issueType = $_POST['issue_type'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (!$requestId || !$issueType || !$description) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }
        
        $result = $service->reportIssue($requestId, $issueType, $description);
        echo json_encode($result);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

$conn->close();
?>
