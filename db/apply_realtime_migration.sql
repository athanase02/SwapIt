-- SwapIt Database Migration Script
-- Safely creates all required tables for real-time features
-- Safe to run multiple times (uses IF NOT EXISTS)

-- ============================================
-- 1. NOTIFICATIONS TABLE
-- ============================================
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
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. TRANSACTION HISTORY TABLE
-- ============================================
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
    INDEX idx_request_id (request_id),
    INDEX idx_borrower_id (borrower_id),
    INDEX idx_lender_id (lender_id),
    INDEX idx_item_id (item_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. ONLINE USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS online_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    status ENUM('online', 'away', 'offline') DEFAULT 'offline',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. USER ACTIVITIES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS user_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. MEETING SCHEDULES TABLE
-- ============================================
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
    INDEX idx_borrow_request_id (borrow_request_id),
    INDEX idx_scheduled_by (scheduled_by),
    INDEX idx_meeting_date (meeting_date),
    INDEX idx_meeting_status (meeting_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. MESSAGE ATTACHMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS message_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ALTER EXISTING TABLES
-- ============================================

-- Add return_condition to borrow_requests if not exists
SET @dbname = DATABASE();
SET @tablename = 'borrow_requests';
SET @columnname = 'return_condition';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(50) DEFAULT NULL')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add start_date to borrow_requests if not exists
SET @columnname = 'start_date';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TIMESTAMP NULL')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add end_date to borrow_requests if not exists
SET @columnname = 'end_date';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TIMESTAMP NULL')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 8. CREATE VIEW FOR ACTIVE TRANSACTIONS
-- ============================================
DROP VIEW IF EXISTS active_transactions;

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

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Show all tables
SELECT 'All Tables Created:' as Status;
SHOW TABLES;

-- Show new tables structure
SELECT 'notifications table structure:' as Info;
DESCRIBE notifications;

SELECT 'transaction_history table structure:' as Info;
DESCRIBE transaction_history;

SELECT 'online_users table structure:' as Info;
DESCRIBE online_users;

SELECT 'user_activities table structure:' as Info;
DESCRIBE user_activities;

SELECT 'meeting_schedules table structure:' as Info;
DESCRIBE meeting_schedules;

SELECT 'message_attachments table structure:' as Info;
DESCRIBE message_attachments;

-- Show borrow_requests new columns
SELECT 'borrow_requests updated structure:' as Info;
DESCRIBE borrow_requests;

SELECT 'Migration completed successfully!' as Status;
