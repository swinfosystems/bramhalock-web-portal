// BramhaLock Dashboard JavaScript
let currentTab = 'devices';
let devices = [];
let refreshInterval;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    loadDashboardData();
    setupEventListeners();
    
    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(loadDashboardData, 30000);
});

// Tab management
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.dataset.tab;
            switchTab(tabName);
        });
    });
}

function switchTab(tabName) {
    // Update button states
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active', 'border-blue-500', 'text-blue-600');
    document.querySelector(`[data-tab="${tabName}"]`).classList.remove('border-transparent', 'text-gray-500');
    
    // Update content visibility
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    document.getElementById(`${tabName}-tab`).classList.remove('hidden');
    currentTab = tabName;
    
    // Load tab-specific data
    loadTabData(tabName);
}

// Event listeners
function setupEventListeners() {
    // Command form
    document.getElementById('command-form').addEventListener('submit', handleCommandSubmit);
    
    // Message form (admin only)
    const messageForm = document.getElementById('message-form');
    if (messageForm) {
        messageForm.addEventListener('submit', handleMessageSubmit);
    }
    
    // Command type change
    document.getElementById('command-type').addEventListener('change', handleCommandTypeChange);
    
    // Filter changes
    const filters = ['security-device-filter', 'capture-type-filter', 'capture-device-filter', 'keylogger-device-filter'];
    filters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', () => loadTabData(currentTab));
        }
    });
}

// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await fetch('bramhalock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_devices'
        });
        
        devices = await response.json();
        updateDashboardStats();
        updateDevicesTable();
        updateDeviceFilters();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showNotification('Error loading dashboard data', 'error');
    }
}

// Update dashboard statistics
function updateDashboardStats() {
    const onlineCount = devices.filter(d => d.status === 'online').length;
    const lockedCount = devices.filter(d => d.status === 'locked').length;
    
    document.getElementById('online-count').textContent = onlineCount;
    document.getElementById('locked-count').textContent = lockedCount;
    
    // Load additional stats for admin
    if (document.getElementById('failed-count')) {
        loadFailedAttemptsCount();
        loadMediaCapturesCount();
    }
}

// Update devices table
function updateDevicesTable() {
    const tbody = document.getElementById('devices-table');
    tbody.innerHTML = '';
    
    devices.forEach(device => {
        const row = createDeviceRow(device);
        tbody.appendChild(row);
    });
}

// Create device table row
function createDeviceRow(device) {
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50';
    
    const statusClass = {
        'online': 'status-online',
        'offline': 'status-offline',
        'locked': 'status-locked',
        'unlocked': 'status-online'
    }[device.status] || 'status-offline';
    
    const lastSeen = device.seconds_since_seen < 60 ? 'Just now' : 
                    device.seconds_since_seen < 3600 ? `${Math.floor(device.seconds_since_seen / 60)}m ago` :
                    device.seconds_since_seen < 86400 ? `${Math.floor(device.seconds_since_seen / 3600)}h ago` :
                    `${Math.floor(device.seconds_since_seen / 86400)}d ago`;
    
    row.innerHTML = `
        <td class="px-6 py-4">
            <div class="flex items-center">
                <i class="fas fa-desktop mr-3 text-gray-400"></i>
                <div>
                    <div class="font-medium text-gray-900">${escapeHtml(device.device_name)}</div>
                    <div class="text-sm text-gray-500">${escapeHtml(device.user_name || 'Unassigned')}</div>
                </div>
            </div>
        </td>
        <td class="px-6 py-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                <i class="fas fa-circle mr-1 text-xs"></i>
                ${device.status.charAt(0).toUpperCase() + device.status.slice(1)}
            </span>
        </td>
        <td class="px-6 py-4 text-sm text-gray-500">${lastSeen}</td>
        <td class="px-6 py-4 text-sm text-gray-500">
            ${device.location_lat && device.location_lng ? 
                `<i class="fas fa-map-marker-alt mr-1"></i>${device.location_lat.toFixed(4)}, ${device.location_lng.toFixed(4)}` : 
                '<i class="fas fa-question-circle mr-1"></i>Unknown'}
        </td>
        <td class="px-6 py-4">
            <div class="flex space-x-2">
                <button onclick="showCommandModal(${device.id})" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-terminal"></i>
                </button>
                <button onclick="viewUsageStats(${device.id})" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-chart-line"></i>
                </button>
                ${device.location_lat ? `<button onclick="viewLocation(${device.location_lat}, ${device.location_lng})" class="text-purple-600 hover:text-purple-800">
                    <i class="fas fa-map-marker-alt"></i>
                </button>` : ''}
            </div>
        </td>
    `;
    
    return row;
}

// Load tab-specific data
async function loadTabData(tabName) {
    switch (tabName) {
        case 'security':
            await loadSecurityEvents();
            break;
        case 'captures':
            await loadMediaCaptures();
            break;
        case 'keylogger':
            await loadKeyloggerData();
            break;
        case 'messages':
            await loadAdminMessages();
            break;
    }
}

// Load security events
async function loadSecurityEvents() {
    try {
        const deviceFilter = document.getElementById('security-device-filter').value;
        const response = await fetch('bramhalock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_security_events&device_id=${deviceFilter}`
        });
        
        const events = await response.json();
        displaySecurityEvents(events);
        
    } catch (error) {
        console.error('Error loading security events:', error);
    }
}

// Display security events
function displaySecurityEvents(events) {
    const container = document.getElementById('security-events');
    container.innerHTML = '';
    
    events.forEach(event => {
        const eventDiv = document.createElement('div');
        eventDiv.className = 'bg-white border border-gray-200 rounded-lg p-4 shadow-sm';
        
        const eventTypeClass = {
            'login_failed': 'text-red-600',
            'login_success': 'text-green-600',
            'screen_locked': 'text-yellow-600',
            'screen_unlocked': 'text-blue-600',
            'suspicious_activity': 'text-red-600'
        }[event.event_type] || 'text-gray-600';
        
        eventDiv.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-shield-alt ${eventTypeClass}"></i>
                    <div>
                        <h4 class="font-medium text-gray-900">${formatEventType(event.event_type)}</h4>
                        <p class="text-sm text-gray-500">${escapeHtml(event.device_name)}</p>
                    </div>
                </div>
                <span class="text-sm text-gray-500">${formatDateTime(event.timestamp)}</span>
            </div>
            ${event.event_data ? `<div class="mt-2 text-sm text-gray-600">${formatEventData(event.event_data)}</div>` : ''}
        `;
        
        container.appendChild(eventDiv);
    });
}

// Load media captures
async function loadMediaCaptures() {
    try {
        const deviceFilter = document.getElementById('capture-device-filter').value;
        const typeFilter = document.getElementById('capture-type-filter').value;
        
        const response = await fetch('bramhalock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_media_captures&device_id=${deviceFilter}&type=${typeFilter}`
        });
        
        const captures = await response.json();
        displayMediaCaptures(captures);
        
    } catch (error) {
        console.error('Error loading media captures:', error);
    }
}

// Display media captures
function displayMediaCaptures(captures) {
    const container = document.getElementById('media-captures');
    container.innerHTML = '';
    
    captures.forEach(capture => {
        const captureDiv = document.createElement('div');
        captureDiv.className = 'bg-white border border-gray-200 rounded-lg p-4 shadow-sm card-hover';
        
        const typeIcon = {
            'screenshot': 'fa-desktop',
            'camera': 'fa-camera',
            'video': 'fa-video',
            'audio': 'fa-microphone'
        }[capture.capture_type] || 'fa-file';
        
        captureDiv.innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <i class="fas ${typeIcon} text-blue-600"></i>
                    <div>
                        <h4 class="font-medium text-gray-900">${capture.capture_type.charAt(0).toUpperCase() + capture.capture_type.slice(1)}</h4>
                        <p class="text-sm text-gray-500">${escapeHtml(capture.device_name)}</p>
                    </div>
                </div>
                <span class="text-sm text-gray-500">${formatDateTime(capture.timestamp)}</span>
            </div>
            <div class="text-sm text-gray-600 mb-3">
                Size: ${formatBytes(capture.file_size)}
                ${capture.duration ? ` | Duration: ${capture.duration}s` : ''}
            </div>
            <button onclick="viewMediaCapture(${capture.id})" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors">
                <i class="fas fa-eye mr-2"></i>View
            </button>
        `;
        
        container.appendChild(captureDiv);
    });
}

// Command modal functions
function showCommandModal(deviceId) {
    document.getElementById('command-device-id').value = deviceId;
    document.getElementById('command-modal').classList.add('active');
}

function showMessageModal() {
    document.getElementById('message-modal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Handle command type change
function handleCommandTypeChange() {
    const commandType = document.getElementById('command-type').value;
    const payloadSection = document.getElementById('command-payload-section');
    
    const needsPayload = ['capture_video', 'capture_audio', 'show_message'].includes(commandType);
    payloadSection.style.display = needsPayload ? 'block' : 'none';
}

// Handle command submission
async function handleCommandSubmit(e) {
    e.preventDefault();
    
    const deviceId = document.getElementById('command-device-id').value;
    const commandType = document.getElementById('command-type').value;
    const payload = document.getElementById('command-payload').value;
    
    try {
        const response = await fetch('bramhalock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_command&device_id=${deviceId}&command=${commandType}&payload=${encodeURIComponent(payload)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Command sent successfully', 'success');
            closeModal('command-modal');
        } else {
            showNotification(result.error || 'Failed to send command', 'error');
        }
        
    } catch (error) {
        console.error('Error sending command:', error);
        showNotification('Error sending command', 'error');
    }
}

// Handle message submission
async function handleMessageSubmit(e) {
    e.preventDefault();
    
    const deviceId = document.getElementById('message-device-id').value;
    const messageType = document.getElementById('message-type').value;
    const title = document.getElementById('message-title').value;
    const content = document.getElementById('message-content').value;
    
    try {
        const response = await fetch('bramhalock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&device_id=${deviceId}&type=${messageType}&title=${encodeURIComponent(title)}&content=${encodeURIComponent(content)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Message sent successfully', 'success');
            closeModal('message-modal');
            loadAdminMessages();
        } else {
            showNotification(result.error || 'Failed to send message', 'error');
        }
        
    } catch (error) {
        console.error('Error sending message:', error);
        showNotification('Error sending message', 'error');
    }
}

// Update device filters
function updateDeviceFilters() {
    const filters = ['security-device-filter', 'capture-device-filter', 'keylogger-device-filter', 'message-device-id'];
    
    filters.forEach(filterId => {
        const select = document.getElementById(filterId);
        if (select) {
            // Clear existing options (except first one)
            while (select.children.length > 1) {
                select.removeChild(select.lastChild);
            }
            
            // Add device options
            devices.forEach(device => {
                const option = document.createElement('option');
                option.value = device.id;
                option.textContent = device.device_name;
                select.appendChild(option);
            });
        }
    });
}

// Utility functions
function refreshDevices() {
    loadDashboardData();
    showNotification('Devices refreshed', 'info');
}

function viewUsageStats(deviceId) {
    // Implementation for viewing usage statistics
    console.log('View usage stats for device:', deviceId);
}

function viewLocation(lat, lng) {
    // Open location in new window/tab
    window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
}

function viewMediaCapture(captureId) {
    // Implementation for viewing media capture
    window.open(`view_media.php?id=${captureId}`, '_blank');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function formatEventType(eventType) {
    return eventType.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
}

function formatEventData(eventDataJson) {
    try {
        const data = JSON.parse(eventDataJson);
        return Object.entries(data).map(([key, value]) => 
            `${key}: ${value}`
        ).join(', ');
    } catch {
        return eventDataJson;
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Load additional stats for admin
async function loadFailedAttemptsCount() {
    try {
        const response = await fetch('bramhalock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_failed_attempts&limit=1000'
        });
        
        const attempts = await response.json();
        document.getElementById('failed-count').textContent = attempts.length;
        
    } catch (error) {
        console.error('Error loading failed attempts count:', error);
    }
}

async function loadMediaCapturesCount() {
    try {
        const response = await fetch('bramhalock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_media_captures&limit=1000'
        });
        
        const captures = await response.json();
        document.getElementById('media-count').textContent = captures.length;
        
    } catch (error) {
        console.error('Error loading media captures count:', error);
    }
}
