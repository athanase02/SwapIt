<?php
/**
 * Database Migration Script
 * Run this ONCE to add new tables for core features
 * 
 * SECURITY: Delete this file after running!
 * Access: https://your-render-url.com/api/run-migration.php
 */

// Prevent running this script more than once
$lock_file = __DIR__ . '/../logs/migration.lock';
if (file_exists($lock_file)) {
    die("Migration already run! Delete logs/migration.lock to run again.");
}

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Database Migration</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#00ff00;}";
echo ".success{color:#00ff00;}.error{color:#ff0000;}.info{color:#00aaff;}</style></head><body>";
echo "<h1>SwapIt Database Migration</h1>";
echo "<p class='info'>Running migration script...</p><hr>";

$errors = [];
$success = [];

try {
    // 1. Create meeting_schedules table
    echo "<h3>Creating meeting_schedules table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS meeting_schedules (
        id INT PRIMARY KEY AUTO_INCREMENT,
        borrow_request_id INT NOT NULL,
        scheduled_by INT NOT NULL,
        meeting_type ENUM('online', 'offline') NOT NULL,
        meeting_date DATETIME NOT NULL,
        meeting_location VARCHAR(255),
        meeting_link TEXT,
        meeting_status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (borrow_request_id) REFERENCES borrow_requests(id) ON DELETE CASCADE,
        FOREIGN KEY (scheduled_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_request (borrow_request_id),
        INDEX idx_date (meeting_date),
        INDEX idx_status (meeting_status)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ meeting_schedules table created</p>";
    $success[] = "meeting_schedules table";

    // 2. Create user_activities table
    echo "<h3>Creating user_activities table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS user_activities (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        activity_type ENUM('request_created', 'request_accepted', 'request_rejected', 
                          'review_submitted', 'item_listed', 'message_sent', 
                          'meeting_scheduled', 'item_borrowed', 'item_returned') NOT NULL,
        related_id INT,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_created (created_at),
        INDEX idx_type (activity_type)
    )";
    $conn->exec($sql);
    echo "<p class='success'>✓ user_activities table created</p>";
    $success[] = "user_activities table";

    // 3. Add columns to profiles table
    echo "<h3>Updating profiles table...</h3>";
    try {
        $conn->exec("ALTER TABLE profiles ADD COLUMN rating_average DECIMAL(3, 2) DEFAULT 0.00");
        echo "<p class='success'>✓ Added rating_average column</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>→ rating_average column already exists</p>";
        } else {
            throw $e;
        }
    }

    try {
        $conn->exec("ALTER TABLE profiles ADD COLUMN total_reviews INT DEFAULT 0");
        echo "<p class='success'>✓ Added total_reviews column</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>→ total_reviews column already exists</p>";
        }
    }

    try {
        $conn->exec("ALTER TABLE profiles ADD COLUMN total_items_borrowed INT DEFAULT 0");
        echo "<p class='success'>✓ Added total_items_borrowed column</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>→ total_items_borrowed column already exists</p>";
        }
    }

    try {
        $conn->exec("ALTER TABLE profiles ADD COLUMN total_items_lent INT DEFAULT 0");
        echo "<p class='success'>✓ Added total_items_lent column</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>→ total_items_lent column already exists</p>";
        }
    }

    // 4. Add column to conversations table
    echo "<h3>Updating conversations table...</h3>";
    try {
        $conn->exec("ALTER TABLE conversations ADD COLUMN last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✓ Added last_message_at column</p>";
        $success[] = "conversations table updated";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>→ last_message_at column already exists</p>";
        } else {
            throw $e;
        }
    }

    // 5. Add indexes
    echo "<h3>Adding indexes for performance...</h3>";
    $indexes = [
        "ALTER TABLE messages ADD INDEX idx_conversation (conversation_id)",
        "ALTER TABLE messages ADD INDEX idx_receiver (receiver_id)",
        "ALTER TABLE messages ADD INDEX idx_is_read (is_read)",
        "ALTER TABLE borrow_requests ADD INDEX idx_status (status)",
        "ALTER TABLE borrow_requests ADD INDEX idx_borrower (borrower_id)",
        "ALTER TABLE borrow_requests ADD INDEX idx_lender (lender_id)",
        "ALTER TABLE reviews ADD INDEX idx_reviewed_user (reviewed_user_id)",
        "ALTER TABLE reviews ADD INDEX idx_rating (rating)",
        "ALTER TABLE notifications ADD INDEX idx_user_unread (user_id, is_read)",
        "ALTER TABLE profiles ADD INDEX idx_rating (rating_average)"
    ];

    foreach ($indexes as $index_sql) {
        try {
            $conn->exec($index_sql);
            echo "<p class='success'>✓ Index added</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "<p class='info'>→ Index already exists</p>";
            } else {
                echo "<p class='error'>! Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // Create lock file to prevent re-running
    $lock_dir = dirname($lock_file);
    if (!is_dir($lock_dir)) {
        mkdir($lock_dir, 0755, true);
    }
    file_put_contents($lock_file, date('Y-m-d H:i:s'));

    echo "<hr>";
    echo "<h2 class='success'>✓ MIGRATION COMPLETED SUCCESSFULLY!</h2>";
    echo "<p>Created/Updated:</p><ul>";
    foreach ($success as $item) {
        echo "<li class='success'>$item</li>";
    }
    echo "</ul>";
    
    echo "<h3 class='error'>IMPORTANT SECURITY STEP:</h3>";
    echo "<p class='error'>Delete this file immediately: api/run-migration.php</p>";
    echo "<p>Or rename it to prevent unauthorized access</p>";

} catch (PDOException $e) {
    echo "<h2 class='error'>✗ MIGRATION FAILED</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Check your database connection and try again.</p>";
}

echo "</body></html>";
?>
