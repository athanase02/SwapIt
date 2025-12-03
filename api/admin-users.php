<?php
/**
 * Admin Users API - Check registered users
 * WARNING: This is for testing only. Remove in production or add proper authentication!
 * 
 * @author Athanase Abayo
 */

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/db.php';

try {
    // Get query parameters
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get recent users
            $stmt = $conn->prepare(
                "SELECT id, email, full_name, phone, created_at 
                 FROM users 
                 ORDER BY created_at DESC 
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            $users = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'count' => count($users),
                'users' => $users
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'stats':
            // Get user statistics
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $total = $stmt->fetch()['total'];
            
            $stmt = $conn->query(
                "SELECT COUNT(*) as today FROM users 
                 WHERE DATE(created_at) = CURDATE()"
            );
            $today = $stmt->fetch()['today'];
            
            $stmt = $conn->query(
                "SELECT COUNT(*) as this_week FROM users 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            $thisWeek = $stmt->fetch()['this_week'];
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_users' => $total,
                    'registered_today' => $today,
                    'registered_this_week' => $thisWeek
                ]
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'search':
            // Search for specific user by email
            $email = $_GET['email'] ?? '';
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Email required']);
                exit;
            }
            
            $stmt = $conn->prepare(
                "SELECT id, email, full_name, phone, created_at 
                 FROM users 
                 WHERE email LIKE ?"
            );
            $stmt->execute(["%$email%"]);
            $users = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'count' => count($users),
                'users' => $users
            ], JSON_PRETTY_PRINT);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log("Admin Users API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
