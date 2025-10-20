-- CI & Change Management Module Migration Script
-- Created: 2025-10-20
-- Description: Database schema for Configuration Item and Change Management modules

-- ============================================================================
-- CONFIGURATION ITEMS TABLES
-- ============================================================================

-- Configuration Items table: stores all IT assets
CREATE TABLE IF NOT EXISTS configuration_items (
    ci_id INT PRIMARY KEY AUTO_INCREMENT,
    ci_number VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('Hardware', 'Software', 'Licentie', 'Overig') NOT NULL,
    category VARCHAR(100),
    brand VARCHAR(100),
    model VARCHAR(100),
    name VARCHAR(255) NOT NULL,
    serial_number VARCHAR(255) UNIQUE,
    asset_tag VARCHAR(100),
    status ENUM('In gebruik', 'In voorraad', 'Defect', 'Afgeschreven', 'Onderhoud') NOT NULL DEFAULT 'In voorraad',
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
    created_by INT NOT NULL,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_ci_number (ci_number),
    INDEX idx_serial_number (serial_number),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_owner_id (owner_id),
    INDEX idx_department (department),
    INDEX idx_warranty_expiry (warranty_expiry),
    INDEX idx_created_at (created_at),
    INDEX idx_status_type (status, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CI History table: audit trail for all CI changes
CREATE TABLE IF NOT EXISTS ci_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    ci_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    field_changed VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_ci_id (ci_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CI Attachments table: stores file metadata for CIs
CREATE TABLE IF NOT EXISTS ci_attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    ci_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    filesize INT NOT NULL,
    file_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_ci_id (ci_id),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CHANGE MANAGEMENT TABLES
-- ============================================================================

-- Changes table: stores all change requests
CREATE TABLE IF NOT EXISTS changes (
    change_id INT PRIMARY KEY AUTO_INCREMENT,
    change_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    requested_by INT NOT NULL,
    assigned_to INT NULL,
    type ENUM('Feature', 'Patch', 'Hardware', 'Software', 'Netwerk', 'Infrastructuur', 'Overig') NOT NULL,
    priority ENUM('Laag', 'Normaal', 'Hoog', 'Urgent') DEFAULT 'Normaal',
    impact ENUM('Laag', 'Middel', 'Hoog') DEFAULT 'Laag',
    status ENUM('Nieuw', 'In beoordeling', 'Goedgekeurd', 'Ingepland', 'In uitvoering', 'Geïmplementeerd', 'Afgewezen', 'Geannuleerd') DEFAULT 'Nieuw',
    description TEXT,
    reason TEXT,
    expected_result TEXT,
    affected_systems TEXT,
    affected_users INT,
    downtime_expected BOOLEAN DEFAULT FALSE,
    downtime_duration INT COMMENT 'Duration in minutes',
    risk_assessment TEXT,
    implementation_plan TEXT,
    rollback_plan TEXT,
    resources_needed TEXT,
    planned_start_date DATETIME,
    planned_end_date DATETIME,
    actual_start_date DATETIME,
    actual_end_date DATETIME,
    approved_by INT NULL,
    approved_at DATETIME,
    approval_comment TEXT,
    post_implementation_success BOOLEAN,
    post_implementation_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_change_number (change_number),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_priority (priority),
    INDEX idx_impact (impact),
    INDEX idx_requested_by (requested_by),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_planned_start_date (planned_start_date),
    INDEX idx_created_at (created_at),
    INDEX idx_status_priority (status, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Change History table: audit trail for all change status transitions
CREATE TABLE IF NOT EXISTS change_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    change_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_change_id (change_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Change Attachments table: stores file metadata for changes
CREATE TABLE IF NOT EXISTS change_attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    change_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    filesize INT NOT NULL,
    file_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_change_id (change_id),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- RELATIONSHIP TABLES
-- ============================================================================

-- Ticket-CI Relations: links tickets to configuration items
CREATE TABLE IF NOT EXISTS ticket_ci_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    ci_id INT NOT NULL,
    relation_type ENUM('affects', 'caused_by', 'resolved_by', 'related_to') DEFAULT 'related_to',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_ticket_ci (ticket_id, ci_id),
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_ci_id (ci_id),
    INDEX idx_relation_type (relation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket-Change Relations: links tickets to changes
CREATE TABLE IF NOT EXISTS ticket_change_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    change_id INT NOT NULL,
    relation_type ENUM('caused_by', 'resolved_by', 'related_to') DEFAULT 'related_to',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_ticket_change (ticket_id, change_id),
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_change_id (change_id),
    INDEX idx_relation_type (relation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Change-CI Relations: links changes to configuration items
CREATE TABLE IF NOT EXISTS change_ci_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    change_id INT NOT NULL,
    ci_id INT NOT NULL,
    relation_type ENUM('affects', 'modifies', 'replaces', 'uses', 'related_to') DEFAULT 'related_to',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_change_ci (change_id, ci_id),
    INDEX idx_change_id (change_id),
    INDEX idx_ci_id (ci_id),
    INDEX idx_relation_type (relation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEQUENCES TABLE FOR AUTO-NUMBERING
-- ============================================================================

-- Sequences table: manages auto-numbering for CIs and Changes
CREATE TABLE IF NOT EXISTS sequences (
    sequence_name VARCHAR(50) PRIMARY KEY,
    current_year INT NOT NULL,
    current_number INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sequence_name (sequence_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initialize sequences
INSERT INTO sequences (sequence_name, current_year, current_number) 
VALUES ('ci_sequence', YEAR(CURDATE()), 0),
       ('change_sequence', YEAR(CURDATE()), 0)
ON DUPLICATE KEY UPDATE sequence_name = sequence_name;

-- ============================================================================
-- TRIGGERS FOR AUTO-NUMBERING
-- ============================================================================

DELIMITER $$

DROP TRIGGER IF EXISTS before_ci_insert$$

CREATE TRIGGER before_ci_insert
BEFORE INSERT ON configuration_items
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    DECLARE current_year INT;
    
    SET current_year = YEAR(CURDATE());
    
    -- Get and update sequence
    UPDATE sequences 
    SET current_number = CASE 
        WHEN current_year = YEAR(CURDATE()) THEN current_number + 1
        ELSE 1
    END,
    current_year = current_year
    WHERE sequence_name = 'ci_sequence';
    
    -- Get the new number
    SELECT current_number INTO next_number
    FROM sequences
    WHERE sequence_name = 'ci_sequence';
    
    -- Set the CI number
    SET NEW.ci_number = CONCAT('CI-', current_year, '-', LPAD(next_number, 3, '0'));
END$$

DROP TRIGGER IF EXISTS before_change_insert$$

CREATE TRIGGER before_change_insert
BEFORE INSERT ON changes
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    DECLARE current_year INT;
    
    SET current_year = YEAR(CURDATE());
    
    -- Get and update sequence
    UPDATE sequences 
    SET current_number = CASE 
        WHEN current_year = YEAR(CURDATE()) THEN current_number + 1
        ELSE 1
    END,
    current_year = current_year
    WHERE sequence_name = 'change_sequence';
    
    -- Get the new number
    SELECT current_number INTO next_number
    FROM sequences
    WHERE sequence_name = 'change_sequence';
    
    -- Set the change number
    SET NEW.change_number = CONCAT('CHG-', current_year, '-', LPAD(next_number, 3, '0'));
END$$

DELIMITER ;

-- ============================================================================
-- VIEWS FOR REPORTING
-- ============================================================================

-- View: Active CIs with owner information
CREATE OR REPLACE VIEW v_active_cis AS
SELECT 
    ci.ci_id,
    ci.ci_number,
    ci.type,
    ci.category,
    ci.name,
    ci.brand,
    ci.model,
    ci.serial_number,
    ci.status,
    ci.department,
    ci.location,
    ci.purchase_date,
    ci.purchase_price,
    ci.warranty_expiry,
    CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
    u.email AS owner_email,
    ci.created_at
FROM configuration_items ci
LEFT JOIN users u ON ci.owner_id = u.user_id
WHERE ci.status != 'Afgeschreven';

-- View: Changes with user information
CREATE OR REPLACE VIEW v_changes_overview AS
SELECT 
    c.change_id,
    c.change_number,
    c.title,
    c.type,
    c.priority,
    c.impact,
    c.status,
    CONCAT(req.first_name, ' ', req.last_name) AS requester_name,
    CONCAT(asn.first_name, ' ', asn.last_name) AS assignee_name,
    c.planned_start_date,
    c.planned_end_date,
    c.created_at,
    c.updated_at
FROM changes c
LEFT JOIN users req ON c.requested_by = req.user_id
LEFT JOIN users asn ON c.assigned_to = asn.user_id;

-- View: CI financial summary
CREATE OR REPLACE VIEW v_ci_financial_summary AS
SELECT 
    type,
    status,
    COUNT(*) AS total_items,
    SUM(purchase_price) AS total_value,
    AVG(purchase_price) AS avg_value,
    MIN(purchase_date) AS oldest_purchase,
    MAX(purchase_date) AS newest_purchase
FROM configuration_items
WHERE purchase_price IS NOT NULL
GROUP BY type, status;

-- View: Expiring warranties
CREATE OR REPLACE VIEW v_expiring_warranties AS
SELECT 
    ci.ci_id,
    ci.ci_number,
    ci.name,
    ci.type,
    ci.brand,
    ci.model,
    ci.warranty_expiry,
    DATEDIFF(ci.warranty_expiry, CURDATE()) AS days_until_expiry,
    CONCAT(u.first_name, ' ', u.last_name) AS owner_name
FROM configuration_items ci
LEFT JOIN users u ON ci.owner_id = u.user_id
WHERE ci.warranty_expiry IS NOT NULL
  AND ci.warranty_expiry >= CURDATE()
  AND ci.warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
  AND ci.status = 'In gebruik'
ORDER BY ci.warranty_expiry ASC;

-- View: Change statistics
CREATE OR REPLACE VIEW v_change_statistics AS
SELECT 
    status,
    type,
    priority,
    COUNT(*) AS total_changes,
    AVG(TIMESTAMPDIFF(DAY, created_at, 
        CASE 
            WHEN status = 'Geïmplementeerd' THEN actual_end_date
            ELSE NULL 
        END)) AS avg_days_to_implement
FROM changes
GROUP BY status, type, priority;

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================
