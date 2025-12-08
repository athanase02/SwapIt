-- Create missing tables for SwapIt
-- Run this in Railway or via run-migration.php

-- Items table
CREATE TABLE IF NOT EXISTS items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    image_url VARCHAR(500),
    condition_status ENUM('new', 'like_new', 'good', 'fair', 'poor') DEFAULT 'good',
    availability_status ENUM('available', 'borrowed', 'unavailable') DEFAULT 'available',
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_availability (availability_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ratings table (using 'ratings' to match your schema)
CREATE TABLE IF NOT EXISTS ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    request_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_reviewee (reviewee_id),
    INDEX idx_request (request_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT IGNORE INTO categories (name, description, icon) VALUES
('Electronics', 'Phones, laptops, tablets, cameras', 'fa-laptop'),
('Books', 'Textbooks, novels, reference books', 'fa-book'),
('Sports', 'Sports equipment, gear, accessories', 'fa-football-ball'),
('Clothing', 'Clothes, shoes, accessories', 'fa-tshirt'),
('Tools', 'Hand tools, power tools, equipment', 'fa-wrench'),
('Music', 'Instruments, audio equipment', 'fa-music'),
('Gaming', 'Video games, consoles, accessories', 'fa-gamepad'),
('Kitchen', 'Appliances, cookware, utensils', 'fa-utensils'),
('Furniture', 'Tables, chairs, shelves', 'fa-couch'),
('Other', 'Miscellaneous items', 'fa-box');

SELECT 'Tables created successfully!' as Status;
SHOW TABLES;
