<?php
/**
 * SwapIt Authentication API
 * Handles user authentication, registration, and session management
 * 
 * @author Athanase Abayo - Core architecture and session management
 * @author Mabinty Mambu - User registration and profile updates
 * @author Olivier Kwizera - Security enhancements and input validation
 * @version 2.0
 */

// Configure session to persist across page navigation
// Security: Session cookies configured for maximum security
ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1); // Prevent XSS attacks on session cookies
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
session_start();

// Security Headers - OWASP #5: Security Misconfiguration
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff'); // Prevent MIME type sniffing
header('X-Frame-Options: DENY'); // Prevent clickjacking
header('X-XSS-Protection: 1; mode=block'); // Enable XSS filter
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:;"); // OWASP #8: Content Security Policy

require_once dirname(__DIR__) . '/config/db_with_fallback.php';

/**
 * Security Event Logger
 * OWASP #9: Security Logging and Monitoring Failures - Prevention
 * Logs all security-relevant events for audit and monitoring
 * 
 * @author Olivier Kwizera - Logging implementation
 * @author Athanase Abayo - Event tracking
 */
class SecurityLogger {
    private static $logFile = __DIR__ . '/../logs/security.log';
    
    /**
     * Log security events with timestamp and context
     * @param string $event - Event type (login, logout, failed_login, etc.)
     * @param string $message - Event details
     * @param array $context - Additional context data
     * @author Olivier Kwizera
     */
    public static function log($event, $message, $context = []) {
        // Ensure log directory exists
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'guest';
        
        $logEntry = sprintf(
            "[%s] EVENT: %s | USER: %s | IP: %s | MESSAGE: %s | CONTEXT: %s | USER_AGENT: %s\n",
            $timestamp,
            strtoupper($event),
            $userId,
            $ip,
            $message,
            json_encode($context),
            $userAgent
        );
        
        // Write to log file
        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also log critical events to system error log
        if (in_array($event, ['failed_login', 'account_locked', 'suspicious_activity'])) {
            error_log("SECURITY ALERT: $message");
        }
    }
}

/**
 * Rate Limiter for brute force protection
 * OWASP #4: Insecure Design - Prevention
 * OWASP #7: Identification and Authentication Failures - Prevention
 * 
 * @author Olivier Kwizera - Rate limiting logic
 * @author Athanase Abayo - Session integration
 */
class RateLimiter {
    private static $storageFile = __DIR__ . '/../logs/rate_limit.json';
    
    /**
     * Check if request is within rate limit
     * @param string $identifier - Usually IP address or email
     * @param int $maxAttempts - Maximum attempts allowed
     * @param int $timeWindow - Time window in seconds
     * @return array - ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     * @author Olivier Kwizera
     */
    public static function check($identifier, $maxAttempts = 5, $timeWindow = 900) {
        // Ensure storage directory exists
        $storageDir = dirname(self::$storageFile);
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        
        // Load existing rate limit data
        $data = [];
        if (file_exists(self::$storageFile)) {
            $content = @file_get_contents(self::$storageFile);
            $data = json_decode($content, true) ?: [];
        }
        
        $now = time();
        $key = hash('sha256', $identifier); // Hash identifier for privacy
        
        // Clean old entries
        if (isset($data[$key])) {
            $data[$key]['attempts'] = array_filter($data[$key]['attempts'], function($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            });
        } else {
            $data[$key] = ['attempts' => [], 'locked_until' => 0];
        }
        
        // Check if account is locked
        if ($data[$key]['locked_until'] > $now) {
            $remaining = $data[$key]['locked_until'] - $now;
            SecurityLogger::log('rate_limit_exceeded', "Rate limit exceeded for: $identifier", ['remaining_seconds' => $remaining]);
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $data[$key]['locked_until'],
                'locked' => true,
                'message' => "Too many attempts. Account locked for " . ceil($remaining / 60) . " minutes."
            ];
        }
        
        // Check current attempts
        $attemptCount = count($data[$key]['attempts']);
        
        if ($attemptCount >= $maxAttempts) {
            // Lock account for 15 minutes
            $data[$key]['locked_until'] = $now + 900;
            self::save($data);
            SecurityLogger::log('account_locked', "Account temporarily locked: $identifier", ['attempts' => $attemptCount]);
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $data[$key]['locked_until'],
                'locked' => true,
                'message' => 'Too many attempts. Account locked for 15 minutes.'
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => $maxAttempts - $attemptCount,
            'reset_time' => $now + $timeWindow,
            'locked' => false
        ];
    }
    
    /**
     * Record a failed attempt
     * @param string $identifier - Usually IP address or email
     * @author Olivier Kwizera
     */
    public static function recordAttempt($identifier) {
        $storageDir = dirname(self::$storageFile);
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        
        $data = [];
        if (file_exists(self::$storageFile)) {
            $content = @file_get_contents(self::$storageFile);
            $data = json_decode($content, true) ?: [];
        }
        
        $key = hash('sha256', $identifier);
        if (!isset($data[$key])) {
            $data[$key] = ['attempts' => [], 'locked_until' => 0];
        }
        
        $data[$key]['attempts'][] = time();
        self::save($data);
    }
    
    /**
     * Reset attempts for an identifier (on successful login)
     * @param string $identifier - Usually IP address or email
     * @author Olivier Kwizera
     */
    public static function reset($identifier) {
        if (!file_exists(self::$storageFile)) {
            return;
        }
        
        $data = json_decode(@file_get_contents(self::$storageFile), true) ?: [];
        $key = hash('sha256', $identifier);
        
        if (isset($data[$key])) {
            $data[$key] = ['attempts' => [], 'locked_until' => 0];
            self::save($data);
        }
    }
    
    /**
     * Save rate limit data to file
     * @param array $data - Rate limit data
     * @author Olivier Kwizera
     */
    private static function save($data) {
        @file_put_contents(self::$storageFile, json_encode($data), LOCK_EX);
    }
}

/**
 * Input Sanitization Utility
 * OWASP #3: Injection Prevention
 * 
 * @author Olivier Kwizera - Security implementation
 */
class InputSanitizer {
    /**
     * Sanitize user input to prevent XSS attacks
     * @param string $data - Raw input data
     * @return string - Sanitized data
     * @author Olivier Kwizera
     */
    public static function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /**
     * Validate email format
     * @param string $email - Email address to validate
     * @return bool - True if valid, false otherwise
     * @author Olivier Kwizera
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate base64 image data
     * OWASP #8: Software and Data Integrity Failures - Prevention
     * Ensures uploaded images are valid and safe
     * 
     * @param string $base64Data - Base64 encoded image data
     * @return array - ['valid' => bool, 'message' => string, 'mime_type' => string]
     * @author Olivier Kwizera - File validation and security
     * @author Victoria Ama Nyonato - Image processing
     */
    public static function validateImageData($base64Data) {
        // Remove data URI prefix if present
        if (strpos($base64Data, 'data:image') === 0) {
            $parts = explode(',', $base64Data);
            if (count($parts) === 2) {
                $base64Data = $parts[1];
            }
        }
        
        // Decode base64
        $imageData = base64_decode($base64Data, true);
        if ($imageData === false) {
            SecurityLogger::log('invalid_upload', 'Invalid base64 image data received');
            return ['valid' => false, 'message' => 'Invalid image data', 'mime_type' => null];
        }
        
        // Check file size (max 5MB)
        $size = strlen($imageData);
        if ($size > 5 * 1024 * 1024) {
            SecurityLogger::log('invalid_upload', 'Image file too large', ['size' => $size]);
            return ['valid' => false, 'message' => 'Image too large (max 5MB)', 'mime_type' => null];
        }
        
        // Verify it's actually an image using finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        
        // Allow only specific image types
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes)) {
            SecurityLogger::log('invalid_upload', 'Invalid image type uploaded', ['mime_type' => $mimeType]);
            return ['valid' => false, 'message' => 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP', 'mime_type' => $mimeType];
        }
        
        // Additional check: try to create image resource to verify integrity
        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            SecurityLogger::log('invalid_upload', 'Corrupted image data');
            return ['valid' => false, 'message' => 'Corrupted image file', 'mime_type' => null];
        }
        imagedestroy($image);
        
        return ['valid' => true, 'message' => 'Valid image', 'mime_type' => $mimeType];
    }
}

/**
 * User Service - Handles user data operations
 * 
 * @author Athanase Abayo - Core user operations
 * @author Mabinty Mambu - Profile management
 */
class UserService {
    private $conn;

    /**
     * Initialize user service with database connection
     * @param mysqli $conn - Database connection
     * @author Athanase Abayo
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Find user by email address
     * @param string $email - User email
     * @return array|null - User data or null if not found
     * @author Athanase Abayo
     */
    public function findByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id, email, password_hash, full_name, avatar_url FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Find user by ID
     * @param int $userId - User ID
     * @return array|null - User data or null if not found
     * @author Athanase Abayo
     */
    public function findById($userId) {
        $stmt = $this->conn->prepare("SELECT id, email, full_name, avatar_url FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Create new user account
     * @param string $email - User email
     * @param string $passwordHash - Hashed password
     * @param string $fullName - User's full name
     * @return int - New user ID
     * @throws Exception - On creation failure
     * @author Mabinty Mambu - User registration
     */
    public function createUser($email, $passwordHash, $fullName) {
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Insert into users table
            $stmt = $this->conn->prepare("INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $passwordHash, $fullName);
            $stmt->execute();
            $userId = $this->conn->insert_id;
            
            // Create profile
            $stmt = $this->conn->prepare("INSERT INTO profiles (user_id, full_name, email) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $fullName, $email);
            $stmt->execute();
            
            $this->conn->commit();
            return $userId;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Update user profile information
     * @param int $userId - User ID
     * @param array $updates - Fields to update
     * @return bool - Success status
     * @author Mabinty Mambu - Profile updates
     */
    public function updateProfile($userId, $updates) {
        $fields = [];
        $types = "";
        $params = [];
        
        if (isset($updates['avatar_url'])) {
            $fields[] = "avatar_url = ?";
            $types .= "s";
            $params[] = $updates['avatar_url'];
        }
        
        if (isset($updates['full_name'])) {
            $fields[] = "full_name = ?";
            $types .= "s";
            $params[] = $updates['full_name'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $types .= "i";
        $params[] = $userId;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
}

/**
 * Authentication Controller - Handles authentication logic
 * 
 * @author Athanase Abayo - Core authentication flow
 * @author Mabinty Mambu - Registration process
 * @author Olivier Kwizera - Security and validation
 */
class AuthController {
    private $userService;

    /**
     * Initialize authentication controller
     * @param UserService $userService - User service instance
     * @author Athanase Abayo
     */
    public function __construct($userService) {
        $this->userService = $userService;
    }

    /**
     * Handle user login
     * OWASP #1: Broken Access Control - Prevention
     * OWASP #7: Identification and Authentication Failures - Prevention
     * Includes rate limiting and comprehensive security logging
     * 
     * @return array - Response data
     * @author Athanase Abayo - Core login logic
     * @author Olivier Kwizera - Validation and rate limiting
     */
    public function login() {
        $email = InputSanitizer::sanitize($_POST['email']);
        $password = $_POST['password'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Input validation
        if (empty($email) || empty($password)) {
            SecurityLogger::log('invalid_login_attempt', 'Empty credentials submitted', ['email' => $email]);
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        // Rate limiting check - prevent brute force attacks
        $rateLimitCheck = RateLimiter::check($email . $ip, 5, 900); // 5 attempts per 15 minutes
        if (!$rateLimitCheck['allowed']) {
            SecurityLogger::log('rate_limit_exceeded', 'Login rate limit exceeded', [
                'email' => $email,
                'ip' => $ip,
                'remaining_seconds' => $rateLimitCheck['reset_time'] - time()
            ]);
            return [
                'success' => false,
                'message' => $rateLimitCheck['message'],
                'locked' => true,
                'retry_after' => $rateLimitCheck['reset_time']
            ];
        }
        
        // Find user
        $user = $this->userService->findByEmail($email);
        
        // Verify password
        if ($user && password_verify($password, $user['password_hash'])) {
            // Reset rate limiter on successful login
            RateLimiter::reset($email . $ip);
            
            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $ip; // Track IP for session hijacking detection
            
            // Log successful login
            SecurityLogger::log('login_success', 'User logged in successfully', [
                'user_id' => $user['id'],
                'email' => $email
            ]);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'avatar_url' => $user['avatar_url']
                ]
            ];
        }
        
        // Failed login - record attempt
        RateLimiter::recordAttempt($email . $ip);
        SecurityLogger::log('login_failed', 'Invalid credentials', [
            'email' => $email,
            'remaining_attempts' => $rateLimitCheck['remaining'] - 1
        ]);
        
        return [
            'success' => false,
            'message' => 'Invalid email or password',
            'remaining_attempts' => max(0, $rateLimitCheck['remaining'] - 1)
        ];
    }

    /**
     * Handle user registration
     * OWASP #7: Identification and Authentication Failures - Prevention
     * Includes enhanced password validation and security logging
     * 
     * @return array - Response data
     * @author Mabinty Mambu - Registration implementation
     * @author Olivier Kwizera - Input validation and security
     */
    public function signup() {
        $email = InputSanitizer::sanitize($_POST['email']);
        $password = $_POST['password'];
        $fullName = InputSanitizer::sanitize($_POST['full_name']);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Validate inputs
        if (empty($email) || empty($password) || empty($fullName)) {
            SecurityLogger::log('signup_failed', 'Missing required fields', ['email' => $email]);
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Enhanced password validation
        if (strlen($password) < 6) {
            SecurityLogger::log('signup_failed', 'Password too short', ['email' => $email]);
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Check password strength (basic check)
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            SecurityLogger::log('signup_failed', 'Weak password', ['email' => $email]);
            return ['success' => false, 'message' => 'Password must contain both letters and numbers'];
        }
        
        if (!InputSanitizer::validateEmail($email)) {
            SecurityLogger::log('signup_failed', 'Invalid email format', ['email' => $email]);
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        // Check if email already exists
        if ($this->userService->findByEmail($email)) {
            SecurityLogger::log('signup_failed', 'Email already exists', ['email' => $email]);
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password with secure algorithm
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $userId = $this->userService->createUser($email, $passwordHash, $fullName);
            SecurityLogger::log('signup_success', 'New user registered', [
                'user_id' => $userId,
                'email' => $email
            ]);
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (Exception $e) {
            SecurityLogger::log('signup_error', 'Registration failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Handle user logout
     * OWASP #1: Broken Access Control - Session termination
     * 
     * @return array - Response data
     * @author Olivier Kwizera - Session cleanup and security logging
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? 'unknown';
        
        // Log logout event
        SecurityLogger::log('logout', 'User logged out', ['user_id' => $userId]);
        
        // Clear all session variables
        $_SESSION = array();
        
        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Update user profile
     * OWASP #1: Broken Access Control - User can only update own profile
     * OWASP #8: Software and Data Integrity Failures - Image validation
     * 
     * @return array - Response data
     * @author Mabinty Mambu - Profile update implementation
     * @author Olivier Kwizera - Security validation
     */
    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            SecurityLogger::log('unauthorized_access', 'Attempted profile update without authentication');
            return ['success' => false, 'message' => 'Not authenticated'];
        }
        
        $userId = $_SESSION['user_id'];
        $updates = [];
        
        // Validate and sanitize avatar upload
        if (isset($_POST['avatar_url'])) {
            $avatarData = $_POST['avatar_url'];
            
            // Validate image if it's base64 data
            if (strpos($avatarData, 'data:image') === 0) {
                $validation = InputSanitizer::validateImageData($avatarData);
                if (!$validation['valid']) {
                    SecurityLogger::log('invalid_avatar_upload', $validation['message'], [
                        'user_id' => $userId
                    ]);
                    return ['success' => false, 'message' => $validation['message']];
                }
            }
            
            $updates['avatar_url'] = $avatarData;
        }
        
        // Sanitize full name
        if (isset($_POST['full_name'])) {
            $updates['full_name'] = InputSanitizer::sanitize($_POST['full_name']);
            $_SESSION['full_name'] = $updates['full_name'];
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No updates provided'];
        }
        
        try {
            $this->userService->updateProfile($userId, $updates);
            $user = $this->userService->findById($userId);
            
            SecurityLogger::log('profile_updated', 'User profile updated successfully', [
                'user_id' => $userId,
                'fields' => array_keys($updates)
            ]);
            
            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user
            ];
        } catch (Exception $e) {
            SecurityLogger::log('profile_update_error', 'Profile update failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()];
        }
    }

    /**
     * Check authentication status
     * OWASP #1: Broken Access Control - Session validation
     * Includes session hijacking detection
     * 
     * @return array - Response data
     * @author Athanase Abayo - Core authentication check
     * @author Olivier Kwizera - Session security validation
     */
    public function checkAuth() {
        if (isset($_SESSION['user_id'])) {
            // Session hijacking detection - verify IP address hasn't changed
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $sessionIp = $_SESSION['ip_address'] ?? null;
            
            if ($sessionIp && $sessionIp !== $currentIp) {
                SecurityLogger::log('suspicious_activity', 'Possible session hijacking detected', [
                    'user_id' => $_SESSION['user_id'],
                    'session_ip' => $sessionIp,
                    'current_ip' => $currentIp
                ]);
                
                // Optionally terminate session for security
                // For now, just log it
            }
            
            $user = $this->userService->findById($_SESSION['user_id']);
            
            if ($user) {
                return [
                    'success' => true,
                    'authenticated' => true,
                    'user' => $user
                ];
            } else {
                // User no longer exists in database
                SecurityLogger::log('invalid_session', 'Session references non-existent user', [
                    'user_id' => $_SESSION['user_id']
                ]);
                session_destroy();
            }
        }
        
        return [
            'success' => true,
            'authenticated' => false,
            'user' => null
        ];
    }

    /**
     * Create a new listing
     * @return array - Response data
     * @author Mabinty Mambu - Listing creation
     */
    public function createListing() {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'User not authenticated'];
        }

        // For now, listings are stored in localStorage on client-side
        // This is a placeholder for future server-side implementation
        return ['success' => true, 'message' => 'Listing functionality uses client storage'];
    }

    /**
     * Create an order/swap request
     * @return array - Response data
     * @author Mabinty Mambu - Order creation
     */
    public function createOrder() {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'User not authenticated'];
        }

        // For now, orders are handled client-side
        // This is a placeholder for future server-side implementation
        return ['success' => true, 'message' => 'Order functionality uses client storage'];
    }
}

// Initialize services
$userService = new UserService($conn);
$authController = new AuthController($userService);

// Route requests to appropriate controller methods
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    // Handle JSON input for Google OAuth
    if (empty($action)) {
        $jsonInput = file_get_contents('php://input');
        $data = json_decode($jsonInput, true);
        $action = $data['action'] ?? '';
    }
    
    switch ($action) {
        case 'login':
            echo json_encode($authController->login());
            break;
        case 'signup':
            echo json_encode($authController->signup());
            break;
        case 'logout':
            echo json_encode($authController->logout());
            break;
        case 'update_profile':
            echo json_encode($authController->updateProfile());
            break;
        case 'create_listing':
            echo json_encode($authController->createListing());
            break;
        case 'create_order':
            echo json_encode($authController->createOrder());
            break;
        case 'google_login':
            require_once __DIR__ . '/google-oauth.php';
            $code = $data['code'] ?? '';
            if ($code) {
                echo json_encode(handleGoogleLogin($code, $conn));
            } else {
                echo json_encode(['success' => false, 'message' => 'No authorization code provided']);
            }
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    exit;
}

// Handle GET requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'check_auth') {
        echo json_encode($authController->checkAuth());
        exit;
    }
    
    if ($action === 'get_google_config') {
        require_once __DIR__ . '/google-oauth.php';
        echo json_encode(getGoogleConfig());
        exit;
    }
}
?>