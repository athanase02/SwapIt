<?php
/**
 * Test Connection with Render Environment Variables
 * Simulates Render environment to test Railway connection
 */

echo "Testing Render → Railway MySQL Connection\n";
echo "==========================================\n\n";

// Simulate Render environment variables (from your Render dashboard)
putenv('DB_HOST=crossover.proxy.rlwy.net');
putenv('DB_PORT=20980');
putenv('DB_NAME=railway');
putenv('DB_USER=root');
putenv('DB_PASSWORD=nLPPhjVDjtuxSKJiPHYQlxSKkvdGjtQx');

echo "Environment variables set (simulating Render):\n";
echo "  DB_HOST: " . getenv('DB_HOST') . "\n";
echo "  DB_PORT: " . getenv('DB_PORT') . "\n";
echo "  DB_NAME: " . getenv('DB_NAME') . "\n";
echo "  DB_USER: " . getenv('DB_USER') . "\n";
echo "  DB_PASSWORD: " . str_repeat('*', strlen(getenv('DB_PASSWORD'))) . "\n\n";

try {
    // This will use the Render DB_HOST path in config/db.php
    include 'config/db.php';
    
    echo "✓ Connection successful!\n\n";
    
    // Test basic queries
    echo "Database Information:\n";
    
    // Get database name
    $dbName = $conn->query('SELECT DATABASE()')->fetchColumn();
    echo "  Connected to: $dbName\n";
    
    // Count tables
    $tableCount = $conn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$dbName'")->fetchColumn();
    echo "  Total tables: $tableCount\n";
    
    // Count users
    $userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "  Total users: $userCount\n\n";
    
    // Get sample users
    echo "Sample users:\n";
    $stmt = $conn->query("SELECT id, email, full_name, is_verified FROM users LIMIT 5");
    while ($row = $stmt->fetch()) {
        $verified = $row['is_verified'] ? '✓' : '✗';
        echo "  [{$row['id']}] {$row['full_name']} - {$row['email']} $verified\n";
    }
    
    // Get table list
    echo "\nTables in database:\n";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $chunks = array_chunk($tables, 5);
    foreach ($chunks as $chunk) {
        echo "  " . implode(", ", $chunk) . "\n";
    }
    
    echo "\n✓ All tests passed! Render will be able to connect to Railway.\n";
    echo "\nNext steps:\n";
    echo "1. Add these environment variables to Render dashboard\n";
    echo "2. Deploy your application to Render\n";
    echo "3. Check Render logs for: 'Connected to Railway MySQL from Render'\n";
    
} catch (Exception $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Troubleshooting:\n";
    echo "- Verify Railway database is running\n";
    echo "- Check credentials match Railway dashboard\n";
    echo "- Ensure Railway allows external connections\n";
    exit(1);
}
