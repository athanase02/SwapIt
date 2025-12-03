<?php
/**
 * SwapIt Profile API
 * Handles user profile data, statistics, and activity history
 * 
 * @author Athanase Abayo - Profile management and activity tracking
 * @version 1.0
 */

session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/db_with_fallback.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = $_SESSION['user_id'];
// Check both GET and POST for action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_profile':
        // Get user profile with statistics
        $stmt = $conn->prepare(
            "SELECT u.id, u.email, u.full_name, u.avatar_url, u.phone, u.account_type, u.created_at,
                    p.bio, p.location, p.university, p.student_id, p.graduation_year,
                    p.rating_average, p.total_reviews, p.total_items_listed, p.total_items_borrowed, p.total_items_lent, p.trust_score
             FROM users u
             LEFT JOIN profiles p ON u.id = p.user_id
             WHERE u.id = ?"
        );
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        
        if ($profile) {
            echo json_encode([
                'success' => true,
                'profile' => $profile
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Profile not found']);
        }
        break;
        
    case 'get_activities':
        $limit = intval($_GET['limit'] ?? 20);
        
        try {
            // Check if activity_logs table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_logs'");
            if ($tableCheck->num_rows == 0) {
                // Table doesn't exist, return empty activities
                echo json_encode([
                    'success' => true,
                    'activities' => []
                ]);
                break;
            }
            
            // Get user's recent activities with details
            $stmt = $conn->prepare(
                "SELECT 
                    al.action, 
                    al.entity_type, 
                    al.entity_id, 
                    al.details, 
                    al.created_at,
                    CASE 
                        WHEN al.entity_type = 'item' THEN i.title
                        WHEN al.entity_type = 'borrow_request' THEN CONCAT('Request #', al.entity_id)
                        ELSE NULL
                    END as entity_name
                 FROM activity_logs al
                 LEFT JOIN items i ON al.entity_type = 'item' AND al.entity_id = i.id
                 WHERE al.user_id = ?
                 ORDER BY al.created_at DESC
                 LIMIT ?"
            );
            
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                // Parse details JSON
                if (!empty($row['details'])) {
                    $row['details'] = json_decode($row['details'], true);
                } else {
                    $row['details'] = [];
                }
                $activities[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'activities' => $activities
            ]);
        } catch (Exception $e) {
            // Return empty activities on error instead of failing
            echo json_encode([
                'success' => true,
                'activities' => []
            ]);
        }
        break;
        
    case 'get_stats':
        // Get comprehensive user statistics
        $stats = [];
        
        // Active listings count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM items WHERE owner_id = ? AND status = 'available'");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stats['active_listings'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Pending borrow requests (as borrower)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrow_requests WHERE borrower_id = ? AND status = 'pending'");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stats['pending_requests'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Active borrows (as borrower)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrow_requests WHERE borrower_id = ? AND status = 'active'");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stats['active_borrows'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Items being lent out (as lender)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrow_requests WHERE lender_id = ? AND status = 'active'");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stats['items_lent'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Total earnings (as lender)
        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(t.amount), 0) as total 
             FROM transactions t
             JOIN borrow_requests br ON t.borrow_request_id = br.id
             WHERE br.lender_id = ? AND t.transaction_type = 'rental_payment' AND t.payment_status = 'completed'"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stats['total_earnings'] = floatval($stmt->get_result()->fetch_assoc()['total']);
        
        // Total spent (as borrower)
        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(t.amount), 0) as total 
             FROM transactions t
             JOIN borrow_requests br ON t.borrow_request_id = br.id
             WHERE br.borrower_id = ? AND t.transaction_type = 'rental_payment' AND t.payment_status = 'completed'"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stats['total_spent'] = floatval($stmt->get_result()->fetch_assoc()['total']);
        
        // Wishlist count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM saved_items WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stats['wishlist_count'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Cart count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stats['cart_count'] = $stmt->get_result()->fetch_assoc()['count'];
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        break;
        
    case 'update_profile':
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $avatarUrl = $_POST['avatar_url'] ?? '';
        
        try {
            $conn->begin_transaction();
            
            // Update users table
            if (!empty($fullName)) {
                $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->bind_param('si', $fullName, $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update full name");
                }
            }
            
            if (!empty($phone)) {
                $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
                $stmt->bind_param('si', $phone, $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update phone");
                }
            }
            
            // Update avatar if provided
            if (!empty($avatarUrl)) {
                $stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                $stmt->bind_param('si', $avatarUrl, $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update avatar");
                }
            }
            
            // Update profiles table - check if profile exists first
            $stmt = $conn->prepare("SELECT user_id FROM profiles WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $profileExists = $stmt->get_result()->num_rows > 0;
            
            if ($profileExists) {
                $stmt = $conn->prepare("UPDATE profiles SET bio = ?, location = ? WHERE user_id = ?");
                $stmt->bind_param('ssi', $bio, $location, $userId);
            } else {
                // Create profile if it doesn't exist
                $stmt = $conn->prepare("INSERT INTO profiles (user_id, bio, location) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $userId, $bio, $location);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update profile information");
            }
            
            // Log activity (ignore if table doesn't exist)
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO activity_logs (user_id, action, entity_type, ip_address, user_agent) 
                     VALUES (?, 'update_profile', 'user', ?, ?)"
                );
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $stmt->bind_param('iss', $userId, $ip, $userAgent);
                $stmt->execute();
            } catch (Exception $e) {
                // Ignore activity log errors
            }
            
            $conn->commit();
            
            // Get updated user data
            $stmt = $conn->prepare(
                "SELECT u.id, u.email, u.full_name, u.avatar_url, u.phone
                 FROM users u WHERE u.id = ?"
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false, 
                'error' => 'Failed to update profile: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
