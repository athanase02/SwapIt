<?php
/**
 * Database Configuration
 * Supports MySQL on both local and Render environments
 * 
 * @author Athanase Abayo - Database configuration
 */

try {
    // Check if running on Render with MySQL (via environment variables)
    if (getenv('DB_HOST')) {
        // Render MySQL connection
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_NAME');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        
        $conn = new PDO($dsn, $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        error_log("SwapIt: Connected to MySQL on Render ($host:$port/$database)");
        
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
        
        error_log("SwapIt: Render MySQL tables initialized");
        
    } else {
        // Local MySQL using PDO
        $host = "localhost";
        $username = "root"; 
        $password = ""; 
        $database = "SI2025";
        
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Create database if doesn't exist
        $conn->exec("CREATE DATABASE IF NOT EXISTS $database");
        $conn->exec("USE $database");
        
        error_log("SwapIt: Connected to local MySQL database");
        
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
        
        error_log("SwapIt: Local MySQL tables initialized");
    }
    
} catch (PDOException $e) {
    error_log("SwapIt: MySQL Error - " . $e->getMessage());
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
?>