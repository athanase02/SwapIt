<?php
// Direct test for Google config
require_once dirname(__DIR__) . '/api/google-oauth.php';

header('Content-Type: application/json');
echo json_encode(getGoogleConfig());
?>
