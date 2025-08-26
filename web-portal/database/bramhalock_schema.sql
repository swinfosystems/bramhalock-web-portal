-- BramhaLock Database Schema for InfinityFree
-- Simplified schema for API testing

-- Devices table for managing connected PCs
CREATE TABLE devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id VARCHAR(255) UNIQUE NOT NULL,
    device_name VARCHAR(255) DEFAULT 'Unknown Device',
    profile_data JSON,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('online', 'offline') DEFAULT 'offline',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Commands table for remote commands
CREATE TABLE commands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id VARCHAR(255) NOT NULL,
    command_type VARCHAR(100) NOT NULL,
    command_data JSON,
    status ENUM('pending', 'executed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    executed_at TIMESTAMP NULL
);

-- Events table for logging
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id VARCHAR(255) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX idx_devices_device_id ON devices(device_id);
CREATE INDEX idx_commands_device_status ON commands(device_id, status);
CREATE INDEX idx_events_device_type ON events(device_id, event_type);

-- Insert test data
INSERT INTO devices (device_id, device_name) VALUES 
('test_device_001', 'Test Device 1');

INSERT INTO commands (device_id, command_type, command_data) VALUES 
('test_device_001', 'lock', '{"message": "Device locked remotely"}');
