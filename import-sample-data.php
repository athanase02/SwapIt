<?php
// Import sample data to Railway MySQL
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'yamabiko.proxy.rlwy.net';
$port = 53608;
$dbname = 'SI2025';
$username = 'root';
$password = 'oxkHZYorRjhuudWnSGROQmSiYMSokBqq';

try {
    // Create PDO connection with SSL disabled
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✓ Connected to Railway MySQL\n\n";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/db/sample_data.sql';
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolons and execute
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $executed = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || substr(ltrim($statement), 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            if ($executed % 10 === 0) {
                echo ".";
            }
        } catch (PDOException $e) {
            $failed++;
            // Only show non-duplicate key errors
            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "\n⚠ Warning: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n\n✓ Import complete!\n";
    echo "  Executed: $executed statements\n";
    if ($failed > 0) {
        echo "  Warnings: $failed statements\n";
    }
    
    // Verify counts
    echo "\nData verification:\n";
    $tables = ['users', 'profiles', 'categories', 'items', 'borrow_requests', 'messages', 'notifications'];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "  $table: $count rows\n";
    }
    
    // Check views
    $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll();
    echo "\nViews created: " . count($views) . "\n";
    foreach ($views as $view) {
        echo "  - " . $view['Tables_in_si2025'] . "\n";
    }
    
    echo "\n✅ Database ready for use!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
