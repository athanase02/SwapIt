<?php
/**
 * Railway Database Wake-Up with Retry
 */

echo "Waking up Railway database...\n\n";

$maxAttempts = 5;
$connected = false;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    echo "Attempt $attempt/$maxAttempts: ";
    
    try {
        $dsn = "mysql:host=shinkansen.proxy.rlwy.net;port=32604;dbname=railway;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ];
        
        $conn = new PDO($dsn, 'root', 'psMDOMvbOfBoWmHXkhNkhbRLpnPjpcVV', $options);
        $result = $conn->query("SELECT COUNT(*) as count FROM users")->fetch();
        
        echo "✓ CONNECTED! Found {$result['count']} users\n";
        $connected = true;
        break;
        
    } catch (Exception $e) {
        echo "✗ Failed - " . $e->getMessage() . "\n";
        if ($attempt < $maxAttempts) {
            echo "   Waiting 5 seconds before retry...\n";
            sleep(5);
        }
    }
}

echo "\n";

if ($connected) {
    echo "✅ Database is AWAKE and READY!\n";
    echo "You can now run: php test-new-user-flow.php\n";
    exit(0);
} else {
    echo "❌ Could not connect after $maxAttempts attempts\n";
    echo "Please check Railway dashboard: https://railway.com/project/d2132bb7-72c6-458a-af63-7a23d3356697\n";
    exit(1);
}
