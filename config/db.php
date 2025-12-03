<?php
/**
 * Database Configuration
 * 
 * IMPORTANT: Update these credentials before uploading to school server!
 * Get your database credentials from your instructor or server documentation
 * 
 * @author Athanase Abayo - Database configuration
 */

// Database configuration
// Database name: SI2025 (SwapIt 2025) for local, swapit_db for production

// Check if running on Render (environment variables are set)
if (getenv('DB_HOST')) {
    // Production configuration (Render)
    $host = getenv('DB_HOST');
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');
    $database = getenv('DB_NAME') ?: 'swapit_db';
} else {
    // Local development configuration
    $host = "localhost";
    $username = "root"; 
    $password = ""; 
    $database = "SI2025";
}

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    // Log error for debugging
    error_log("Database connection failed: " . $conn->connect_error);
    
    // For API requests, return JSON error instead of dying
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Database connection failed',
            'details' => 'Unable to connect to database. Please check configuration.',
            'debug' => getenv('DB_HOST') ? 'Using environment variables' : 'Using localhost'
        ]);
        exit;
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Try to create database if it doesn't exist
$createDb = $conn->query("CREATE DATABASE IF NOT EXISTS $database");
if (!$createDb) {
    error_log("Failed to create database: " . $conn->error);
}

// Select database
if (!$conn->select_db($database)) {
    error_log("Failed to select database: " . $conn->error);
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database not found',
            'details' => 'Database does not exist and could not be created'
        ]);
        exit;
    }
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");

// Auto-initialize essential tables if they don't exist
function ensureTablesExist($conn) {
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        // Create users table
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            avatar_url TEXT,
            phone VARCHAR(20),
            google_id VARCHAR(255),
            is_verified BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Create profiles table
        $conn->query("CREATE TABLE IF NOT EXISTS profiles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL UNIQUE,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            bio TEXT,
            avatar_url TEXT,
            location VARCHAR(255),
            rating_average DECIMAL(3,2) DEFAULT 5.00,
            total_reviews INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        error_log("SwapIt: Essential database tables created");
    }
}

// Initialize tables
ensureTablesExist($conn);
?>
