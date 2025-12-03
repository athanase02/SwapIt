<?php
/**
 * SwapIt Profile API
 * Handles user profile data, statistics, and activity history
 * 
 * @author Athanase Abayo - Profile management and activity tracking
 * @version 2.0 - Updated to use PDO
 */

session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = $_SESSION['user_id'];
// Check both GET and POST for action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_profile':
            // Get user profile with statistics
            $stmt = $conn->prepare(
                "SELECT u.id, u.email, u.full_name, u.avatar_url, u.phone, u.created_at,
                        p.bio, p.location, p.rating_average, p.total_reviews
                 FROM users u
                 LEFT JOIN profiles p ON u.id = p.user_id
                 WHERE u.id = ?"
            );
            
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();
            
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
            // Return empty activities for now (can be implemented later with proper schema)
            echo json_encode([
                'success' => true,
                'activities' => []
            ]);
            break;
            
        case 'get_stats':
            // Return basic stats (most tables don't exist yet)
            echo json_encode([
                'success' => true,
                'stats' => [
                    'active_listings' => 0,
                    'pending_requests' => 0,
                    'active_borrows' => 0,
                    'items_lent' => 0,
                    'total_earnings' => 0,
                    'total_spent' => 0,
                    'wishlist_count' => 0,
                    'cart_count' => 0
                ]
            ]);
            break;
            
        case 'update_profile':
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $avatarUrl = $_POST['avatar_url'] ?? '';
            
            $conn->beginTransaction();
            
            // Update users table
            $updates = [];
            $params = [];
            
            if (!empty($fullName)) {
                $updates[] = "full_name = ?";
                $params[] = $fullName;
            }
            
            if (!empty($phone)) {
                $updates[] = "phone = ?";
                $params[] = $phone;
            }
            
            if (!empty($avatarUrl)) {
                $updates[] = "avatar_url = ?";
                $params[] = $avatarUrl;
            }
            
            if (!empty($updates)) {
                $params[] = $userId;
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
            }
            
            // Update profiles table - check if profile exists first
            $stmt = $conn->prepare("SELECT user_id FROM profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profileExists = $stmt->fetch();
            
            if ($profileExists) {
                $stmt = $conn->prepare("UPDATE profiles SET bio = ?, location = ? WHERE user_id = ?");
                $stmt->execute([$bio, $location, $userId]);
            } else {
                // Get user's full name and email for the profile
                $stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                // Create profile if it doesn't exist
                $stmt = $conn->prepare("INSERT INTO profiles (user_id, full_name, email, bio, location) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $user['full_name'], $user['email'], $bio, $location]);
            }
            
            $conn->commit();
            
            // Get updated user data
            $stmt = $conn->prepare(
                "SELECT u.id, u.email, u.full_name, u.avatar_url, u.phone
                 FROM users u WHERE u.id = ?"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Profile API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to process request. Please try again.'
    ]);
}
?>
