<?php
/**
 * Database Configuration
 * Supports both MySQL (local) and PostgreSQL (Render)
 * 
 * @author Athanase Abayo - Database configuration
 */

// Check if running on Render with PostgreSQL
if (getenv('DATABASE_URL')) {
    // Render PostgreSQL connection string
    $databaseUrl = getenv('DATABASE_URL');
    
    try {
        $conn = new PDO($databaseUrl);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        error_log("SwapIt: Connected to PostgreSQL database");
        
        // Create tables for PostgreSQL
        $conn->exec("CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            avatar_url TEXT,
            phone VARCHAR(20),
            google_id VARCHAR(255),
            is_verified BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $conn->exec("CREATE TABLE IF NOT EXISTS profiles (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            bio TEXT,
            avatar_url TEXT,
            location VARCHAR(255),
            rating_average DECIMAL(3,2) DEFAULT 5.00,
            total_reviews INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        error_log("SwapIt: PostgreSQL tables initialized");
        
    } catch (PDOException $e) {
        error_log("SwapIt: PostgreSQL Error - " . $e->getMessage());
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed',
                'details' => $e->getMessage()
            ]);
            exit;
        }
        die("Database connection failed: " . $e->getMessage());
    }
} else {
    // Local MySQL using PDO
    $host = "localhost";
    $username = "root"; 
    $password = ""; 
    $database = "SI2025";
    
    try {
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Create database if doesn't exist
        $conn->exec("CREATE DATABASE IF NOT EXISTS $database");
        $conn->exec("USE $database");
        
        error_log("SwapIt: Connected to MySQL database");
        
        // Create tables for MySQL
        $conn->exec("CREATE TABLE IF NOT EXISTS users (
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
        
        $conn->exec("CREATE TABLE IF NOT EXISTS profiles (
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
        
        error_log("SwapIt: MySQL tables initialized");
        
    } catch (PDOException $e) {
        error_log("MySQL connection failed: " . $e->getMessage());
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed',
                'details' => $e->getMessage()
            ]);
            exit;
        }
        die("Connection failed: " . $e->getMessage());
    }
}
?>