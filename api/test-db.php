<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

header('Content-Type: application/json');

$response = [
    'connection' => false,
    'database_exists' => false,
    'tables' => [],
    'error' => null
];

try {
    // Use absolute path for db.php
    $dbPath = dirname(__DIR__) . '/config/db.php';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Database config file not found at: $dbPath");
    }
    
    require_once $dbPath;
    // Test connection
    if ($conn && !$conn->connect_error) {
        $response['connection'] = true;
        
        // Check if database exists and is selected
        $result = $conn->query("SELECT DATABASE()");
        if ($result) {
            $row = $result->fetch_row();
            $response['database_exists'] = ($row[0] === 'SI2025');
            $response['current_database'] = $row[0];
        }
        
        // Get list of tables
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            while ($row = $result->fetch_row()) {
                $tableName = $row[0];
                
                // Get row count for each table
                $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                $countRow = $countResult->fetch_assoc();
                
                $response['tables'][] = [
                    'name' => $tableName,
                    'rows' => $countRow['count']
                ];
            }
        }
        
        // Test a simple query on users table
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            $response['users_count'] = $row['count'];
        }
        
    } else {
        $response['error'] = $conn->connect_error;
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
