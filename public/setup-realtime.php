<?php
/**
 * Real-Time System Migration Script
 * Run this to set up all database tables for real-time features
 */

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>SwapIt - Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; }
        .success { color: #4ade80; padding: 10px; background: #f0fdf4; border-left: 4px solid #4ade80; margin: 10px 0; }
        .error { color: #ef4444; padding: 10px; background: #fef2f2; border-left: 4px solid #ef4444; margin: 10px 0; }
        .info { color: #3b82f6; padding: 10px; background: #eff6ff; border-left: 4px solid #3b82f6; margin: 10px 0; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🚀 SwapIt Real-Time System Migration</h1>
    <p>This script will create all necessary database tables for the real-time features.</p>
";

try {
    echo "<div class='info'>Starting database migration...</div>";
    
    // Read the SQL migration file
    $sqlFile = dirname(__DIR__) . '/db/realtime_system_migration.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read migration file");
    }
    
    echo "<div class='info'>Found migration file. Executing SQL statements...</div>";
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($statement) {
            return !empty($statement) && 
                   !preg_match('/^--/', $statement) && 
                   !preg_match('/^\/\*.*\*\/$/', $statement);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Execute each statement
    foreach ($statements as $statement) {
        try {
            if (trim($statement)) {
                $conn->exec($statement . ';');
                $successCount++;
                
                // Extract table name for logging
                if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $matches)) {
                    echo "<div class='success'>✓ Created/verified table: {$matches[1]}</div>";
                } elseif (preg_match('/ALTER TABLE `?(\w+)`?/i', $statement, $matches)) {
                    echo "<div class='success'>✓ Updated table: {$matches[1]}</div>";
                }
            }
        } catch (PDOException $e) {
            $errorCount++;
            // Ignore "table already exists" and "duplicate column" errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate column') === false) {
                $errors[] = $e->getMessage();
                echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    echo "<h2>Migration Summary</h2>";
    echo "<div class='info'>";
    echo "<strong>Successful operations:</strong> $successCount<br>";
    echo "<strong>Errors (ignored if tables exist):</strong> $errorCount<br>";
    echo "</div>";
    
    if (empty($errors)) {
        echo "<div class='success'><h3>✅ Migration completed successfully!</h3>";
        echo "<p>All tables have been created or verified. Your database is ready for real-time features.</p>";
        echo "</div>";
    } else {
        echo "<div class='error'><h3>⚠️ Migration completed with some errors</h3>";
        echo "<p>Some non-critical errors occurred. Most tables should be created correctly.</p>";
        echo "<pre>" . implode("\n\n", array_map('htmlspecialchars', $errors)) . "</pre>";
        echo "</div>";
    }
    
    // Verify tables were created
    echo "<h2>Database Tables Verification</h2>";
    $tables = [
        'users',
        'items',
        'user_online_status',
        'conversations',
        'messages',
        'borrow_requests',
        'meeting_schedules',
        'transactions',
        'ratings',
        'notifications',
        'user_activities'
    ];
    
    echo "<div class='info'>";
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table '$table' exists<br>";
            } else {
                echo "✗ Table '$table' NOT FOUND<br>";
            }
        } catch (PDOException $e) {
            echo "✗ Could not verify table '$table': " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>The database is now ready for real-time features</li>";
    echo "<li>You can now test the messaging, requests, and notifications systems</li>";
    echo "<li>Try logging in with two different users to test real-time interactions</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='/pages/browse.html' style='display: inline-block; padding: 10px 20px; background: #7ef9ff; color: #0a0b10; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Browse Page</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'><h3>❌ Migration Failed</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
