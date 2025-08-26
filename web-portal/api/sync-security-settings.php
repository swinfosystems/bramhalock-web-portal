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

if (!$input || !isset($input['deviceId']) || !isset($input['settings'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

$deviceId = $input['deviceId'];
$settings = $input['settings'];

try {
    $pdo = getDbConnection();
    
    // Get device database ID
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch();
    
    if (!$device) {
        // Register new device if not exists
        $stmt = $pdo->prepare("
            INSERT INTO devices (device_id, device_name, status, last_seen) 
            VALUES (?, ?, 'online', NOW())
        ");
        $stmt->execute([$deviceId, 'BramhaLock Device']);
        $deviceDbId = $pdo->lastInsertId();
    } else {
        $deviceDbId = $device['id'];
        
        // Update last seen
        $stmt = $pdo->prepare("UPDATE devices SET last_seen = NOW() WHERE id = ?");
        $stmt->execute([$deviceDbId]);
    }
    
    // Check if security settings exist
    $stmt = $pdo->prepare("SELECT id FROM system_settings WHERE device_id = ? AND setting_key = 'security_settings'");
    $stmt->execute([$deviceDbId]);
    $existingSettings = $stmt->fetch();
    
    $settingsJson = json_encode($settings);
    
    if ($existingSettings) {
        // Update existing settings
        $stmt = $pdo->prepare("
            UPDATE system_settings SET 
                setting_value = ?,
                updated_at = NOW()
            WHERE device_id = ? AND setting_key = 'security_settings'
        ");
        $stmt->execute([$settingsJson, $deviceDbId]);
    } else {
        // Insert new settings
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (device_id, setting_key, setting_value, created_at, updated_at) 
            VALUES (?, 'security_settings', ?, NOW(), NOW())
        ");
        $stmt->execute([$deviceDbId, $settingsJson]);
    }
    
    // Log the security settings update
    logSecurityEvent($deviceDbId, 'security_settings_updated', [
        'camera_enabled' => $settings['cameraEnabled'],
        'keylogger_enabled' => $settings['keyloggerEnabled'],
        'geolocation_enabled' => $settings['geolocationEnabled'],
        'video_recording_enabled' => $settings['videoRecordingEnabled']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Security settings synced successfully',
        'device_id' => $deviceDbId
    ]);
    
} catch (Exception $e) {
    error_log('Security settings sync error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
