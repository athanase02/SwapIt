<?php
/**
 * Database Configuration
 * Supports MySQL on both local and Render environments
 * 
 * @author Athanase Abayo - Database configuration
 */

try {
    // Check if running on Render with Railway MySQL (via DB_* environment variables)
    // This is the primary check for Render deployment
    if (getenv('DB_HOST')) {
        // Render deployment connecting to Railway MySQL
        // Use RAILWAY_DB_* variables if available, otherwise fall back to DB_* variables
        $host = getenv('RAILWAY_DB_HOST') ?: getenv('DB_HOST');
        $port = getenv('RAILWAY_DB_PORT') ?: getenv('DB_PORT') ?: '3306';
        $database = getenv('RAILWAY_DB_NAME') ?: getenv('DB_NAME') ?: 'railway';
        $username = getenv('RAILWAY_DB_USER') ?: getenv('DB_USER') ?: 'root';
        $password = getenv('RAILWAY_DB_PASSWORD') ?: getenv('DB_PASSWORD');
        
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        
        $conn = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 15,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
        
        error_log("SwapIt: Connected to Railway MySQL from Render ($host:$port/$database)");
    }
    // Check if running with Railway environment variables (production on Railway)
    elseif (getenv('MYSQLHOST') || getenv('RAILWAY_DB_HOST')) {
        // Railway MySQL connection via environment variables
        $host = getenv('MYSQLHOST') ?: getenv('RAILWAY_DB_HOST');
        $port = getenv('MYSQLPORT') ?: getenv('RAILWAY_DB_PORT') ?: '3306';
        $database = getenv('MYSQLDATABASE') ?: getenv('RAILWAY_DB_NAME') ?: 'railway';
        $username = getenv('MYSQLUSER') ?: getenv('RAILWAY_DB_USER') ?: 'root';
        $password = getenv('MYSQLPASSWORD') ?: getenv('RAILWAY_DB_PASSWORD');
        
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        
        $conn = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
        
        error_log("SwapIt: Connected to Railway MySQL (Railway production) ($host:$port/$database)");
    }
    // Local development connecting to Railway MySQL
    else {
        // Local development connecting to Railway MySQL (hardcoded)
        $host = 'shinkansen.proxy.rlwy.net';
        $port = '32604';
        $database = 'railway';
        $username = 'root';
        $password = 'psMDOMvbOfBoWmHXkhNkhbRLpnPjpcVV';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        
        $conn = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
        
        error_log("SwapIt: Connected to Railway MySQL (local dev) ($host:$port/$database)");
    }
    
    // Verify connection and log table count
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '$database'");
        $result = $stmt->fetch();
        error_log("SwapIt: Database has {$result['count']} tables");
    } catch (Exception $e) {
        error_log("SwapIt: Could not verify tables: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    error_log("SwapIt: MySQL Error - " . $e->getMessage());
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed',
            'details' => $e->getMessage()
        ]);
        exit;
    }
    die("Database connection failed: " . $e->getMessage());
}
?>