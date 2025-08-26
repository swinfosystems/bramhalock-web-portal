// API Configuration - easily switch between hosting providers
const HOSTING_PROVIDERS = {
  infinityfree: 'https://swt.42web.io/web-portal/api',
  railway: 'https://your-app.railway.app/api',
  render: 'https://bramhalock-web-portal.onrender.com/api', // Render Web Portal API base URL
  local: 'http://localhost/api'
};

const CURRENT_PROVIDER = 'render'; // Switch to Render when deployed
const API_BASE_URL = HOSTING_PROVIDERS[CURRENT_PROVIDER as keyof typeof HOSTING_PROVIDERS];
const USE_MOCK = false; // Disable mock when Railway is deployed

interface Command {
    command: 'LOCK' | 'UNLOCK' | 'WIPE' | 'SCREENSHOT' | 'VIDEO' | 'RESTART';
    payload: string;
}

const parseJsonOrThrow = async (response: Response) => {
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        return response.json();
    }
    const text = await response.text();
    const preview = text.slice(0, 300);
    throw new SyntaxError(`Non-JSON response (status ${response.status} ${response.statusText}) from ${response.url}: ${preview}`);
};

// Poll for remote commands from server
export const pollForCommands = async (): Promise<Command | null> => {
    if (USE_MOCK) {
        console.log('Mock API: Polling for commands - no commands pending');
        return null;
    }
    
    try {
        const deviceId = localStorage.getItem('deviceId') || 'unknown';
        const response = await fetch(`${API_BASE_URL}/get-commands.php`, {
            method: 'POST',
            body: `deviceId=${encodeURIComponent(deviceId)}`
        });
        
        if (response.ok) {
            const data = await parseJsonOrThrow(response);
            if (data.command) {
                console.log('Received command:', data.command);
                return data;
            }
        }
    } catch (error) {
        console.error('Failed to poll commands:', error);
    }
    return null;
};

// Submit activity logs to server
export const submitLog = async (logType: string, logData: object): Promise<void> => {
    if (USE_MOCK) {
        console.log(`Mock API: Event logged successfully - ${logType}:`, logData);
        return;
    }
    
    try {
        const deviceId = localStorage.getItem('deviceId') || 'unknown';
        const response = await fetch(`${API_BASE_URL}/log-event.php`, {
            method: 'POST',
            body: `deviceId=${encodeURIComponent(deviceId)}&eventType=${encodeURIComponent(logType)}&eventData=${encodeURIComponent(JSON.stringify(logData))}&timestamp=${encodeURIComponent(new Date().toISOString())}`
        });
        
        if (response.ok) {
            // Some endpoints may return empty body; just log success
            console.log('Log submitted successfully');
        } else {
            const text = await response.text();
            console.error('Log submit failed:', response.status, response.statusText, text.slice(0, 300));
        }
    } catch (error) {
        console.error('Failed to submit log:', error);
    }
};

// Sync user profile with server
export const syncProfile = async (profile: any): Promise<boolean> => {
    if (USE_MOCK) {
        console.log('Mock API: Profile synced successfully for device:', profile.deviceId);
        return true;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/sync-profile.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `deviceId=${encodeURIComponent(profile.deviceId)}&profile=${encodeURIComponent(JSON.stringify(profile))}`
        });
        
        if (response.ok) {
            const data = await parseJsonOrThrow(response);
            console.log('Profile synced:', data.message);
            return true;
        } else {
            const text = await response.text();
            console.error('Sync profile failed:', response.status, response.statusText, text.slice(0, 300));
        }
    } catch (error) {
        console.error('Failed to sync profile:', error);
    }
    return false;
};
