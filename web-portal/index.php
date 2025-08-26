<?php
// Simple index file for Railway deployment
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

echo json_encode([
    'status' => 'success',
    'message' => 'BramhaLock API is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        '/api/sync-profile.php',
        '/api/get-commands.php', 
        '/api/log-event.php'
    ]
]);
?>
