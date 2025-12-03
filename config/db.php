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
?>
