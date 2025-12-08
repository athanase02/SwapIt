-- =====================================================
-- LOGIN ATTEMPTS TABLE FOR RAILWAY
-- Copy and paste this into Railway MySQL Query console
-- Safe to run multiple times
-- =====================================================

USE railway;

-- Create login_attempts table for security and rate limiting
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

-- Verify table was created
SELECT 'login_attempts table created successfully!' as Status;

-- Show table structure
DESCRIBE login_attempts;

-- Count records
SELECT COUNT(*) as TotalAttempts FROM login_attempts;
