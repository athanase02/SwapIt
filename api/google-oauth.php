<?php
/**
 * Google OAuth Configuration and Handler
 * @author Athanase Abayo - Google OAuth backend implementation
 */

// Load environment variables from .env file
$envFile = __DIR__ . '/../config/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set as environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Google OAuth Configuration - now loaded from environment variables
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost:3000/api/google-callback.php');

/**
 * Get Google OAuth configuration
 */
function getGoogleConfig() {
    return [
        'success' => true,
        'clientId' => GOOGLE_CLIENT_ID,
        'configured' => (GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com')
    ];
}

/**
 * Exchange authorization code for access token
 */
function exchangeCodeForToken($code) {
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    
    $postData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    // Use file_get_contents as fallback when cURL is not available
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($postData),
            'ignore_errors' => true,
            'timeout' => 30
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($tokenUrl, false, $context);

    if ($response === false) {
        $error = error_get_last();
        error_log("Google token exchange failed: Unable to connect - " . ($error['message'] ?? 'Unknown error'));
        return null;
    }

    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        error_log("Google token exchange failed: " . ($result['error_description'] ?? $result['error']));
        error_log("Full response: " . $response);
        return null;
    }

    error_log("Google token exchange successful");
    return $result;
}

/**
 * Get user info from Google using access token
 */
function getGoogleUserInfo($accessToken) {
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';

    $options = [
        'http' => [
            'header'  => "Authorization: Bearer $accessToken\r\n",
            'method'  => 'GET',
            'ignore_errors' => true,
            'timeout' => 30
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($userInfoUrl, false, $context);

    if ($response === false) {
        $error = error_get_last();
        error_log("Google user info failed: Unable to connect - " . ($error['message'] ?? 'Unknown error'));
        return null;
    }

    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        error_log("Google user info failed: " . ($result['error_description'] ?? $result['error']));
        return null;
    }

    error_log("Google user info retrieved successfully for: " . ($result['email'] ?? 'unknown'));
    return $result;
}

/**
 * Handle Google OAuth login
 */
function handleGoogleLogin($code, $db) {
    // Exchange code for access token
    $tokenData = exchangeCodeForToken($code);
    
    if (!$tokenData || !isset($tokenData['access_token'])) {
        return [
            'success' => false,
            'message' => 'Failed to exchange authorization code'
        ];
    }

    // Get user info from Google
    $userInfo = getGoogleUserInfo($tokenData['access_token']);
    
    if (!$userInfo || !isset($userInfo['email'])) {
        return [
            'success' => false,
            'message' => 'Failed to get user information from Google'
        ];
    }

    $email = $userInfo['email'];
    $fullName = $userInfo['name'] ?? 'Google User';
    $googleId = $userInfo['id'] ?? null;
    $avatar = $userInfo['picture'] ?? null;

    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // User exists - update Google ID if not set
        if (!$user['google_id'] && $googleId) {
            $updateStmt = $db->prepare("UPDATE users SET google_id = ?, avatar_url = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $googleId, $avatar, $user['id']);
            $updateStmt->execute();
        }
    } else {
        // Create new user
        $dummyPassword = password_hash(random_bytes(32), PASSWORD_DEFAULT); // Google users don't have passwords
        $stmt = $db->prepare("INSERT INTO users (email, full_name, google_id, avatar_url, password_hash, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $email, $fullName, $googleId, $avatar, $dummyPassword);
        
        if (!$stmt->execute()) {
            error_log("Failed to create Google user: " . $stmt->error);
            return [
                'success' => false,
                'message' => 'Failed to create user account'
            ];
        }

        $userId = $db->insert_id;

        // Create user profile
        $profileStmt = $db->prepare("INSERT INTO profiles (user_id) VALUES (?)");
        $profileStmt->bind_param("i", $userId);
        $profileStmt->execute();

        // Fetch the newly created user
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }

    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    return [
        'success' => true,
        'message' => 'Successfully signed in with Google',
        'user' => [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'avatar_url' => $user['avatar_url']
        ]
    ];
}
