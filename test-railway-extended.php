<?php
/**
 * Test Railway Connection with Extended Timeout
 */

echo "Testing Railway MySQL with extended timeout...\n";
echo "==============================================\n\n";

$host = 'shinkansen.proxy.rlwy.net';
$port = '32604';
$database = 'railway';
$username = 'root';
$password = 'psMDOMvbOfBoWmHXkhNkhbRLpnPjpcVV';

echo "Credentials:\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  Database: $database\n";
echo "  User: $username\n";
echo "  Password: " . str_repeat('*', strlen($password)) . "\n\n";

echo "Attempting connection (30 second timeout)...\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ]);
    
    echo "✓ Connection successful!\n\n";
    
    // Test query
    $result = $conn->query("SELECT VERSION() as version")->fetch();
    echo "MySQL Version: {$result['version']}\n";
    
    $result = $conn->query("SELECT DATABASE() as db")->fetch();
    echo "Current Database: {$result['db']}\n\n";
    
    // Count tables
    $count = $conn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$database'")->fetchColumn();
    echo "Total tables: $count\n\n";
    
    // Count users
    $userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total users: $userCount\n\n";
    
    echo "✅ All tests passed! Railway database is accessible.\n";
    
} catch (PDOException $e) {
    echo "✗ Connection failed!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Possible causes:\n";
    echo "1. Railway database is sleeping (free tier)\n";
    echo "2. Check Railway dashboard: https://railway.app/project/energetic-forgiveness\n";
    echo "3. Verify database service is running\n";
    echo "4. Network connectivity issues\n";
    exit(1);
}
