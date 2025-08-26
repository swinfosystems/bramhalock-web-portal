<?php
// Helper functions for BramhaLock API

function getOrCreateDevice($deviceId) {
    global $pdo;
    
    // Check if device exists
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch();
    
    if (!$device) {
        // Create new device
        $stmt = $pdo->prepare("
            INSERT INTO devices (device_id, created_at, last_seen) 
            VALUES (?, NOW(), NOW())
        ");
        $stmt->execute([$deviceId]);
        
        // Get the created device
        $stmt = $pdo->prepare("SELECT * FROM devices WHERE device_id = ?");
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch();
    }
    
    return $device;
}

function logEvent($deviceId, $eventType, $eventData) {
    global $pdo;
    
    // Use default timestamp column in Postgres schema
    $stmt = $pdo->prepare("
        INSERT INTO events (device_id, event_type, event_data) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$deviceId, $eventType, $eventData]);
    
    return $pdo->lastInsertId();
}

function generateDeviceId() {
    return 'device_' . uniqid() . '_' . time();
}

function validateDeviceId($deviceId) {
    return !empty($deviceId) && is_string($deviceId) && strlen($deviceId) <= 255;
}

function sanitizeInput($input) {
    if (is_string($input)) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    return $input;
}
?>
