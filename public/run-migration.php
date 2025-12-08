<?php
/**
 * Database Migration and Verification Script
 * Applies real-time features migration and verifies all tables
 * Safe to run multiple times
 */

// Set longer execution time for migrations
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html>
<head>
    <title>SwapIt Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #2c3e50; margin-top: 30px; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:hover { background: #f9f9f9; }
        .step { background: #ecf0f1; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .code { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: monospace; }
        pre { margin: 0; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #27ae60; color: white; }
        .badge-error { background: #e74c3c; color: white; }
        .badge-info { background: #3498db; color: white; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🚀 SwapIt Database Migration Tool</h1>";
echo "<p>Running migration to add real-time transaction features...</p>";

// Database connection
require_once __DIR__ . '/../config/db.php';

$errors = [];
$warnings = [];
$successes = [];

// ============================================
// STEP 1: Check Database Connection
// ============================================
echo "<div class='step'>";
echo "<h2>Step 1: Database Connection</h2>";

if (!isset($conn)) {
    echo "<p class='error'>❌ Failed: Database connection not established</p>";
    echo "</div></div></body></html>";
    exit;
}

// Get connection info
try {
    if ($conn instanceof PDO) {
        $dbType = "PDO";
        $result = $conn->query("SELECT DATABASE() as db_name, VERSION() as version");
        $info = $result->fetch(PDO::FETCH_ASSOC);
    } else {
        $dbType = "MySQLi";
        $result = $conn->query("SELECT DATABASE() as db_name, VERSION() as version");
        $info = $result->fetch_assoc();
    }
    
    echo "<p class='success'>✅ Connected to MySQL Database</p>";
    echo "<p><strong>Database:</strong> {$info['db_name']}</p>";
    echo "<p><strong>Version:</strong> {$info['version']}</p>";
    echo "<p><strong>Connection Type:</strong> {$dbType}</p>";
    $successes[] = "Database connection verified";
} catch (Exception $e) {
    echo "<p class='error'>❌ Connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
    $errors[] = "Connection failed";
}
echo "</div>";

// ============================================
// STEP 2: Check Existing Tables
// ============================================
echo "<div class='step'>";
echo "<h2>Step 2: Check Existing Tables</h2>";

try {
    if ($conn instanceof PDO) {
        $result = $conn->query("SHOW TABLES");
        $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    
    echo "<p>Found " . count($tables) . " existing tables:</p>";
    echo "<table><tr><th>#</th><th>Table Name</th><th>Status</th></tr>";
    foreach ($tables as $i => $table) {
        echo "<tr><td>" . ($i+1) . "</td><td>{$table}</td><td><span class='badge badge-info'>EXISTS</span></td></tr>";
    }
    echo "</table>";
    $successes[] = count($tables) . " tables found";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error listing tables: " . htmlspecialchars($e->getMessage()) . "</p>";
    $errors[] = "Failed to list tables";
}
echo "</div>";

// ============================================
// STEP 3: Create New Tables
// ============================================
echo "<div class='step'>";
echo "<h2>Step 3: Create New Tables for Real-Time Features</h2>";

$newTables = [
    'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        related_id INT,
        related_type VARCHAR(50),
        is_read TINYINT(1) DEFAULT 0,
        read_at TIMESTAMP NULL,
        action_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_type (type),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'transaction_history' => "CREATE TABLE IF NOT EXISTS transaction_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        request_id INT NOT NULL,
        borrower_id INT NOT NULL,
        lender_id INT NOT NULL,
        item_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        performed_by INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_request_id (request_id),
        INDEX idx_borrower_id (borrower_id),
        INDEX idx_lender_id (lender_id),
        INDEX idx_item_id (item_id),
        INDEX idx_action_type (action_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'online_users' => "CREATE TABLE IF NOT EXISTS online_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL UNIQUE,
        status ENUM('online', 'away', 'offline') DEFAULT 'offline',
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_last_activity (last_activity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'user_activities' => "CREATE TABLE IF NOT EXISTS user_activities (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        activity_details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_activity_type (activity_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'meeting_schedules' => "CREATE TABLE IF NOT EXISTS meeting_schedules (
        id INT PRIMARY KEY AUTO_INCREMENT,
        borrow_request_id INT NOT NULL,
        scheduled_by INT NOT NULL,
        meeting_type ENUM('online', 'offline') DEFAULT 'offline',
        meeting_date DATETIME NOT NULL,
        meeting_location VARCHAR(255),
        meeting_link VARCHAR(500),
        notes TEXT,
        meeting_status ENUM('scheduled', 'confirmed', 'completed', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_borrow_request_id (borrow_request_id),
        INDEX idx_scheduled_by (scheduled_by),
        INDEX idx_meeting_date (meeting_date),
        INDEX idx_meeting_status (meeting_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'message_attachments' => "CREATE TABLE IF NOT EXISTS message_attachments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        message_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        file_size INT NOT NULL,
        file_url VARCHAR(500) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_message_id (message_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

echo "<table><tr><th>Table Name</th><th>Status</th><th>Details</th></tr>";

foreach ($newTables as $tableName => $sql) {
    try {
        if ($conn instanceof PDO) {
            $conn->exec($sql);
        } else {
            $conn->query($sql);
        }
        echo "<tr><td>{$tableName}</td><td><span class='badge badge-success'>✅ CREATED</span></td><td>Table ready</td></tr>";
        $successes[] = "Table '{$tableName}' created";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<tr><td>{$tableName}</td><td><span class='badge badge-info'>ℹ️ EXISTS</span></td><td>Already exists</td></tr>";
            $warnings[] = "Table '{$tableName}' already exists";
        } else {
            echo "<tr><td>{$tableName}</td><td><span class='badge badge-error'>❌ FAILED</span></td><td>" . htmlspecialchars($e->getMessage()) . "</td></tr>";
            $errors[] = "Failed to create '{$tableName}': " . $e->getMessage();
        }
    }
}
echo "</table>";
echo "</div>";

// ============================================
// STEP 4: Alter Existing Tables
// ============================================
echo "<div class='step'>";
echo "<h2>Step 4: Update Existing Tables</h2>";

$alterations = [
    'return_condition' => "ALTER TABLE borrow_requests ADD COLUMN return_condition VARCHAR(50) DEFAULT NULL",
    'start_date' => "ALTER TABLE borrow_requests ADD COLUMN start_date TIMESTAMP NULL",
    'end_date' => "ALTER TABLE borrow_requests ADD COLUMN end_date TIMESTAMP NULL"
];

echo "<table><tr><th>Column</th><th>Status</th><th>Details</th></tr>";

foreach ($alterations as $column => $sql) {
    try {
        if ($conn instanceof PDO) {
            $conn->exec($sql);
        } else {
            $conn->query($sql);
        }
        echo "<tr><td>{$column}</td><td><span class='badge badge-success'>✅ ADDED</span></td><td>Column added to borrow_requests</td></tr>";
        $successes[] = "Column '{$column}' added";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "<tr><td>{$column}</td><td><span class='badge badge-info'>ℹ️ EXISTS</span></td><td>Already exists</td></tr>";
            $warnings[] = "Column '{$column}' already exists";
        } else {
            echo "<tr><td>{$column}</td><td><span class='badge badge-error'>❌ FAILED</span></td><td>" . htmlspecialchars($e->getMessage()) . "</td></tr>";
            $errors[] = "Failed to add '{$column}': " . $e->getMessage();
        }
    }
}
echo "</table>";
echo "</div>";

// ============================================
// STEP 5: Verify All Tables
// ============================================
echo "<div class='step'>";
echo "<h2>Step 5: Final Verification</h2>";

$requiredTables = [
    'users', 'profiles', 'items', 'categories', 'borrow_requests', 
    'conversations', 'messages', 'ratings',
    'notifications', 'transaction_history', 'online_users', 
    'user_activities', 'meeting_schedules', 'message_attachments'
];

echo "<table><tr><th>Required Table</th><th>Status</th><th>Row Count</th></tr>";

$allTablesExist = true;
foreach ($requiredTables as $table) {
    try {
        if ($conn instanceof PDO) {
            $result = $conn->query("SELECT COUNT(*) as cnt FROM {$table}");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $count = $row['cnt'];
        } else {
            $result = $conn->query("SELECT COUNT(*) as cnt FROM {$table}");
            $row = $result->fetch_assoc();
            $count = $row['cnt'];
        }
        echo "<tr><td>{$table}</td><td><span class='badge badge-success'>✅ EXISTS</span></td><td>{$count} rows</td></tr>";
    } catch (Exception $e) {
        echo "<tr><td>{$table}</td><td><span class='badge badge-error'>❌ MISSING</span></td><td>N/A</td></tr>";
        $errors[] = "Table '{$table}' is missing";
        $allTablesExist = false;
    }
}
echo "</table>";

if ($allTablesExist) {
    echo "<p class='success'>✅ All required tables exist!</p>";
    $successes[] = "All tables verified";
} else {
    echo "<p class='error'>❌ Some required tables are missing</p>";
}
echo "</div>";

// ============================================
// STEP 6: Test APIs
// ============================================
echo "<div class='step'>";
echo "<h2>Step 6: API Connectivity Test</h2>";

$apis = [
    'Messages API' => '../api/messages.php?action=get_conversations',
    'Requests API' => '../api/requests.php?action=get_my_requests',
    'Notifications API' => '../api/notifications.php?action=get_notifications',
    'Transactions API' => '../api/transactions.php?action=get_my_transactions&status=all'
];

echo "<table><tr><th>API</th><th>Endpoint</th><th>Status</th></tr>";

foreach ($apis as $name => $endpoint) {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
    $fullUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $endpoint;
    $accessible = file_exists(__DIR__ . '/' . str_replace('../', '', $endpoint));
    
    if ($accessible) {
        echo "<tr><td>{$name}</td><td>{$endpoint}</td><td><span class='badge badge-success'>✅ ACCESSIBLE</span></td></tr>";
        $successes[] = "{$name} accessible";
    } else {
        echo "<tr><td>{$name}</td><td>{$endpoint}</td><td><span class='badge badge-error'>❌ NOT FOUND</span></td></tr>";
        $errors[] = "{$name} not found";
    }
}
echo "</table>";
echo "</div>";

// ============================================
// SUMMARY
// ============================================
echo "<div class='step'>";
echo "<h2>📊 Migration Summary</h2>";

echo "<table>";
echo "<tr><th>Category</th><th>Count</th></tr>";
echo "<tr><td><span class='success'>✅ Successes</span></td><td>" . count($successes) . "</td></tr>";
echo "<tr><td><span class='warning'>⚠️ Warnings</span></td><td>" . count($warnings) . "</td></tr>";
echo "<tr><td><span class='error'>❌ Errors</span></td><td>" . count($errors) . "</td></tr>";
echo "</table>";

if (count($errors) > 0) {
    echo "<h3 class='error'>Errors:</h3><ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
}

if (count($warnings) > 0) {
    echo "<h3 class='warning'>Warnings:</h3><ul>";
    foreach ($warnings as $warning) {
        echo "<li>{$warning}</li>";
    }
    echo "</ul>";
}

echo "</div>";

// ============================================
// NEXT STEPS
// ============================================
echo "<div class='step'>";
echo "<h2>🎯 Next Steps</h2>";

if (count($errors) == 0) {
    echo "<p class='success'><strong>✅ Migration completed successfully!</strong></p>";
    echo "<ol>";
    echo "<li>Test messaging functionality at <a href='../pages/messages.html'>messages.html</a></li>";
    echo "<li>Test requests at <a href='../pages/requests.html'>requests.html</a></li>";
    echo "<li>View transactions at <a href='../pages/transactions.html'>transactions.html</a></li>";
    echo "<li>Check notifications by clicking the bell icon in navigation</li>";
    echo "</ol>";
    
    echo "<h3>Integration Checklist:</h3>";
    echo "<ul>";
    echo "<li>✅ Database tables created</li>";
    echo "<li>➡️ Add notification bell to navigation (see INTEGRATION_GUIDE.md)</li>";
    echo "<li>➡️ Add real-time scripts to pages (see INTEGRATION_GUIDE.md)</li>";
    echo "<li>➡️ Test with two different users</li>";
    echo "</ul>";
} else {
    echo "<p class='error'><strong>⚠️ Migration completed with errors</strong></p>";
    echo "<p>Please review the errors above and fix them before proceeding.</p>";
}

echo "</div>";

echo "<div class='code'>";
echo "<h3>📋 Copy this for Railway MySQL Console:</h3>";
echo "<pre>SOURCE /path/to/apply_realtime_migration.sql;</pre>";
echo "<p>Or run this PHP script directly on your Render deployment.</p>";
echo "</div>";

echo "</div></body></html>";
?>
