<?php
/**
 * SwapIt Items API
 * Handles item listing, browsing, and details
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');

require_once dirname(__DIR__) . '/config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

class ItemsService {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all available items
     */
    public function getAllItems($filters = []) {
        $sql = "SELECT i.*, 
                       c.name as category_name,
                       c.slug as category_slug,
                       u.full_name as owner_name, 
                       u.avatar_url as owner_avatar,
                       u.id as owner_id,
                       (SELECT image_url FROM item_images WHERE item_id = i.id AND is_primary = 1 LIMIT 1) as primary_image
                FROM items i
                JOIN users u ON i.owner_id = u.id
                LEFT JOIN categories c ON i.category_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['category'])) {
            $sql .= " AND c.slug = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['location'])) {
            $sql .= " AND i.location LIKE ?";
            $params[] = '%' . $filters['location'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        } else {
            $sql .= " AND i.status = 'available'";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (i.title LIKE ? OR i.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND i.price >= ?";
            $params[] = floatval($filters['min_price']);
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND i.price <= ?";
            $params[] = floatval($filters['max_price']);
        }
        
        // Sorting
        $sortBy = $filters['sort'] ?? 'recent';
        switch ($sortBy) {
            case 'price-low':
                $sql .= " ORDER BY i.price ASC";
                break;
            case 'price-high':
                $sql .= " ORDER BY i.price DESC";
                break;
            default:
                $sql .= " ORDER BY i.created_at DESC";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add image_url for compatibility
        foreach ($items as &$item) {
            $item['image_url'] = $item['primary_image'] ?? 'https://placehold.co/400x300?text=' . urlencode($item['title']);
        }
        
        return [
            'success' => true,
            'items' => $items
        ];
    }
    
    /**
     * Get single item details
     */
    public function getItem($itemId) {
        $stmt = $this->conn->prepare(
            "SELECT i.*, 
                    c.name as category_name,
                    c.slug as category_slug,
                    u.full_name as owner_name, 
                    u.avatar_url as owner_avatar,
                    u.email as owner_email,
                    u.phone as owner_phone,
                    u.id as owner_id
             FROM items i
             JOIN users u ON i.owner_id = u.id
             LEFT JOIN categories c ON i.category_id = c.id
             WHERE i.id = ?"
        );
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            return ['success' => false, 'error' => 'Item not found'];
        }
        
        // Get all images for this item
        $imageStmt = $this->conn->prepare(
            "SELECT image_url, is_primary, display_order 
             FROM item_images 
             WHERE item_id = ? 
             ORDER BY is_primary DESC, display_order ASC"
        );
        $imageStmt->execute([$itemId]);
        $images = [];
        
        while ($img = $imageStmt->fetch(PDO::FETCH_ASSOC)) {
            $images[] = $img['image_url'];
        }
        
        $item['images'] = $images;
        $item['image_url'] = !empty($images) ? $images[0] : 'https://placehold.co/400x300?text=' . urlencode($item['title']);
        
        return [
            'success' => true,
            'item' => $item
        ];
    }
    
    /**
     * Create new item listing
     */
    public function createItem($data, $userId) {
        $stmt = $this->conn->prepare(
            "INSERT INTO items 
             (user_id, title, description, category, price_per_day, location, 
              image_url, condition_description, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available')"
        );
        
        $stmt->execute([
            $userId,
            $data['title'],
            $data['description'],
            $data['category'],
            $data['price_per_day'],
            $data['location'] ?? null,
            $data['image_url'] ?? null,
            $data['condition'] ?? 'Good'
        ]);
        
        $itemId = $this->conn->lastInsertId();
        
        return [
            'success' => true,
            'item_id' => $itemId,
            'message' => 'Item listed successfully'
        ];
    }
    
    /**
     * Update item
     */
    public function updateItem($itemId, $data, $userId) {
        // Verify ownership
        $stmt = $this->conn->prepare("SELECT user_id FROM items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item || $item['user_id'] != $userId) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }
        
        $stmt = $this->conn->prepare(
            "UPDATE items 
             SET title = ?, description = ?, category = ?, price_per_day = ?, 
                 location = ?, condition_description = ?, status = ?
             WHERE id = ?"
        );
        
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['category'],
            $data['price_per_day'],
            $data['location'] ?? null,
            $data['condition'] ?? 'Good',
            $data['status'] ?? 'available',
            $itemId
        ]);
        
        return [
            'success' => true,
            'message' => 'Item updated successfully'
        ];
    }
    
    /**
     * Delete item
     */
    public function deleteItem($itemId, $userId) {
        // Verify ownership
        $stmt = $this->conn->prepare("SELECT user_id FROM items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item || $item['user_id'] != $userId) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }
        
        $stmt = $this->conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$itemId]);
        
        return [
            'success' => true,
            'message' => 'Item deleted successfully'
        ];
    }
    
    /**
     * Get user's items
     */
    public function getUserItems($userId) {
        $stmt = $this->conn->prepare(
            "SELECT i.*, 
                    COUNT(DISTINCT br.id) as total_requests,
                    COUNT(DISTINCT CASE WHEN br.status = 'pending' THEN br.id END) as pending_requests
             FROM items i
             LEFT JOIN borrow_requests br ON i.id = br.item_id
             WHERE i.user_id = ?
             GROUP BY i.id
             ORDER BY i.created_at DESC"
        );
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();
        
        return [
            'success' => true,
            'items' => $items
        ];
    }
}

// Initialize service
$itemsService = new ItemsService($conn);

// Handle requests
try {
    switch ($action) {
        case 'get_all':
            $filters = [
                'category' => $_GET['category'] ?? null,
                'location' => $_GET['location'] ?? null,
                'status' => $_GET['status'] ?? null,
                'search' => $_GET['search'] ?? null,
                'min_price' => $_GET['min_price'] ?? null,
                'max_price' => $_GET['max_price'] ?? null,
                'sort' => $_GET['sort'] ?? 'recent'
            ];
            
            $result = $itemsService->getAllItems($filters);
            echo json_encode($result);
            break;
            
        case 'get_item':
            $itemId = $_GET['id'] ?? null;
            
            if (!$itemId) {
                echo json_encode(['success' => false, 'error' => 'Item ID required']);
                exit;
            }
            
            $result = $itemsService->getItem($itemId);
            echo json_encode($result);
            break;
            
        case 'create':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $_POST['category'] ?? '',
                'price_per_day' => $_POST['price_per_day'] ?? 0,
                'location' => $_POST['location'] ?? null,
                'image_url' => $_POST['image_url'] ?? null,
                'condition' => $_POST['condition'] ?? 'Good'
            ];
            
            $result = $itemsService->createItem($data, $_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'update':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            
            $itemId = $_POST['item_id'] ?? null;
            if (!$itemId) {
                echo json_encode(['success' => false, 'error' => 'Item ID required']);
                exit;
            }
            
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $_POST['category'] ?? '',
                'price_per_day' => $_POST['price_per_day'] ?? 0,
                'location' => $_POST['location'] ?? null,
                'condition' => $_POST['condition'] ?? 'Good',
                'status' => $_POST['status'] ?? 'available'
            ];
            
            $result = $itemsService->updateItem($itemId, $data, $_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'delete':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            
            $itemId = $_POST['item_id'] ?? null;
            if (!$itemId) {
                echo json_encode(['success' => false, 'error' => 'Item ID required']);
                exit;
            }
            
            $result = $itemsService->deleteItem($itemId, $_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'get_my_items':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            
            $result = $itemsService->getUserItems($_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Items API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred. Please try again.'
    ]);
}
