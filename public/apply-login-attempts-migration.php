<?php
/**
 * Apply Login Attempts Table Migration
 * Creates the login_attempts table for database-based rate limiting
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Login Attempts Migration</title></head><body>";
echo "<h1>Creating login_attempts Table</h1>";

try {
    // Connect to database
    require_once __DIR__ . '/../config/db.php';
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    echo "<p>✓ Connected to database</p>";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/../db/create_login_attempts_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "<p>✓ SQL file loaded</p>";
    
    // Execute SQL
    $conn->exec($sql);
    echo "<p style='color: green; font-weight: bold;'>✓ login_attempts table created successfully!</p>";
    
    // Verify table exists
    $result = $conn->query("SHOW TABLES LIKE 'login_attempts'");
    if ($result && $result->rowCount() > 0) {
        echo "<p>✓ Table verified in database</p>";
        
        // Show table structure
        $result = $conn->query("DESCRIBE login_attempts");
        echo "<h2>Table Structure:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['Extra'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li><a href='test-login-attempts.php'>Run Tests</a> - Test the login attempts tracking system</li>";
    echo "<li>The RateLimiter class in <code>api/auth.php</code> now uses database storage</li>";
    echo "<li>All login attempts (failed and successful) will be tracked in the database</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
