-- Chat Conversations and Messages Tables
-- Stores all AI chat interactions for learning

-- Conversations table
CREATE TABLE IF NOT EXISTS chat_conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    message_count INT DEFAULT 0,
    avg_feedback_score DECIMAL(3,2) NULL,
    resolved_ticket_id INT NULL,
    INDEX idx_user (user_id),
    INDEX idx_started (started_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    role ENUM('user', 'assistant') NOT NULL,
    message TEXT NOT NULL,
    confidence_score DECIMAL(3,2) NULL,
    sources_count INT DEFAULT 0,
    feedback_score TINYINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (conversation_id),
    INDEX idx_role (role),
    INDEX idx_feedback (feedback_score),
    INDEX idx_created (created_at),
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(conversation_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Feedback analytics table
CREATE TABLE IF NOT EXISTS feedback_analytics (
    analytics_id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_messages INT DEFAULT 0,
    positive_feedback INT DEFAULT 0,
    negative_feedback INT DEFAULT 0,
    avg_confidence DECIMAL(3,2) NULL,
    common_topics JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cache validation table
CREATE TABLE IF NOT EXISTS cache_validation (
    cache_id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(64) NOT NULL,
    query_text TEXT NOT NULL,
    response_text TEXT NOT NULL,
    feedback_score DECIMAL(3,2) NULL,
    hit_count INT DEFAULT 0,
    last_validated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_valid BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cache_key (cache_key),
    INDEX idx_valid (is_valid),
    INDEX idx_feedback (feedback_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
