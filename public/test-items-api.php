<?php
/**
 * Quick API Test - Check if items API is working
 */
session_start();
header('Content-Type: application/json');

// Simulate logged in user for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Temporary for testing
}

require_once dirname(__DIR__) . '/api/listings.php';
?>
