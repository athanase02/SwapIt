<?php
/**
 * SwapIt Requests API
 * Handles borrow requests, approvals, rejections, and meeting scheduling
 * 
 * Features:
 * - Create borrow requests
 * - Approve/reject requests
 * - Schedule meetings (online/offline)
 * - Update request status
 * - Track request history
 * 
 * @author SwapIt Team - Request management system
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
 * Request Logger
 */
class RequestLogger {
    private static $logFile = __DIR__ . '/../logs/requests.log';
    
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
 * Request Service
 */
class RequestService {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Create a borrow request
     */
    public function createRequest($data) {
        // Validate required fields
        $required = ['item_id', 'borrow_start_date', 'borrow_end_date', 'borrower_message'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Missing required field: $field"];
            }
        }
        
        // Get item and lender info
        $stmt = $this->conn->prepare(
            "SELECT i.*, u.id as lender_id, u.full_name as lender_name 
             FROM items i 
             JOIN users u ON i.user_id = u.id 
             WHERE i.id = ? AND i.status = 'available'"
        );
        $stmt->execute([$data['item_id']]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return ['success' => false, 'error' => 'Item not available'];
        }
        
        // Prevent user from borrowing their own item
        if ($item['lender_id'] == $data['borrower_id']) {
            return ['success' => false, 'error' => 'Cannot borrow your own item'];
        }
        
        // Calculate total price
        $startDate = new DateTime($data['borrow_start_date']);
        $endDate = new DateTime($data['borrow_end_date']);
        $days = $startDate->diff($endDate)->days + 1;
        $totalPrice = $item['price_per_day'] * $days;
        
        // Create request
        $stmt = $this->conn->prepare(
            "INSERT INTO borrow_requests 
             (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, 
              total_price, security_deposit, pickup_location, borrower_message) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $data['item_id'],
            $data['borrower_id'],
            $item['lender_id'],
            $data['borrow_start_date'],
            $data['borrow_end_date'],
            $totalPrice,
            $data['security_deposit'] ?? 0,
            $data['pickup_location'] ?? null,
            $data['borrower_message']
        ]);
        
        $requestId = $this->conn->lastInsertId();
        
        // Create notification for lender
        $this->createNotification(
            $item['lender_id'],
            'borrow_request',
            'New Borrow Request',
            "You have a new borrow request for {$item['title']}",
            $requestId
        );
        
        // Log activity
        $this->logActivity($data['borrower_id'], 'request_created', $requestId, 
            "Created borrow request for {$item['title']}");
        
        RequestLogger::log('request_created', 'Borrow request created', [
            'request_id' => $requestId,
            'item_id' => $data['item_id'],
            'borrower_id' => $data['borrower_id'],
            'lender_id' => $item['lender_id']
        ]);
        
        return [
            'success' => true,
            'request_id' => $requestId,
            'total_price' => $totalPrice,
            'message' => 'Request sent successfully'
        ];
    }
    
    /**
     * Accept a borrow request
     */
    public function acceptRequest($requestId, $lenderId, $notes = null) {
        // Verify lender owns this request
        $stmt = $this->conn->prepare(
            "SELECT br.*, i.title as item_title, i.user_id as item_owner,
                    b.full_name as borrower_name
             FROM borrow_requests br
             JOIN items i ON br.item_id = i.id
             JOIN users b ON br.borrower_id = b.id
             WHERE br.id = ? AND br.lender_id = ? AND br.status = 'pending'"
        );
        $stmt->execute([$requestId, $lenderId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found or already processed'];
        }
        
        $this->conn->beginTransaction();
        
        try {
            // Update request status
            $stmt = $this->conn->prepare(
                "UPDATE borrow_requests 
                 SET status = 'accepted', lender_notes = ?, updated_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->execute([$notes, $requestId]);
            
            // Update item status
            $stmt = $this->conn->prepare(
                "UPDATE items SET status = 'reserved' WHERE id = ?"
            );
            $stmt->execute([$request['item_id']]);
            
            $this->conn->commit();
            
            // Create notification for borrower
            $this->createNotification(
                $request['borrower_id'],
                'request_accepted',
                'Request Accepted!',
                "Your request for {$request['item_title']} has been accepted",
                $requestId
            );
            
            // Log activities
            $this->logActivity($lenderId, 'request_accepted', $requestId,
                "Accepted borrow request for {$request['item_title']}");
            $this->logActivity($request['borrower_id'], 'request_accepted', $requestId,
                "Your request for {$request['item_title']} was accepted");
            
            RequestLogger::log('request_accepted', 'Request accepted', [
                'request_id' => $requestId,
                'lender_id' => $lenderId
            ]);
            
            return [
                'success' => true,
                'message' => 'Request accepted successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            RequestLogger::log('error', 'Failed to accept request: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to accept request'];
        }
    }
    
    /**
     * Reject a borrow request
     */
    public function rejectRequest($requestId, $lenderId, $reason = null) {
        // Verify lender owns this request
        $stmt = $this->conn->prepare(
            "SELECT br.*, i.title as item_title, b.full_name as borrower_name
             FROM borrow_requests br
             JOIN items i ON br.item_id = i.id
             JOIN users b ON br.borrower_id = b.id
             WHERE br.id = ? AND br.lender_id = ? AND br.status = 'pending'"
        );
        $stmt->execute([$requestId, $lenderId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found or already processed'];
        }
        
        // Update request status
        $stmt = $this->conn->prepare(
            "UPDATE borrow_requests 
             SET status = 'rejected', cancellation_reason = ?, updated_at = NOW() 
             WHERE id = ?"
        );
        $stmt->execute([$reason, $requestId]);
        
        // Create notification for borrower
        $this->createNotification(
            $request['borrower_id'],
            'request_rejected',
            'Request Declined',
            "Your request for {$request['item_title']} was declined",
            $requestId
        );
        
        // Log activities
        $this->logActivity($lenderId, 'request_rejected', $requestId,
            "Rejected borrow request for {$request['item_title']}");
        $this->logActivity($request['borrower_id'], 'request_rejected', $requestId,
            "Your request for {$request['item_title']} was declined");
        
        RequestLogger::log('request_rejected', 'Request rejected', [
            'request_id' => $requestId,
            'lender_id' => $lenderId,
            'reason' => $reason
        ]);
        
        return [
            'success' => true,
            'message' => 'Request declined'
        ];
    }
    
    /**
     * Schedule a meeting for an accepted request
     */
    public function scheduleMeeting($requestId, $userId, $meetingData) {
        // Verify user is part of this request
        $stmt = $this->conn->prepare(
            "SELECT br.*, i.title as item_title 
             FROM borrow_requests br
             JOIN items i ON br.item_id = i.id
             WHERE br.id = ? AND (br.borrower_id = ? OR br.lender_id = ?) 
             AND br.status IN ('accepted', 'active')"
        );
        $stmt->execute([$requestId, $userId, $userId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found or unauthorized'];
        }
        
        // Insert meeting schedule
        $stmt = $this->conn->prepare(
            "INSERT INTO meeting_schedules 
             (borrow_request_id, scheduled_by, meeting_type, meeting_date, 
              meeting_location, meeting_link, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $requestId,
            $userId,
            $meetingData['type'],
            $meetingData['date'],
            $meetingData['location'] ?? null,
            $meetingData['link'] ?? null,
            $meetingData['notes'] ?? null
        ]);
        
        $meetingId = $this->conn->lastInsertId();
        
        // Notify the other party
        $otherUserId = ($request['borrower_id'] == $userId) ? 
            $request['lender_id'] : $request['borrower_id'];
        
        $meetingType = ucfirst($meetingData['type']);
        $this->createNotification(
            $otherUserId,
            'meeting_scheduled',
            'Meeting Scheduled',
            "A {$meetingType} meeting has been scheduled for {$request['item_title']}",
            $requestId
        );
        
        // Log activity
        $this->logActivity($userId, 'meeting_scheduled', $requestId,
            "Scheduled a {$meetingType} meeting");
        
        RequestLogger::log('meeting_scheduled', 'Meeting scheduled', [
            'request_id' => $requestId,
            'meeting_id' => $meetingId,
            'type' => $meetingData['type']
        ]);
        
        return [
            'success' => true,
            'meeting_id' => $meetingId,
            'message' => 'Meeting scheduled successfully'
        ];
    }
    
    /**
     * Get user's requests (as borrower or lender)
     */
    public function getUserRequests($userId, $role = 'all', $status = 'all') {
        $sql = "SELECT br.*, 
                       i.title as item_title, i.image_url as item_image, i.price_per_day,
                       borrower.full_name as borrower_name, borrower.avatar_url as borrower_avatar,
                       lender.full_name as lender_name, lender.avatar_url as lender_avatar,
                       CASE 
                           WHEN br.borrower_id = ? THEN 'sent'
                           WHEN br.lender_id = ? THEN 'received'
                       END as request_type,
                       CASE 
                           WHEN br.borrower_id = ? THEN lender.full_name
                           ELSE borrower.full_name
                       END as other_user_name,
                       CASE 
                           WHEN br.borrower_id = ? THEN lender.avatar_url
                           ELSE borrower.avatar_url
                       END as other_user_avatar
                FROM borrow_requests br
                JOIN items i ON br.item_id = i.id
                JOIN users borrower ON br.borrower_id = borrower.id
                JOIN users lender ON br.lender_id = lender.id
                WHERE 1=1";
        
        $params = [$userId, $userId, $userId, $userId];
        
        if ($role === 'borrower') {
            $sql .= " AND br.borrower_id = ?";
            $params[] = $userId;
        } elseif ($role === 'lender') {
            $sql .= " AND br.lender_id = ?";
            $params[] = $userId;
        } else {
            $sql .= " AND (br.borrower_id = ? OR br.lender_id = ?)";
            $params[] = $userId;
            $params[] = $userId;
        }
        
        if ($status !== 'all') {
            $sql .= " AND br.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY br.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $requests = $stmt->fetchAll();
        
        return [
            'success' => true,
            'requests' => $requests
        ];
    }
    
    /**
     * Get request details with meeting info
     */
    public function getRequestDetails($requestId, $userId) {
        $stmt = $this->conn->prepare(
            "SELECT br.*, 
                    i.title as item_title, i.description as item_description, 
                    i.image_url as item_image, i.price_per_day,
                    borrower.full_name as borrower_name, borrower.avatar_url as borrower_avatar,
                    borrower.email as borrower_email, borrower.phone as borrower_phone,
                    lender.full_name as lender_name, lender.avatar_url as lender_avatar,
                    lender.email as lender_email, lender.phone as lender_phone
             FROM borrow_requests br
             JOIN items i ON br.item_id = i.id
             JOIN users borrower ON br.borrower_id = borrower.id
             JOIN users lender ON br.lender_id = lender.id
             WHERE br.id = ? AND (br.borrower_id = ? OR br.lender_id = ?)"
        );
        $stmt->execute([$requestId, $userId, $userId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found'];
        }
        
        // Get meeting schedules
        $stmt = $this->conn->prepare(
            "SELECT ms.*, u.full_name as scheduled_by_name
             FROM meeting_schedules ms
             JOIN users u ON ms.scheduled_by = u.id
             WHERE ms.borrow_request_id = ?
             ORDER BY ms.created_at DESC"
        );
        $stmt->execute([$requestId]);
        $meetings = $stmt->fetchAll();
        
        return [
            'success' => true,
            'request' => $request,
            'meetings' => $meetings
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
    
    /**
     * Log activity
     */
    private function logActivity($userId, $activityType, $relatedId, $description) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO user_activities (user_id, activity_type, related_id, description) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $activityType, $relatedId, $description]);
        } catch (Exception $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}

// Initialize service
$requestService = new RequestService($conn);

// Handle requests
try {
    switch ($action) {
        case 'create_request':
            $data = [
                'item_id' => $_POST['item_id'] ?? null,
                'borrower_id' => $userId,
                'borrow_start_date' => $_POST['start_date'] ?? null,
                'borrow_end_date' => $_POST['end_date'] ?? null,
                'borrower_message' => $_POST['message'] ?? '',
                'security_deposit' => $_POST['security_deposit'] ?? 0,
                'pickup_location' => $_POST['pickup_location'] ?? null
            ];
            
            $result = $requestService->createRequest($data);
            echo json_encode($result);
            break;
            
        case 'accept_request':
            $requestId = $_POST['request_id'] ?? null;
            $notes = $_POST['notes'] ?? null;
            
            if (empty($requestId)) {
                echo json_encode(['success' => false, 'error' => 'Request ID required']);
                exit;
            }
            
            $result = $requestService->acceptRequest($requestId, $userId, $notes);
            echo json_encode($result);
            break;
            
        case 'reject_request':
            $requestId = $_POST['request_id'] ?? null;
            $reason = $_POST['reason'] ?? null;
            
            if (empty($requestId)) {
                echo json_encode(['success' => false, 'error' => 'Request ID required']);
                exit;
            }
            
            $result = $requestService->rejectRequest($requestId, $userId, $reason);
            echo json_encode($result);
            break;
            
        case 'schedule_meeting':
            $requestId = $_POST['request_id'] ?? null;
            $meetingData = [
                'type' => $_POST['meeting_type'] ?? 'offline',
                'date' => $_POST['meeting_date'] ?? null,
                'location' => $_POST['meeting_location'] ?? null,
                'link' => $_POST['meeting_link'] ?? null,
                'notes' => $_POST['notes'] ?? null
            ];
            
            if (empty($requestId) || empty($meetingData['date'])) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            
            $result = $requestService->scheduleMeeting($requestId, $userId, $meetingData);
            echo json_encode($result);
            break;
            
        case 'get_my_requests':
            $role = $_GET['role'] ?? 'all';
            $status = $_GET['status'] ?? 'all';
            
            $result = $requestService->getUserRequests($userId, $role, $status);
            echo json_encode($result);
            break;
            
        case 'get_request_details':
            $requestId = $_GET['request_id'] ?? null;
            
            if (empty($requestId)) {
                echo json_encode(['success' => false, 'error' => 'Request ID required']);
                exit;
            }
            
            $result = $requestService->getRequestDetails($requestId, $userId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    RequestLogger::log('error', 'Exception occurred: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again.'
    ]);
}
?>
