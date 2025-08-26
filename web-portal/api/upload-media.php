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

if (!$input || !isset($input['deviceId']) || !isset($input['media'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

$deviceId = $input['deviceId'];
$media = $input['media'];

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
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/media/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Process base64 data
    $mediaData = $media['data'];
    if (strpos($mediaData, 'data:') === 0) {
        // Extract base64 data
        $parts = explode(',', $mediaData);
        if (count($parts) === 2) {
            $base64Data = $parts[1];
            $mimeType = $parts[0];
            
            // Determine file extension
            $extension = 'jpg';
            if (strpos($mimeType, 'video') !== false) {
                $extension = 'webm';
            } elseif (strpos($mimeType, 'png') !== false) {
                $extension = 'png';
            }
            
            // Generate unique filename
            $filename = $deviceId . '_' . $media['id'] . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Save file
            $fileData = base64_decode($base64Data);
            if (file_put_contents($filepath, $fileData)) {
                // Insert media record into database
                $stmt = $pdo->prepare("
                    INSERT INTO media_captures (device_id, media_type, file_path, trigger_event, captured_at, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $deviceDbId,
                    $media['type'],
                    $filename,
                    $media['trigger'],
                    $media['timestamp']
                ]);
                
                $mediaId = $pdo->lastInsertId();
                
                // Log the media capture event
                logSecurityEvent($deviceDbId, 'media_captured', [
                    'media_id' => $mediaId,
                    'media_type' => $media['type'],
                    'trigger' => $media['trigger'],
                    'file_size' => strlen($fileData)
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Media uploaded successfully',
                    'media_id' => $mediaId,
                    'filename' => $filename
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save media file']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid media data format']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid base64 data']);
    }
    
} catch (Exception $e) {
    error_log('Media upload error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
