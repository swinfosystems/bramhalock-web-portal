<?php
// Apply CORS via centralized helper to avoid duplicate headers
require_once '../includes/cors.php';
apply_cors();
// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Content-Type: application/json');
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Include database and functions
require_once '../config/database-render.php';
require_once '../includes/functions.php';

try {
    // Handle both JSON and form-encoded data
    $deviceId = $_POST['deviceId'] ?? ($_POST['device_id'] ?? '');
    $eventType = $_POST['eventType'] ?? ($_POST['event_type'] ?? '');
    $eventData = $_POST['eventData'] ?? ($_POST['event_data'] ?? '');
    
    if (empty($deviceId) || empty($eventType)) {
        // Fallback to JSON if form data not present
        $input = json_decode(file_get_contents('php://input'), true);
        $deviceId = $input['deviceId'] ?? ($input['device_id'] ?? '');
        $eventType = $input['eventType'] ?? ($input['event_type'] ?? '');
        $eventData = $input['eventData'] ?? ($input['event_data'] ?? '');
    }
    
    if (empty($deviceId) || empty($eventType)) {
        throw new Exception('Device ID and event type are required');
    }
    
    // Get or create device
    $device = getOrCreateDevice($deviceId);
    
    // Log the event
    logEvent($deviceId, $eventType, $eventData);
    
    echo json_encode([
        'success' => true,
        'message' => 'Event logged successfully',
        'deviceId' => $deviceId,
        'eventType' => $eventType
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
