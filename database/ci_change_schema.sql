-- CI & Change Management Module - Database Schema
-- Created: January 2025
-- Description: Configuration Items and Change Management tables

-- ============================================
-- CONFIGURATION ITEMS MANAGEMENT
-- ============================================

-- Configuration Items table
CREATE TABLE IF NOT EXISTS configuration_items (
    ci_id INT PRIMARY KEY AUTO_INCREMENT,
    ci_number VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('Hardware','Software','Licentie','Overig') NOT NULL,
    category VARCHAR(100),
    brand VARCHAR(100),
    model VARCHAR(100),
    name VARCHAR(255) NOT NULL,
    serial_number VARCHAR(255) UNIQUE,
    status ENUM('In gebruik','In voorraad','Defect','Afgeschreven') DEFAULT 'In gebruik',
    owner_id INT NULL,
    department VARCHAR(100),
    location VARCHAR(255),
    purchase_date DATE,
    purchase_price DECIMAL(10,2),
    supplier VARCHAR(255),
    warranty_expiry DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_ci_number (ci_number),
    INDEX idx_serial_number (serial_number),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CI History table
CREATE TABLE IF NOT EXISTS ci_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    ci_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('created','updated','status_changed','deleted') NOT NULL,
    field_changed VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_ci_id (ci_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CHANGE MANAGEMENT
-- ============================================

-- Changes table
CREATE TABLE IF NOT EXISTS changes (
    change_id INT PRIMARY KEY AUTO_INCREMENT,
    change_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    requested_by INT NOT NULL,
    assigned_to INT NULL,
    type ENUM('Feature','Patch','Hardware','Software','Netwerk','Infrastructuur','Overig') DEFAULT 'Overig',
    priority ENUM('Laag','Normaal','Hoog','Urgent') DEFAULT 'Normaal',
    impact ENUM('Laag','Middel','Hoog') DEFAULT 'Middel',
    status ENUM('Nieuw','In beoordeling','Goedgekeurd','Ingepland','Geïmplementeerd','Afgewezen') DEFAULT 'Nieuw',
    
    -- Beschrijving
    description TEXT,
    reason TEXT,
    expected_result TEXT,
    
    -- Impact
    affected_systems TEXT,
    affected_users INT,
    downtime_expected BOOLEAN DEFAULT FALSE,
    downtime_duration INT,
    risk_assessment TEXT,
    
    -- Implementatie
    implementation_plan TEXT,
    rollback_plan TEXT,
    resources_needed TEXT,
    
    -- Datums
    planned_date DATE,
    implemented_date DATE NULL,
    
    -- Review
    post_implementation_success BOOLEAN NULL,
    post_implementation_notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_change_number (change_number),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_priority (priority),
    INDEX idx_planned_date (planned_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Change History table
CREATE TABLE IF NOT EXISTS change_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    change_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('created','status_changed','updated','approved','rejected','implemented') NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    comment TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_change_id (change_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RELATION TABLES
-- ============================================

-- Change to CI relations
CREATE TABLE IF NOT EXISTS change_ci_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    change_id INT NOT NULL,
    ci_id INT NOT NULL,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE,
    UNIQUE KEY unique_change_ci (change_id, ci_id),
    INDEX idx_change_id (change_id),
    INDEX idx_ci_id (ci_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket to CI relations
CREATE TABLE IF NOT EXISTS ticket_ci_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    ci_id INT NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE,
    UNIQUE KEY unique_ticket_ci (ticket_id, ci_id),
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_ci_id (ci_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket to Change relations
CREATE TABLE IF NOT EXISTS ticket_change_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    change_id INT NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE,
    UNIQUE KEY unique_ticket_change (ticket_id, change_id),
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_change_id (change_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
