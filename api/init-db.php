<?php
/**
 * Database Initialization Script
 * Creates essential tables if they don't exist
 */

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Function to create essential tables
function initializeDatabase($conn) {
    $tables = [];
    
    // Users table
    $tables[] = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        avatar_url TEXT,
        phone VARCHAR(20),
        google_id VARCHAR(255),
        is_verified BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        account_type ENUM('student', 'staff', 'admin') DEFAULT 'student',
        email_verified_at TIMESTAMP NULL,
        last_login_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_account_type (account_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    // Profiles table
    $tables[] = "CREATE TABLE IF NOT EXISTS profiles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL UNIQUE,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        bio TEXT,
        avatar_url TEXT,
        location VARCHAR(255),
        university VARCHAR(255) DEFAULT 'Ashesi University',
        student_id VARCHAR(50),
        graduation_year INT,
        rating_average DECIMAL(3,2) DEFAULT 5.00,
        total_reviews INT DEFAULT 0,
        total_items_listed INT DEFAULT 0,
        total_items_borrowed INT DEFAULT 0,
        total_items_lent INT DEFAULT 0,
        trust_score INT DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_rating (rating_average),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $success = true;
    $errors = [];
    
    foreach ($tables as $sql) {
        if (!$conn->query($sql)) {
            $success = false;
            $errors[] = $conn->error;
            error_log("Failed to create table: " . $conn->error);
        }
    }
    
    return ['success' => $success, 'errors' => $errors];
}

// Initialize tables
$result = initializeDatabase($conn);

header('Content-Type: application/json');
echo json_encode([
    'success' => $result['success'],
    'message' => $result['success'] ? 'Database initialized successfully' : 'Failed to initialize database',
    'errors' => $result['errors']
]);
?>
