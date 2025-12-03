<?php
echo "OpenSSL loaded: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "Testing HTTPS connection...\n";

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$result = @file_get_contents('https://www.google.com', false, $context);
if ($result !== false) {
    echo "SUCCESS: Can connect to HTTPS URLs\n";
} else {
    $error = error_get_last();
    echo "FAILED: " . ($error['message'] ?? 'Unknown error') . "\n";
}
?>
