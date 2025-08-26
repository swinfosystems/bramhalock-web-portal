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
    
    if (empty($deviceId)) {
        // Fallback to JSON if form data not present
        $input = json_decode(file_get_contents('php://input'), true);
        $deviceId = $input['deviceId'] ?? ($input['device_id'] ?? '');
    }
    
    if (empty($deviceId)) {
        throw new Exception('Device ID is required');
    }
    
    // Get or create device
    $device = getOrCreateDevice($deviceId);
    
    // Update last seen
    $stmt = $pdo->prepare("UPDATE devices SET last_seen = NOW() WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    
    // Get pending commands (align with Postgres schema)
    $stmt = $pdo->prepare("
        SELECT id, command, payload, created_at 
        FROM commands 
        WHERE device_id = ? AND status = 'pending' 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$deviceId]);
    $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark commands as executed and set executed_at
    if (!empty($commands)) {
        $commandIds = array_column($commands, 'id');
        $placeholders = str_repeat('?,', count($commandIds) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE commands SET status = 'executed', executed_at = NOW() WHERE id IN ($placeholders)");
        $stmt->execute($commandIds);
    }
    
    // Log polling event
    logEvent($deviceId, 'command_poll', 'Polled for commands, found ' . count($commands));
    
    echo json_encode([
        'success' => true,
        'commands' => $commands,
        'count' => count($commands)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
