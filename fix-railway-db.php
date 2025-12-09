<?php
/**
 * Fix remaining database issues
 */

echo "Fixing remaining Railway database issues...\n";
echo "==========================================\n\n";

try {
    include 'config/db.php';
    
    echo "✓ Connected to Railway MySQL\n\n";
    
    // Try to add google_id column if it doesn't exist
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email");
        echo "✓ Added google_id column to users table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  google_id column already exists\n";
        } else {
            echo "  Error adding google_id: " . $e->getMessage() . "\n";
        }
    }
    
    // Try to create index
    try {
        $conn->exec("CREATE INDEX idx_google_id ON users(google_id)");
        echo "✓ Created index on google_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "  Index already exists\n";
        } else {
            echo "  Error creating index: " . $e->getMessage() . "\n";
        }
    }
    
    // Update null google_id values
    try {
        $conn->exec("UPDATE users SET google_id = NULL WHERE google_id = ''");
        echo "✓ Updated empty google_id values\n";
    } catch (PDOException $e) {
        echo "  Error updating google_id: " . $e->getMessage() . "\n";
    }
    
    echo "\n==========================================\n";
    echo "Database status:\n\n";
    
    // Show table count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'railway'");
    $result = $stmt->fetch();
    echo "Total tables: " . $result['count'] . "\n\n";
    
    // Show user count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "\n\n";
    
    // Show some sample data
    echo "Sample users:\n";
    $stmt = $conn->query("SELECT id, email, full_name FROM users LIMIT 5");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['full_name']} ({$row['email']})\n";
    }
    
    echo "\n✓ Database setup complete!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
