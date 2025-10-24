-- Create feedback table for AI learning
CREATE TABLE IF NOT EXISTS chat_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    feedback_score TINYINT NOT NULL COMMENT '-1 for negative, 1 for positive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_message_user (message_id, user_id),
    KEY idx_feedback_score (feedback_score),
    KEY idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
