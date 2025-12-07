<?php
/**
 * SwapIt Rating & Review API
 * Handles user ratings and reviews with automatic profile updates
 * 
 * Features:
 * - Rate users after completed transactions
 * - Leave reviews with comments
 * - Automatic profile rating updates
 * - Review moderation
 * - Rating statistics
 * 
 * @author SwapIt Team - Rating system
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
 * Rating Logger
 */
class RatingLogger {
    private static $logFile = __DIR__ . '/../logs/ratings.log';
    
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
 * Rating Service
 */
class RatingService {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Submit a review/rating
     */
    public function submitReview($data) {
        // Validate required fields
        $required = ['reviewed_user_id', 'borrow_request_id', 'rating', 'review_type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return ['success' => false, 'error' => "Missing required field: $field"];
            }
        }
        
        // Validate rating range
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            return ['success' => false, 'error' => 'Rating must be between 1 and 5'];
        }
        
        // Verify the borrow request exists and user is authorized
        $stmt = $this->conn->prepare(
            "SELECT * FROM borrow_requests 
             WHERE id = ? AND status = 'completed' 
             AND (borrower_id = ? OR lender_id = ?)"
        );
        $stmt->execute([$data['borrow_request_id'], $data['reviewer_id'], $data['reviewer_id']]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return ['success' => false, 'error' => 'Request not found or not completed'];
        }
        
        // Verify review type matches user role
        if ($data['review_type'] === 'borrower_to_lender' && $request['borrower_id'] != $data['reviewer_id']) {
            return ['success' => false, 'error' => 'Invalid review type for your role'];
        }
        if ($data['review_type'] === 'lender_to_borrower' && $request['lender_id'] != $data['reviewer_id']) {
            return ['success' => false, 'error' => 'Invalid review type for your role'];
        }
        
        // Check if review already exists
        $stmt = $this->conn->prepare(
            "SELECT id FROM reviews 
             WHERE reviewer_id = ? AND borrow_request_id = ? AND review_type = ?"
        );
        $stmt->execute([$data['reviewer_id'], $data['borrow_request_id'], $data['review_type']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'You have already reviewed this transaction'];
        }
        
        $this->conn->beginTransaction();
        
        try {
            // Insert review
            $stmt = $this->conn->prepare(
                "INSERT INTO reviews 
                 (reviewer_id, reviewed_user_id, borrow_request_id, rating, review_type, 
                  title, comment, is_anonymous) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $stmt->execute([
                $data['reviewer_id'],
                $data['reviewed_user_id'],
                $data['borrow_request_id'],
                $data['rating'],
                $data['review_type'],
                $data['title'] ?? null,
                $data['comment'] ?? null,
                $data['is_anonymous'] ?? 0
            ]);
            
            $reviewId = $this->conn->lastInsertId();
            
            // Update user's profile rating
            $this->updateUserRating($data['reviewed_user_id']);
            
            // Update profile statistics
            if ($data['review_type'] === 'lender_to_borrower') {
                $stmt = $this->conn->prepare(
                    "UPDATE profiles SET total_items_borrowed = total_items_borrowed + 1 
                     WHERE user_id = ?"
                );
                $stmt->execute([$data['reviewed_user_id']]);
            } else {
                $stmt = $this->conn->prepare(
                    "UPDATE profiles SET total_items_lent = total_items_lent + 1 
                     WHERE user_id = ?"
                );
                $stmt->execute([$data['reviewed_user_id']]);
            }
            
            $this->conn->commit();
            
            // Create notification
            $this->createNotification(
                $data['reviewed_user_id'],
                'new_review',
                'New Review Received',
                "You received a {$data['rating']}-star review",
                $reviewId
            );
            
            // Log activity
            $this->logActivity($data['reviewer_id'], 'review_submitted', $reviewId,
                "Submitted a {$data['rating']}-star review");
            
            RatingLogger::log('review_submitted', 'Review submitted successfully', [
                'review_id' => $reviewId,
                'reviewer_id' => $data['reviewer_id'],
                'reviewed_user_id' => $data['reviewed_user_id'],
                'rating' => $data['rating']
            ]);
            
            return [
                'success' => true,
                'review_id' => $reviewId,
                'message' => 'Review submitted successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            RatingLogger::log('error', 'Failed to submit review: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to submit review'];
        }
    }
    
    /**
     * Update user's average rating
     */
    private function updateUserRating($userId) {
        $stmt = $this->conn->prepare(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
             FROM reviews 
             WHERE reviewed_user_id = ?"
        );
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();
        
        $avgRating = round($stats['avg_rating'], 2);
        
        $stmt = $this->conn->prepare(
            "UPDATE profiles 
             SET rating_average = ?, total_reviews = ? 
             WHERE user_id = ?"
        );
        $stmt->execute([$avgRating, $stats['total_reviews'], $userId]);
        
        RatingLogger::log('rating_updated', 'User rating updated', [
            'user_id' => $userId,
            'new_rating' => $avgRating,
            'total_reviews' => $stats['total_reviews']
        ]);
    }
    
    /**
     * Get user's reviews
     */
    public function getUserReviews($userId, $limit = 20, $offset = 0) {
        $stmt = $this->conn->prepare(
            "SELECT r.*, 
                    reviewer.full_name as reviewer_name, 
                    reviewer.avatar_url as reviewer_avatar,
                    CASE WHEN r.is_anonymous = 1 THEN 'Anonymous' 
                         ELSE reviewer.full_name END as display_name
             FROM reviews r
             LEFT JOIN users reviewer ON r.reviewer_id = reviewer.id
             WHERE r.reviewed_user_id = ?
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$userId, $limit, $offset]);
        $reviews = $stmt->fetchAll();
        
        // Get rating distribution
        $stmt = $this->conn->prepare(
            "SELECT rating, COUNT(*) as count
             FROM reviews
             WHERE reviewed_user_id = ?
             GROUP BY rating
             ORDER BY rating DESC"
        );
        $stmt->execute([$userId]);
        $distribution = $stmt->fetchAll();
        
        return [
            'success' => true,
            'reviews' => $reviews,
            'distribution' => $distribution
        ];
    }
    
    /**
     * Get reviews for a specific request
     */
    public function getRequestReviews($requestId) {
        $stmt = $this->conn->prepare(
            "SELECT r.*, 
                    reviewer.full_name as reviewer_name, 
                    reviewer.avatar_url as reviewer_avatar,
                    reviewed.full_name as reviewed_user_name
             FROM reviews r
             JOIN users reviewer ON r.reviewer_id = reviewer.id
             JOIN users reviewed ON r.reviewed_user_id = reviewed.id
             WHERE r.borrow_request_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$requestId]);
        $reviews = $stmt->fetchAll();
        
        return [
            'success' => true,
            'reviews' => $reviews
        ];
    }
    
    /**
     * Check if user can review a request
     */
    public function canReview($userId, $requestId) {
        // Check if request is completed
        $stmt = $this->conn->prepare(
            "SELECT borrower_id, lender_id FROM borrow_requests 
             WHERE id = ? AND status = 'completed'"
        );
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return ['success' => false, 'can_review' => false, 'reason' => 'Request not completed'];
        }
        
        // Determine user's role and review type
        $reviewType = null;
        $reviewedUserId = null;
        
        if ($request['borrower_id'] == $userId) {
            $reviewType = 'borrower_to_lender';
            $reviewedUserId = $request['lender_id'];
        } elseif ($request['lender_id'] == $userId) {
            $reviewType = 'lender_to_borrower';
            $reviewedUserId = $request['borrower_id'];
        } else {
            return ['success' => false, 'can_review' => false, 'reason' => 'Not part of this transaction'];
        }
        
        // Check if already reviewed
        $stmt = $this->conn->prepare(
            "SELECT id FROM reviews 
             WHERE reviewer_id = ? AND borrow_request_id = ? AND review_type = ?"
        );
        $stmt->execute([$userId, $requestId, $reviewType]);
        
        if ($stmt->fetch()) {
            return ['success' => true, 'can_review' => false, 'reason' => 'Already reviewed'];
        }
        
        return [
            'success' => true,
            'can_review' => true,
            'review_type' => $reviewType,
            'reviewed_user_id' => $reviewedUserId
        ];
    }
    
    /**
     * Get user's rating statistics
     */
    public function getUserRatingStats($userId) {
        $stmt = $this->conn->prepare(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
             FROM reviews
             WHERE reviewed_user_id = ?"
        );
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();
        
        return [
            'success' => true,
            'stats' => $stats
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
$ratingService = new RatingService($conn);

// Handle requests
try {
    switch ($action) {
        case 'submit_review':
            $data = [
                'reviewer_id' => $userId,
                'reviewed_user_id' => $_POST['reviewed_user_id'] ?? null,
                'borrow_request_id' => $_POST['request_id'] ?? null,
                'rating' => $_POST['rating'] ?? null,
                'review_type' => $_POST['review_type'] ?? null,
                'title' => $_POST['title'] ?? null,
                'comment' => $_POST['comment'] ?? null,
                'is_anonymous' => $_POST['is_anonymous'] ?? 0
            ];
            
            $result = $ratingService->submitReview($data);
            echo json_encode($result);
            break;
            
        case 'get_user_reviews':
            $targetUserId = $_GET['user_id'] ?? $userId;
            $limit = min($_GET['limit'] ?? 20, 100);
            $offset = $_GET['offset'] ?? 0;
            
            $result = $ratingService->getUserReviews($targetUserId, $limit, $offset);
            echo json_encode($result);
            break;
            
        case 'get_request_reviews':
            $requestId = $_GET['request_id'] ?? null;
            
            if (empty($requestId)) {
                echo json_encode(['success' => false, 'error' => 'Request ID required']);
                exit;
            }
            
            $result = $ratingService->getRequestReviews($requestId);
            echo json_encode($result);
            break;
            
        case 'can_review':
            $requestId = $_GET['request_id'] ?? null;
            
            if (empty($requestId)) {
                echo json_encode(['success' => false, 'error' => 'Request ID required']);
                exit;
            }
            
            $result = $ratingService->canReview($userId, $requestId);
            echo json_encode($result);
            break;
            
        case 'get_rating_stats':
            $targetUserId = $_GET['user_id'] ?? $userId;
            
            $result = $ratingService->getUserRatingStats($targetUserId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    RatingLogger::log('error', 'Exception occurred: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again.'
    ]);
}
?>
