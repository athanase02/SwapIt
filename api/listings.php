<?php
/**
 * SwapIt Listings API
 * Handles item listing creation, updates, and activity tracking
 * 
 * @author Athanase Abayo - Core architecture and activity logging
 * @version 1.0
 */

session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/db_with_fallback.php';

/**
 * Activity Logger
 * Logs all user activities to database for tracking and analytics
 */
class ActivityLogger {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Log user activity to database
     * @param int $userId - User ID performing the action
     * @param string $action - Action performed (e.g., 'create_listing', 'add_to_cart')
     * @param string $entityType - Type of entity (e.g., 'item', 'transaction')
     * @param int $entityId - ID of the entity
     * @param array $details - Additional details as JSON
     */
    public function log($userId, $action, $entityType = null, $entityId = null, $details = []) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $this->conn->prepare(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, details) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $detailsJson = json_encode($details);
        $stmt->bind_param('issssss', $userId, $action, $entityType, $entityId, $ipAddress, $userAgent, $detailsJson);
        
        return $stmt->execute();
    }
    
    /**
     * Get user's recent activities
     * @param int $userId - User ID
     * @param int $limit - Number of activities to retrieve
     * @return array - Array of activity records
     */
    public function getUserActivities($userId, $limit = 20) {
        $stmt = $this->conn->prepare(
            "SELECT action, entity_type, entity_id, details, created_at 
             FROM activity_logs 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?"
        );
        
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $activities = [];
        
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        
        return $activities;
    }
}

/**
 * Profile Statistics Updater
 * Updates user profile statistics when activities occur
 */
class ProfileStatsUpdater {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Update user's listing count
     */
    public function incrementListingCount($userId) {
        $stmt = $this->conn->prepare(
            "UPDATE profiles SET total_items_listed = total_items_listed + 1 WHERE user_id = ?"
        );
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }
    
    /**
     * Update user's borrow count
     */
    public function incrementBorrowCount($userId) {
        $stmt = $this->conn->prepare(
            "UPDATE profiles SET total_items_borrowed = total_items_borrowed + 1 WHERE user_id = ?"
        );
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }
    
    /**
     * Update user's lend count
     */
    public function incrementLendCount($userId) {
        $stmt = $this->conn->prepare(
            "UPDATE profiles SET total_items_lent = total_items_lent + 1 WHERE user_id = ?"
        );
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$activityLogger = new ActivityLogger($conn);
$statsUpdater = new ProfileStatsUpdater($conn);

switch ($action) {
    case 'create_listing':
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        $imageUrl = $_POST['image_url'] ?? null;
        
        // Validate required fields
        if (empty($title) || empty($description) || empty($category)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        
        // Get category ID from category name
        $categoryId = 1; // Default to first category
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name LIKE ? OR slug LIKE ? LIMIT 1");
        $categoryLike = "%$category%";
        $stmt->bind_param('ss', $categoryLike, $categoryLike);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $categoryId = $row['id'];
        }
        
        // Insert item
        $stmt = $conn->prepare(
            "INSERT INTO items (title, description, category_id, condition_status, price, rental_period, location, owner_id, status, image_urls) 
             VALUES (?, ?, ?, 'Good', ?, 'daily', ?, ?, 'available', ?)"
        );
        
        $imageJson = $imageUrl ? json_encode([$imageUrl]) : null;
        $stmt->bind_param('ssidsis', $title, $description, $categoryId, $price, $location, $userId, $imageJson);
        
        if ($stmt->execute()) {
            $itemId = $conn->insert_id;
            
            // Log activity
            $activityLogger->log($userId, 'create_listing', 'item', $itemId, [
                'title' => $title,
                'category' => $category,
                'price' => $price
            ]);
            
            // Update profile statistics
            $statsUpdater->incrementListingCount($userId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Listing created successfully',
                'item_id' => $itemId
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create listing']);
        }
        break;
        
    case 'add_to_cart':
        $itemId = intval($_POST['item_id'] ?? 0);
        $startDate = $_POST['start_date'] ?? date('Y-m-d H:i:s', strtotime('+1 day'));
        $endDate = $_POST['end_date'] ?? date('Y-m-d H:i:s', strtotime('+3 days'));
        
        if ($itemId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
            exit;
        }
        
        // Check if item already in cart
        $stmt = $conn->prepare("SELECT id FROM cart_items WHERE user_id = ? AND item_id = ?");
        $stmt->bind_param('ii', $userId, $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Item already in cart']);
            exit;
        }
        
        // Add to cart
        $stmt = $conn->prepare(
            "INSERT INTO cart_items (user_id, item_id, start_date, end_date, quantity) 
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->bind_param('iiss', $userId, $itemId, $startDate, $endDate);
        
        if ($stmt->execute()) {
            // Log activity
            $activityLogger->log($userId, 'add_to_cart', 'item', $itemId);
            
            echo json_encode(['success' => true, 'message' => 'Item added to cart']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add to cart']);
        }
        break;
        
    case 'add_to_wishlist':
        $itemId = intval($_POST['item_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if ($itemId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
            exit;
        }
        
        // Check if item already in wishlist
        $stmt = $conn->prepare("SELECT id FROM saved_items WHERE user_id = ? AND item_id = ?");
        $stmt->bind_param('ii', $userId, $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Item already in wishlist']);
            exit;
        }
        
        // Add to wishlist
        $stmt = $conn->prepare(
            "INSERT INTO saved_items (user_id, item_id, notes) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iis', $userId, $itemId, $notes);
        
        if ($stmt->execute()) {
            // Update item saves count
            $conn->query("UPDATE items SET saves_count = saves_count + 1 WHERE id = $itemId");
            
            // Log activity
            $activityLogger->log($userId, 'add_to_wishlist', 'item', $itemId);
            
            echo json_encode(['success' => true, 'message' => 'Item added to wishlist']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add to wishlist']);
        }
        break;
        
    case 'create_borrow_request':
        $itemId = intval($_POST['item_id'] ?? 0);
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $message = trim($_POST['message'] ?? '');
        
        if ($itemId <= 0 || empty($startDate) || empty($endDate)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        
        // Get item details and owner
        $stmt = $conn->prepare("SELECT owner_id, price FROM items WHERE id = ?");
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        
        if (!$item) {
            echo json_encode(['success' => false, 'error' => 'Item not found']);
            exit;
        }
        
        if ($item['owner_id'] == $userId) {
            echo json_encode(['success' => false, 'error' => 'Cannot borrow your own item']);
            exit;
        }
        
        // Calculate total price (days * daily rate)
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $days = $start->diff($end)->days;
        $totalPrice = $days * $item['price'];
        
        // Create borrow request
        $stmt = $conn->prepare(
            "INSERT INTO borrow_requests (item_id, borrower_id, lender_id, status, borrow_start_date, borrow_end_date, total_price, borrower_message) 
             VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)"
        );
        $stmt->bind_param('iiiisds', $itemId, $userId, $item['owner_id'], $startDate, $endDate, $totalPrice, $message);
        
        if ($stmt->execute()) {
            $requestId = $conn->insert_id;
            
            // Log activity
            $activityLogger->log($userId, 'create_borrow_request', 'borrow_request', $requestId, [
                'item_id' => $itemId,
                'total_price' => $totalPrice,
                'days' => $days
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Borrow request created successfully',
                'request_id' => $requestId
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create borrow request']);
        }
        break;
        
    case 'get_activities':
        $limit = intval($_GET['limit'] ?? 20);
        $activities = $activityLogger->getUserActivities($userId, $limit);
        
        echo json_encode([
            'success' => true,
            'activities' => $activities
        ]);
        break;
        
    case 'get_all_items':
        // Get all available items for browsing
        $stmt = $conn->prepare(
            "SELECT i.id, i.title, i.description, i.category_id, i.price, i.location, 
                    i.image_urls, i.status, i.owner_id, i.created_at,
                    c.name as category_name, c.slug as category_slug,
                    u.full_name as owner_name, u.avatar_url as owner_avatar
             FROM items i
             LEFT JOIN categories c ON i.category_id = c.id
             LEFT JOIN users u ON i.owner_id = u.id
             WHERE i.status = 'available'
             ORDER BY i.created_at DESC"
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            // Parse image URLs JSON
            if (!empty($row['image_urls'])) {
                $row['images'] = json_decode($row['image_urls'], true);
                $row['image_url'] = is_array($row['images']) && count($row['images']) > 0 
                    ? $row['images'][0] 
                    : null;
            } else {
                $row['images'] = [];
                $row['image_url'] = null;
            }
            unset($row['image_urls']);
            
            $items[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
        break;
        
    case 'getUserListings':
        // Get user's own listings
        $stmt = $conn->prepare(
            "SELECT i.id, i.title, i.description, i.category_id, i.price, i.location, 
                    i.image_urls, i.status, i.created_at,
                    c.name as category_name
             FROM items i
             LEFT JOIN categories c ON i.category_id = c.id
             WHERE i.owner_id = ?
             ORDER BY i.created_at DESC"
        );
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            // Parse image URLs JSON
            if (!empty($row['image_urls'])) {
                $row['images'] = json_decode($row['image_urls'], true);
                $row['image_url'] = is_array($row['images']) && count($row['images']) > 0 
                    ? $row['images'][0] 
                    : null;
            } else {
                $row['images'] = [];
                $row['image_url'] = null;
            }
            unset($row['image_urls']);
            
            $items[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
