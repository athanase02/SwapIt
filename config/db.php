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
// Database name: SI2025 (SwapIt 2025)

// Check if running on Render (environment variables are set)
if (getenv('DB_HOST')) {
    // Production configuration (Render)
    $host = getenv('DB_HOST');
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');
    $database = getenv('DB_NAME') ?: 'SI2025';
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
    // For API requests, return JSON error instead of dying
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Database connection failed',
            'details' => 'Please ensure MySQL is running and credentials are correct'
        ]);
        exit;
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Try to create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");
?>
