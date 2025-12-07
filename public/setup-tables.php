<?php
// Setup Missing Database Tables
// This script creates all required tables for the core features
// Access via: https://swapit-tjoj.onrender.com/setup-tables.php

header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../config/db.php';

$results = [
    'success' => false,
    'tables_created' => [],
    'tables_verified' => [],
    'errors' => []
];

try {
    // Check connection - config/db.php uses $conn variable
    if (!isset($conn)) {
        throw new Exception("Database connection not available");
    }

    // 1. Create conversations table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS conversations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user1_id INT NOT NULL,
                user2_id INT NOT NULL,
                item_id INT DEFAULT NULL,
                last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user1 (user1_id),
                INDEX idx_user2 (user2_id),
                INDEX idx_users (user1_id, user2_id),
                UNIQUE KEY unique_conversation (user1_id, user2_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'conversations';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $results['errors'][] = "conversations: " . $e->getMessage();
        } else {
            $results['tables_verified'][] = 'conversations';
        }
    }

    // 2. Create messages table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                conversation_id INT NOT NULL,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                message_text TEXT NOT NULL,
                item_id INT DEFAULT NULL,
                is_read TINYINT(1) DEFAULT 0,
                read_at TIMESTAMP NULL,
                is_deleted_by_sender TINYINT(1) DEFAULT 0,
                is_deleted_by_receiver TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_conversation (conversation_id),
                INDEX idx_sender (sender_id),
                INDEX idx_receiver (receiver_id),
                INDEX idx_is_read (is_read),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'messages';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $results['errors'][] = "messages: " . $e->getMessage();
        } else {
            $results['tables_verified'][] = 'messages';
        }
    }

    // 3. Create borrow_requests table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS borrow_requests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                item_id INT NOT NULL,
                borrower_id INT NOT NULL,
                lender_id INT NOT NULL,
                borrow_start_date DATE NOT NULL,
                borrow_end_date DATE NOT NULL,
                total_price DECIMAL(10, 2) NOT NULL,
                security_deposit DECIMAL(10, 2) DEFAULT 0.00,
                pickup_location VARCHAR(255),
                borrower_message TEXT,
                lender_notes TEXT,
                status ENUM('pending', 'accepted', 'rejected', 'active', 'completed', 'cancelled') DEFAULT 'pending',
                cancellation_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_item (item_id),
                INDEX idx_borrower (borrower_id),
                INDEX idx_lender (lender_id),
                INDEX idx_status (status),
                INDEX idx_dates (borrow_start_date, borrow_end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'borrow_requests';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $results['errors'][] = "borrow_requests: " . $e->getMessage();
        } else {
            $results['tables_verified'][] = 'borrow_requests';
        }
    }

    // 4. Create reviews table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT PRIMARY KEY AUTO_INCREMENT,
                reviewer_id INT NOT NULL,
                reviewed_user_id INT NOT NULL,
                borrow_request_id INT NOT NULL,
                rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
                review_type ENUM('borrower_to_lender', 'lender_to_borrower') NOT NULL,
                title VARCHAR(255),
                comment TEXT,
                is_anonymous TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_reviewer (reviewer_id),
                INDEX idx_reviewed_user (reviewed_user_id),
                INDEX idx_request (borrow_request_id),
                INDEX idx_rating (rating),
                INDEX idx_type (review_type),
                UNIQUE KEY unique_review (reviewer_id, borrow_request_id, review_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'reviews';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $results['errors'][] = "reviews: " . $e->getMessage();
        } else {
            $results['tables_verified'][] = 'reviews';
        }
    }

    // 5. Create notifications table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                related_id INT,
                is_read TINYINT(1) DEFAULT 0,
                read_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_user_unread (user_id, is_read),
                INDEX idx_created (created_at),
                INDEX idx_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'notifications';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $results['errors'][] = "notifications: " . $e->getMessage();
        } else {
            $results['tables_verified'][] = 'notifications';
        }
    }

    // Get list of all tables
    $stmt = $conn->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results['success'] = true;
    $results['all_tables'] = $all_tables;
    $results['message'] = 'Migration completed successfully';

} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
