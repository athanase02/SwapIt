<?php
/**
 * Google OAuth Callback Handler
 * Processes the OAuth callback from Google
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db_with_fallback.php';
require_once __DIR__ . '/google-oauth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if we have an authorization code
if (!isset($_GET['code'])) {
    // Check for error from Google
    if (isset($_GET['error'])) {
        $error = htmlspecialchars($_GET['error']);
        error_log("Google OAuth error: $error");
        header('Location: /pages/login.html?error=google_cancelled');
        exit;
    }
    
    // No code provided - redirect to login with error
    error_log("Google OAuth callback: No authorization code provided");
    header('Location: /pages/login.html?error=google_auth_failed');
    exit;
}

$code = $_GET['code'];
error_log("Google OAuth callback: Received authorization code");

// Database connection is in $conn variable from db_with_fallback.php

// Handle Google login
try {
    $result = handleGoogleLogin($code, $conn);
    
    error_log("Google OAuth result: " . json_encode($result));
    
    if ($result['success']) {
        // Success - redirect to dashboard
        error_log("Google OAuth: Login successful, redirecting to dashboard");
        header('Location: /pages/dashboard.html?google_login=success');
        exit;
    } else {
        // Failed - redirect to login with error
        error_log("Google OAuth failed: " . $result['message']);
        $errorMsg = urlencode($result['message']);
        header("Location: /pages/login.html?error=google_auth_failed&message=$errorMsg");
        exit;
    }
} catch (Exception $e) {
    error_log("Google OAuth exception: " . $e->getMessage());
    header("Location: /pages/login.html?error=google_auth_exception");
    exit;
}
