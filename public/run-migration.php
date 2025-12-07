<?php
/**
 * Database Migration Script
 * Run this ONCE to add new tables for core features
 * Access: https://your-render-url.com/run-migration.php
 */

// Prevent running this script more than once
$lock_file = __DIR__ . '/../logs/migration.lock';
if (file_exists($lock_file)) {
    http_response_code(200);
    die(json_encode(['success' => false, 'message' => 'Migration already completed! Delete logs/migration.lock to run again.']));
}

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

$result = [
    'success' => false,
    'tables_created' => [],
    'columns_added' => [],
    'indexes_added' => [],
    'errors' => []
];

try {
    // 1. Create meeting_schedules table (without foreign keys first)
    try {
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
            INDEX idx_request (borrow_request_id),
            INDEX idx_date (meeting_date),
            INDEX idx_status (meeting_status)
        )";
        $conn->exec($sql);
        $result['tables_created'][] = 'meeting_schedules';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $result['errors'][] = 'meeting_schedules: ' . $e->getMessage();
        } else {
            $result['tables_created'][] = 'meeting_schedules (already exists)';
        }
    }

    // 2. Create user_activities table (without foreign keys)
    try {
        $sql = "CREATE TABLE IF NOT EXISTS user_activities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            activity_type ENUM('request_created', 'request_accepted', 'request_rejected', 
                              'review_submitted', 'item_listed', 'message_sent', 
                              'meeting_scheduled', 'item_borrowed', 'item_returned') NOT NULL,
            related_id INT,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_created (created_at),
            INDEX idx_type (activity_type)
        )";
        $conn->exec($sql);
        $result['tables_created'][] = 'user_activities';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $result['errors'][] = 'user_activities: ' . $e->getMessage();
        } else {
            $result['tables_created'][] = 'user_activities (already exists)';
        }
    }

    // 3. Add columns to profiles table
    $profileColumns = [
        'rating_average' => "ALTER TABLE profiles ADD COLUMN rating_average DECIMAL(3, 2) DEFAULT 0.00",
        'total_reviews' => "ALTER TABLE profiles ADD COLUMN total_reviews INT DEFAULT 0",
        'total_items_borrowed' => "ALTER TABLE profiles ADD COLUMN total_items_borrowed INT DEFAULT 0",
        'total_items_lent' => "ALTER TABLE profiles ADD COLUMN total_items_lent INT DEFAULT 0"
    ];

    foreach ($profileColumns as $col => $sql) {
        try {
            $conn->exec($sql);
            $result['columns_added'][] = "profiles.$col";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                $result['errors'][] = "profiles.$col: " . $e->getMessage();
            } else {
                $result['columns_added'][] = "profiles.$col (already exists)";
            }
        }
    }

    // 4. Add column to conversations table
    try {
        $conn->exec("ALTER TABLE conversations ADD COLUMN last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        $result['columns_added'][] = 'conversations.last_message_at';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            $result['errors'][] = 'conversations.last_message_at: ' . $e->getMessage();
        } else {
            $result['columns_added'][] = 'conversations.last_message_at (already exists)';
        }
    }

    // 5. Add indexes
    $indexes = [
        'messages_conversation' => "ALTER TABLE messages ADD INDEX idx_conversation (conversation_id)",
        'messages_receiver' => "ALTER TABLE messages ADD INDEX idx_receiver (receiver_id)",
        'messages_is_read' => "ALTER TABLE messages ADD INDEX idx_is_read (is_read)",
        'borrow_requests_status' => "ALTER TABLE borrow_requests ADD INDEX idx_status (status)",
        'borrow_requests_borrower' => "ALTER TABLE borrow_requests ADD INDEX idx_borrower (borrower_id)",
        'borrow_requests_lender' => "ALTER TABLE borrow_requests ADD INDEX idx_lender (lender_id)",
        'reviews_reviewed_user' => "ALTER TABLE reviews ADD INDEX idx_reviewed_user (reviewed_user_id)",
        'reviews_rating' => "ALTER TABLE reviews ADD INDEX idx_rating (rating)",
        'notifications_user_unread' => "ALTER TABLE notifications ADD INDEX idx_user_unread (user_id, is_read)",
        'profiles_rating' => "ALTER TABLE profiles ADD INDEX idx_rating (rating_average)"
    ];

    foreach ($indexes as $name => $sql) {
        try {
            $conn->exec($sql);
            $result['indexes_added'][] = $name;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false && strpos($e->getMessage(), 'duplicate') === false) {
                $result['errors'][] = "$name: " . $e->getMessage();
            } else {
                $result['indexes_added'][] = "$name (already exists)";
            }
        }
    }

    // Create lock file
    $lock_dir = dirname($lock_file);
    if (!is_dir($lock_dir)) {
        mkdir($lock_dir, 0755, true);
    }
    file_put_contents($lock_file, json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => true
    ]));

    $result['success'] = true;
    $result['message'] = 'Migration completed successfully!';
    $result['security_warning'] = 'IMPORTANT: Delete this file (run-migration.php) for security!';

} catch (PDOException $e) {
    $result['success'] = false;
    $result['message'] = 'Migration failed';
    $result['errors'][] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
