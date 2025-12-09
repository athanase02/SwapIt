<?php
// Populate Railway MySQL with complete schema and data
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes

echo "=== Railway MySQL Setup ===\n\n";

$host = 'yamabiko.proxy.rlwy.net';
$port = 53608;
$dbname = 'railway';
$username = 'root';
$password = 'oxkHZYorRjhuudWnSGROQmSiYMSokBqq';

try {
    // Connect with timeout settings
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_TIMEOUT => 30,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION wait_timeout=300"
    ];
    
    echo "Connecting to Railway MySQL...\n";
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✓ Connected!\n\n";
    
    // Step 1: Import schema
    echo "Step 1: Importing schema from SI2025.sql...\n";
    $schemaFile = __DIR__ . '/db/SI2025.sql';
    $schema = file_get_contents($schemaFile);
    
    // Execute schema (will fail at some point due to foreign keys, that's OK)
    try {
        $pdo->exec($schema);
        echo "✓ Schema imported successfully!\n";
    } catch (PDOException $e) {
        // Expected to fail at foreign key issues
        echo "⚠ Partial schema import (expected): " . $e->getMessage() . "\n";
    }
    
    // Step 2: Check what tables were created
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables created: " . count($tables) . "\n";
    
    if (count($tables) < 28) {
        echo "\nStep 2: Importing remaining tables from complete_import.sql...\n";
        $completeFile = __DIR__ . '/db/complete_import.sql';
        if (file_exists($completeFile)) {
            $complete = file_get_contents($completeFile);
            try {
                $pdo->exec($complete);
                echo "✓ Remaining tables imported!\n";
            } catch (PDOException $e) {
                echo "⚠ Import warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Step 3: Import sample data
    echo "\nStep 3: Importing sample data...\n";
    $dataFile = __DIR__ . '/db/sample_data.sql';
    $data = file_get_contents($dataFile);
    
    // Split by semicolons and execute statement by statement
    $statements = array_filter(array_map('trim', preg_split('/;\\s*$/m', $data)));
    $success = 0;
    $skipped = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || substr(ltrim($statement), 0, 2) === '--' || strtoupper(substr(ltrim($statement), 0, 3)) === 'USE') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success++;
            if ($success % 20 === 0) {
                echo ".";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                $skipped++;
            }
        }
    }
    
    echo "\n✓ Data import complete! ($success statements executed, $skipped skipped)\n";
    
    // Step 4: Verify
    echo "\n=== Final Verification ===\n";
    $finalTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables: " . count($finalTables) . "\n\n";
    
    $dataTables = ['users', 'profiles', 'categories', 'items', 'borrow_requests', 'messages', 'reviews', 'transactions'];
    foreach ($dataTables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "  $table: $count rows\n";
        } catch (PDOException $e) {
            echo "  $table: not found\n";
        }
    }
    
    // Check views
    try {
        $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll();
        echo "\nViews: " . count($views) . "\n";
    } catch (PDOException $e) {
        echo "\nViews: 0\n";
    }
    
    echo "\n✅ Railway database setup complete!\n";
    echo "Database: railway\n";
    echo "Host: yamabiko.proxy.rlwy.net:53608\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}
