<?php
// Universal CORS handler for InfinityFree hosting
// Include this at the top of every API endpoint

function setCorsHeaders() {
    // Set CORS headers for all requests
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    
    // Handle OPTIONS preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Content-Type: application/json');
        http_response_code(200);
        exit(0);
    }
}

// Call this function at the start of every API file
setCorsHeaders();
?>
