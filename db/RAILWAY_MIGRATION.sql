-- =====================================================
-- RAILWAY MYSQL DIRECT MIGRATION SCRIPT
-- Copy and paste this ENTIRE script into Railway MySQL Query console
-- Safe to run multiple times
-- =====================================================

USE railway;

-- Drop existing views if any
DROP VIEW IF EXISTS active_transactions;

-- =====================================================
-- TABLE 1: notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
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
    KEY idx_user_id (user_id),
    KEY idx_type (type),
    KEY idx_is_read (is_read),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE 2: transaction_history
-- =====================================================
CREATE TABLE IF NOT EXISTS transaction_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    borrower_id INT NOT NULL,
    lender_id INT NOT NULL,
    item_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    performed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_request_id (request_id),
    KEY idx_borrower_id (borrower_id),
    KEY idx_lender_id (lender_id),
    KEY idx_item_id (item_id),
    KEY idx_action_type (action_type),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE 3: online_users
-- =====================================================
CREATE TABLE IF NOT EXISTS online_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    status ENUM('online', 'away', 'offline') DEFAULT 'offline',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_status (status),
    KEY idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE 4: user_activities
-- =====================================================
CREATE TABLE IF NOT EXISTS user_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_activity_type (activity_type),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE 5: meeting_schedules
-- =====================================================
CREATE TABLE IF NOT EXISTS meeting_schedules (
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
    KEY idx_borrow_request_id (borrow_request_id),
    KEY idx_scheduled_by (scheduled_by),
    KEY idx_meeting_date (meeting_date),
    KEY idx_meeting_status (meeting_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE 6: message_attachments
-- =====================================================
CREATE TABLE IF NOT EXISTS message_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ALTER EXISTING TABLES (Add new columns if not exist)
-- =====================================================

-- Check and add return_condition to borrow_requests
ALTER TABLE borrow_requests 
ADD COLUMN IF NOT EXISTS return_condition VARCHAR(50) DEFAULT NULL;

-- Check and add start_date to borrow_requests
ALTER TABLE borrow_requests 
ADD COLUMN IF NOT EXISTS start_date TIMESTAMP NULL;

-- Check and add end_date to borrow_requests
ALTER TABLE borrow_requests 
ADD COLUMN IF NOT EXISTS end_date TIMESTAMP NULL;

-- =====================================================
-- CREATE VIEW: active_transactions
-- =====================================================
CREATE VIEW active_transactions AS
SELECT 
    br.id as request_id,
    br.borrower_id,
    br.lender_id,
    br.item_id,
    br.status,
    br.start_date,
    br.end_date,
    i.title as item_title,
    i.image_url as item_image,
    u1.username as borrower_name,
    u1.avatar as borrower_avatar,
    u2.username as lender_name,
    u2.avatar as lender_avatar,
    th.action_type as last_action,
    th.created_at as last_action_time
FROM borrow_requests br
JOIN items i ON br.item_id = i.id
JOIN users u1 ON br.borrower_id = u1.id
JOIN users u2 ON br.lender_id = u2.id
LEFT JOIN (
    SELECT request_id, action_type, created_at,
           ROW_NUMBER() OVER (PARTITION BY request_id ORDER BY created_at DESC) as rn
    FROM transaction_history
) th ON br.id = th.request_id AND th.rn = 1
WHERE br.status IN ('scheduled', 'active');

-- =====================================================
-- TABLE 7: login_attempts (Security & Rate Limiting)
-- =====================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier_hash VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of email + IP',
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL COMMENT 'Supports IPv4 and IPv6',
    attempt_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    success BOOLEAN DEFAULT FALSE,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_hash (identifier_hash),
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
SELECT 'Migration Complete!' as Status;
SELECT 'Checking tables...' as Info;

-- Show all tables
SHOW TABLES;

-- Count records in new tables
SELECT 'notifications' as TableName, COUNT(*) as RecordCount FROM notifications
UNION ALL
SELECT 'transaction_history', COUNT(*) FROM transaction_history
UNION ALL
SELECT 'online_users', COUNT(*) FROM online_users
UNION ALL
SELECT 'user_activities', COUNT(*) FROM user_activities
UNION ALL
SELECT 'meeting_schedules', COUNT(*) FROM meeting_schedules
UNION ALL
SELECT 'message_attachments', COUNT(*) FROM message_attachments
UNION ALL
SELECT 'login_attempts', COUNT(*) FROM login_attempts;

SELECT '✅ All tables created successfully!' as FinalStatus;
