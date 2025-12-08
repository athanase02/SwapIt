<?php
// Router for SwapIt
// This file handles routing for the entire application

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$uri_parts = parse_url($request_uri);
$path = rtrim($uri_parts['path'], '/');

// Handle health check first (for Render.com)
if ($path === '/health.php' || $path === '/health') {
    if (file_exists(__DIR__ . '/health.php')) {
        include __DIR__ . '/health.php';
        exit;
    }
}

// Handle root URL - serve home.html
if (empty($path) || $path === '/') {
    if (file_exists(__DIR__ . '/home.html')) {
        include __DIR__ . '/home.html';
        exit;
    }
}

// Handle API routes
if (strpos($path, '/api/') === 0) {
    // Extract the API file name
    $api_file = str_replace('/api/', '', $path);
    
    // Map of allowed API files for security (use absolute paths)
    $base_dir = dirname(__DIR__);
    $allowed_files = [
        'auth.php' => $base_dir . '/api/auth.php',
        'profile.php' => $base_dir . '/api/profile.php',
        'listings.php' => $base_dir . '/api/listings.php',
        'items.php' => $base_dir . '/api/items.php',
        'messages.php' => $base_dir . '/api/messages.php',
        'notifications.php' => $base_dir . '/api/notifications.php',
        'requests.php' => $base_dir . '/api/requests.php',
        'online-status.php' => $base_dir . '/api/online-status.php',
        'transactions.php' => $base_dir . '/api/transactions.php',
        'ratings.php' => $base_dir . '/api/ratings.php',
        'admin-users.php' => $base_dir . '/api/admin-users.php',
        'google-callback.php' => $base_dir . '/api/google-callback.php',
        'google-oauth.php' => $base_dir . '/api/google-oauth.php',
        'test-db.php' => $base_dir . '/api/test-db.php',
        'test-update.php' => $base_dir . '/api/test-update.php',
        'test-google-oauth.php' => $base_dir . '/api/test-google-oauth.php',
        'test-ssl.php' => $base_dir . '/api/test-ssl.php',
        'test-connection.php' => $base_dir . '/api/test-connection.php',
        'php-info.php' => $base_dir . '/api/php-info.php'
    ];
    
    if (isset($allowed_files[$api_file])) {
        $target_file = $allowed_files[$api_file];
        
        if (file_exists($target_file)) {
            // Include the target API file
            require $target_file;
            exit;
        }
    }
    
    // If we get here, the API file wasn't found
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Handle page routes
$page_routes = [
    '/home.html' => 'home.html',
    '/login' => 'pages/login.html',
    '/signup' => 'pages/signup.html',
    '/dashboard' => 'pages/dashboard.html',
    '/profile' => 'pages/profile.html',
    '/browse' => 'pages/browse.html',
    '/cart' => 'pages/cart.html',
    '/wishlist' => 'pages/wishlist.html',
    '/add-listing' => 'pages/add-listing.html',
    '/reset-password' => 'pages/reset-password.html'
];

if (isset($page_routes[$path])) {
    $page_file = $page_routes[$path];
    if (file_exists(__DIR__ . '/' . $page_file)) {
        include __DIR__ . '/' . $page_file;
        exit;
    }
}

// If not a defined route, let PHP's built-in server handle it normally (for static assets)
return false;
?>