<?php
/**
 * Test Railway MySQL Connection
 */

echo "Testing Railway MySQL Connection...\n";
echo "====================================\n\n";

try {
    include 'config/db.php';
    
    echo "✓ Connection successful!\n\n";
    
    // Get database name
    $dbName = $conn->query('SELECT DATABASE()')->fetchColumn();
    echo "Connected to database: $dbName\n\n";
    
    // List tables
    echo "Tables in database:\n";
    $tables = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "  (No tables found - database is empty)\n";
    } else {
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }
    
    echo "\n✓ Database connection is working correctly!\n";
    
} catch (Exception $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
