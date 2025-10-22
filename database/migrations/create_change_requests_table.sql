-- Create change_requests table for ITIL Change Management
CREATE TABLE IF NOT EXISTS change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('infrastructure', 'application', 'security', 'network', 'hardware', 'software', 'process') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    impact ENUM('low', 'medium', 'high') DEFAULT 'medium',
    risk ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('draft', 'submitted', 'approved', 'rejected', 'scheduled', 'in_progress', 'completed', 'failed', 'cancelled') DEFAULT 'draft',
    planned_start DATETIME NULL,
    planned_end DATETIME NULL,
    actual_start DATETIME NULL,
    actual_end DATETIME NULL,
    created_by INT NOT NULL,
    approved_by INT NULL,
    assigned_agent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_change_number (change_number),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_created_by (created_by)
);

-- Create change_approvals table for approval workflow
CREATE TABLE IF NOT EXISTS change_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_id INT NOT NULL,
    approver_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_id) REFERENCES change_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_change_approver (change_id, approver_id)
);

-- Create change_logs table for audit trail
CREATE TABLE IF NOT EXISTS change_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    comments TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_id) REFERENCES change_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_change_id (change_id),
    INDEX idx_created_at (created_at)
);
