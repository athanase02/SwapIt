<?php
/**
 * Test Railway Database Connection
 * This script verifies that your PHP application can connect to Railway MySQL
 */

// Load database configuration
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

try {
    // Test connection
    $stmt = $conn->query("SELECT VERSION() as mysql_version");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count tables
    $stmt = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
    $tableCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count users
    $stmt = $conn->query("SELECT COUNT(*) as user_count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count items
    $stmt = $conn->query("SELECT COUNT(*) as item_count FROM items");
    $itemCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count categories
    $stmt = $conn->query("SELECT COUNT(*) as category_count FROM categories");
    $categoryCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get database name
    $stmt = $conn->query("SELECT DATABASE() as current_database");
    $dbName = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Successfully connected to Railway MySQL!',
        'connection' => [
            'database' => $dbName['current_database'],
            'mysql_version' => $version['mysql_version'],
            'host' => getenv('MYSQLHOST') ?: 'localhost'
        ],
        'statistics' => [
            'total_tables' => (int)$tableCount['table_count'],
            'total_users' => (int)$userCount['user_count'],
            'total_items' => (int)$itemCount['item_count'],
            'total_categories' => (int)$categoryCount['category_count']
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage(),
        'environment' => [
            'MYSQLHOST' => getenv('MYSQLHOST') ? 'Set' : 'Not set',
            'MYSQLPORT' => getenv('MYSQLPORT') ?: 'Not set',
            'MYSQLDATABASE' => getenv('MYSQLDATABASE') ?: 'Not set',
            'MYSQLUSER' => getenv('MYSQLUSER') ? 'Set' : 'Not set',
            'DB_HOST' => getenv('DB_HOST') ? 'Set (Render)' : 'Not set'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
