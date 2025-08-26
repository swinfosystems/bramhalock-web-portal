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
    $profile = [];
    
    if (!empty($_POST['profile'])) {
        $profile = json_decode($_POST['profile'], true) ?? [];
    } else {
        // Fallback to JSON if form data not present
        $input = json_decode(file_get_contents('php://input'), true);
        $deviceId = $input['deviceId'] ?? ($input['device_id'] ?? '');
        $profile = $input['profile'] ?? [];
    }
    
    if (empty($deviceId)) {
        throw new Exception('Device ID is required');
    }
    
    // Get or create device
    $device = getOrCreateDevice($deviceId);
    
    // Update profile
    $stmt = $pdo->prepare("
        UPDATE devices 
        SET profile_data = ?, last_seen = NOW() 
        WHERE device_id = ?
    ");
    
    $stmt->execute([json_encode($profile), $deviceId]);
    
    // Log the sync event
    logEvent($deviceId, 'profile_sync', 'Profile synchronized successfully');
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile synced successfully',
        'deviceId' => $deviceId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
