<?php
/**
 * Database Connection Test
 * Use this to verify MySQL connection on Render
 */

header('Content-Type: application/json');

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => [],
    'connection' => null,
    'tables' => []
];

// Check environment variables
$response['environment'] = [
    'DB_HOST' => getenv('DB_HOST') ?: 'not set',
    'DB_PORT' => getenv('DB_PORT') ?: 'not set',
    'DB_NAME' => getenv('DB_NAME') ?: 'not set',
    'DB_USER' => getenv('DB_USER') ?: 'not set',
    'DB_PASSWORD' => getenv('DB_PASSWORD') ? 'SET (hidden)' : 'not set',
    'PHP_VERSION' => phpversion(),
    'PDO_MYSQL' => extension_loaded('pdo_mysql') ? 'installed' : 'NOT INSTALLED'
];

// Test database connection
try {
    require_once dirname(__DIR__) . '/config/db.php';
    
    $response['connection'] = 'SUCCESS';
    
    // Check if tables exist
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $response['tables'] = $tables;
    
    // Check users table structure
    if (in_array('users', $tables)) {
        $columns = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        $response['users_table'] = array_column($columns, 'Field');
    }
    
    // Count existing users
    $userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $response['user_count'] = $userCount;
    
} catch (PDOException $e) {
    $response['connection'] = 'FAILED';
    $response['error'] = $e->getMessage();
    $response['code'] = $e->getCode();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
