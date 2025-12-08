<?php
/**
 * Health Check Endpoint for Render.com
 * Returns 200 OK if service is healthy
 */

// Simple health check - just confirm PHP is running
http_response_code(200);
header('Content-Type: application/json');

echo json_encode([
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'service' => 'SwapIt'
]);
exit;
