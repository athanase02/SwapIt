<?php
/**
 * Test script for Google OAuth debugging
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db_with_fallback.php';
require_once __DIR__ . '/google-oauth.php';

echo "<h1>Google OAuth Debug Test</h1>\n";
echo "<hr>\n";

// Test 1: Check constants
echo "<h2>1. Configuration Check</h2>\n";
echo "<pre>\n";
echo "Client ID: " . GOOGLE_CLIENT_ID . "\n";
echo "Redirect URI: " . GOOGLE_REDIRECT_URI . "\n";
echo "Client Secret Length: " . strlen(GOOGLE_CLIENT_SECRET) . " chars\n";
echo "</pre>\n";

// Test 2: Test token exchange with a fake code (will fail but show us the error)
echo "<h2>2. Token Exchange Test (with fake code)</h2>\n";
echo "<pre>\n";
$testResult = exchangeCodeForToken('fake_code_for_testing');
echo "Result: " . ($testResult ? "Got response" : "NULL") . "\n";
if ($testResult) {
    echo "Response: " . json_encode($testResult, JSON_PRETTY_PRINT) . "\n";
}
echo "</pre>\n";

// Test 3: Check if file_get_contents can reach Google
echo "<h2>3. Network Connectivity Test</h2>\n";
echo "<pre>\n";
$testUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$options = [
    'http' => [
        'header' => "Authorization: Bearer fake_token\r\n",
        'method' => 'GET',
        'ignore_errors' => true,
        'timeout' => 10
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($testUrl, false, $context);

if ($response !== false) {
    echo "✓ Can connect to Google APIs\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
} else {
    echo "✗ Cannot connect to Google APIs\n";
    $error = error_get_last();
    echo "Error: " . ($error['message'] ?? 'Unknown') . "\n";
}
echo "</pre>\n";

// Test 4: Check database connection
echo "<h2>4. Database Connection Test</h2>\n";
echo "<pre>\n";
if ($conn) {
    echo "✓ Database connected\n";
    
    // Check if google_id column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
    if ($result && $result->num_rows > 0) {
        echo "✓ google_id column exists\n";
    } else {
        echo "✗ google_id column NOT found\n";
    }
} else {
    echo "✗ Database connection failed\n";
}
echo "</pre>\n";

// Test 5: Check allow_url_fopen
echo "<h2>5. PHP Configuration</h2>\n";
echo "<pre>\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'Loaded' : 'Not loaded') . "\n";
echo "cURL: " . (extension_loaded('curl') ? 'Loaded' : 'Not loaded') . "\n";
echo "</pre>\n";

echo "<hr>\n";
echo "<p>Check the error_log output for detailed messages from the token exchange attempt.</p>\n";
?>
