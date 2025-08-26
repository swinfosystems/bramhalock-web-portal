<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['deviceId']) || !isset($input['latitude']) || !isset($input['longitude'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

$deviceId = $input['deviceId'];
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);

try {
    $pdo = getDbConnection();
    
    // Get device database ID
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch();
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['error' => 'Device not found']);
        exit;
    }
    
    $deviceDbId = $device['id'];
    
    // Insert location record
    $stmt = $pdo->prepare("
        INSERT INTO location_history (device_id, latitude, longitude, accuracy, recorded_at, created_at) 
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $deviceDbId,
        $latitude,
        $longitude,
        isset($input['accuracy']) ? floatval($input['accuracy']) : null
    ]);
    
    $locationId = $pdo->lastInsertId();
    
    // Log the location event
    logSecurityEvent($deviceDbId, 'location_logged', [
        'location_id' => $locationId,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'accuracy' => isset($input['accuracy']) ? $input['accuracy'] : null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Location logged successfully',
        'location_id' => $locationId
    ]);
    
} catch (Exception $e) {
    error_log('Location logging error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
