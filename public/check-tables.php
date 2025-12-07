<?php
/**
 * Check Database Tables
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$results = [
    'success' => false,
    'tables' => [],
    'errors' => []
];

try {
    if (!isset($conn)) {
        throw new Exception("Database connection not available");
    }

    // Get all tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results['tables'] = $tables;
    
    // Check specific tables
    $requiredTables = ['conversations', 'messages', 'borrow_requests', 'reviews', 'notifications', 'meeting_schedules'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $tables)) {
            $missingTables[] = $table;
        } else {
            // Count rows
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $results['table_counts'][$table] = $count;
        }
    }
    
    $results['missing_tables'] = $missingTables;
    
    // Check users
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $results['users_count'] = $stmt->fetchColumn();
    
    $results['success'] = true;

} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
