-- SwapIt Database Updates
-- New tables for messaging, meeting schedules, and user activities
-- Run this script to add the new functionality

-- Meeting Schedules Table
CREATE TABLE IF NOT EXISTS meeting_schedules (
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
);

-- User Activities Table (for dashboard activity feed)
CREATE TABLE IF NOT EXISTS user_activities (
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
);

-- Add indexes to existing tables for better performance
ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_conversation (conversation_id);
ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_receiver (receiver_id);
ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_is_read (is_read);
ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_created (created_at);

ALTER TABLE borrow_requests ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE borrow_requests ADD INDEX IF NOT EXISTS idx_borrower (borrower_id);
ALTER TABLE borrow_requests ADD INDEX IF NOT EXISTS idx_lender (lender_id);
ALTER TABLE borrow_requests ADD INDEX IF NOT EXISTS idx_dates (borrow_start_date, borrow_end_date);

ALTER TABLE reviews ADD INDEX IF NOT EXISTS idx_reviewed_user (reviewed_user_id);
ALTER TABLE reviews ADD INDEX IF NOT EXISTS idx_rating (rating);
ALTER TABLE reviews ADD INDEX IF NOT EXISTS idx_type (review_type);

ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_user_unread (user_id, is_read);
ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_created (created_at);

-- Update profiles table to ensure rating columns exist
ALTER TABLE profiles 
ADD COLUMN IF NOT EXISTS rating_average DECIMAL(3, 2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS total_reviews INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_items_borrowed INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_items_lent INT DEFAULT 0;

-- Add index to profiles
ALTER TABLE profiles ADD INDEX IF NOT EXISTS idx_rating (rating_average);

-- Update conversations table to track last message time
ALTER TABLE conversations 
ADD COLUMN IF NOT EXISTS last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Create a view for dashboard statistics
CREATE OR REPLACE VIEW user_dashboard_stats AS
SELECT 
    u.id as user_id,
    p.rating_average,
    p.total_reviews,
    p.total_items_borrowed,
    p.total_items_lent,
    (SELECT COUNT(*) FROM items WHERE user_id = u.id) as total_items_listed,
    (SELECT COUNT(*) FROM borrow_requests WHERE borrower_id = u.id AND status = 'pending') as pending_borrow_requests,
    (SELECT COUNT(*) FROM borrow_requests WHERE lender_id = u.id AND status = 'pending') as pending_lend_requests,
    (SELECT COUNT(*) FROM messages WHERE receiver_id = u.id AND is_read = 0) as unread_messages,
    (SELECT COUNT(*) FROM notifications WHERE user_id = u.id AND is_read = 0) as unread_notifications
FROM users u
LEFT JOIN profiles p ON u.id = p.user_id;

-- Insert sample activity for existing users (optional)
-- INSERT INTO user_activities (user_id, activity_type, description)
-- SELECT id, 'item_listed', CONCAT('Joined SwapIt on ', DATE_FORMAT(created_at, '%M %d, %Y'))
-- FROM users
-- WHERE id NOT IN (SELECT DISTINCT user_id FROM user_activities);

DELIMITER //

-- Trigger to log item listing activity
CREATE TRIGGER IF NOT EXISTS after_item_insert
AFTER INSERT ON items
FOR EACH ROW
BEGIN
    INSERT INTO user_activities (user_id, activity_type, related_id, description)
    VALUES (NEW.user_id, 'item_listed', NEW.id, CONCAT('Listed ', NEW.title));
END//

-- Trigger to update conversation last message time
CREATE TRIGGER IF NOT EXISTS after_message_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    UPDATE conversations 
    SET last_message_at = NEW.created_at 
    WHERE id = NEW.conversation_id;
END//

DELIMITER ;

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE ON meeting_schedules TO 'your_db_user'@'%';
-- GRANT SELECT, INSERT ON user_activities TO 'your_db_user'@'%';

SELECT 'Database schema updated successfully!' as Status;
