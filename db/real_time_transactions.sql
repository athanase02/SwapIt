-- Enhanced Schema for Real-Time User Interactions
-- Adds missing tables and columns for complete transaction flow

-- Add meeting_schedules table if not exists
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
    INDEX idx_request (borrow_request_id),
    INDEX idx_scheduled_by (scheduled_by),
    INDEX idx_date (meeting_date),
    INDEX idx_status (meeting_status),
    FOREIGN KEY (borrow_request_id) REFERENCES borrow_requests(id) ON DELETE CASCADE
);

-- Add user_activities table for tracking user actions
CREATE TABLE IF NOT EXISTS user_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    related_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (activity_type),
    INDEX idx_created (created_at)
);

-- Add real-time notifications table
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
    INDEX idx_user (user_id),
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at),
    INDEX idx_type (type)
);

-- Add transaction_history for tracking exchanges
CREATE TABLE IF NOT EXISTS transaction_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    borrow_request_id INT NOT NULL,
    borrower_id INT NOT NULL,
    lender_id INT NOT NULL,
    item_id INT NOT NULL,
    transaction_type ENUM('pickup', 'return', 'payment', 'deposit') NOT NULL,
    amount DECIMAL(10, 2),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    transaction_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request (borrow_request_id),
    INDEX idx_borrower (borrower_id),
    INDEX idx_lender (lender_id),
    INDEX idx_type (transaction_type),
    INDEX idx_status (status),
    FOREIGN KEY (borrow_request_id) REFERENCES borrow_requests(id) ON DELETE CASCADE
);

-- Add online_users table for real-time presence
CREATE TABLE IF NOT EXISTS online_users (
    user_id INT PRIMARY KEY,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('online', 'away', 'offline') DEFAULT 'online',
    INDEX idx_status (status),
    INDEX idx_last_seen (last_seen)
);

-- Add message_attachments for file sharing
CREATE TABLE IF NOT EXISTS message_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100),
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message (message_id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);

-- Update borrow_requests to add more tracking columns if they don't exist
ALTER TABLE borrow_requests 
ADD COLUMN IF NOT EXISTS pickup_confirmed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS return_confirmed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS payment_confirmed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS actual_return_date DATE NULL,
ADD COLUMN IF NOT EXISTS condition_on_return TEXT NULL,
ADD COLUMN IF NOT EXISTS late_fee DECIMAL(10, 2) DEFAULT 0,
ADD INDEX IF NOT EXISTS idx_pickup_confirmed (pickup_confirmed_at),
ADD INDEX IF NOT EXISTS idx_return_confirmed (return_confirmed_at);

-- Update conversations to track typing indicators
ALTER TABLE conversations
ADD COLUMN IF NOT EXISTS user1_typing TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS user2_typing TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS user1_last_typing TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS user2_last_typing TIMESTAMP NULL;

-- Create view for active transactions
CREATE OR REPLACE VIEW active_transactions AS
SELECT 
    br.id as request_id,
    br.item_id,
    br.borrower_id,
    br.lender_id,
    br.status,
    br.borrow_start_date,
    br.borrow_end_date,
    br.total_price,
    i.title as item_title,
    i.image_url as item_image,
    borrower.full_name as borrower_name,
    borrower.email as borrower_email,
    lender.full_name as lender_name,
    lender.email as lender_email,
    ms.meeting_date as next_meeting,
    ms.meeting_location,
    ms.meeting_status,
    br.pickup_confirmed_at,
    br.return_confirmed_at,
    CASE 
        WHEN br.return_confirmed_at IS NOT NULL THEN 'completed'
        WHEN br.pickup_confirmed_at IS NOT NULL THEN 'in_use'
        WHEN br.status = 'accepted' THEN 'ready_for_pickup'
        ELSE br.status
    END as transaction_stage
FROM borrow_requests br
JOIN items i ON br.item_id = i.id
JOIN users borrower ON br.borrower_id = borrower.id
JOIN users lender ON br.lender_id = lender.id
LEFT JOIN meeting_schedules ms ON br.id = ms.borrow_request_id AND ms.meeting_status != 'cancelled'
WHERE br.status IN ('accepted', 'active', 'completed')
ORDER BY br.updated_at DESC;
