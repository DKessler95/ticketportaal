<?php
/**
 * AI Feedback Handler
 * Stores user feedback on AI responses for learning
 */

require_once '../config/config.php';
require_once '../config/database.php';

session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get feedback data
$message_id = $_POST['message_id'] ?? '';
$feedback_score = (int)($_POST['feedback_score'] ?? 0);
$timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');

if (empty($message_id) || $feedback_score === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid feedback data']);
    exit;
}

try {
    // Store feedback in database
    $stmt = $pdo->prepare("
        INSERT INTO chat_feedback (
            message_id, 
            user_id, 
            feedback_score, 
            created_at
        ) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            feedback_score = VALUES(feedback_score),
            created_at = VALUES(created_at)
    ");
    
    $stmt->execute([
        $message_id,
        $_SESSION['user_id'],
        $feedback_score,
        $timestamp
    ]);
    
    // Log for learning pipeline
    error_log("AI Feedback: user={$_SESSION['user_id']}, message={$message_id}, score={$feedback_score}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Feedback opgeslagen'
    ]);
    
} catch (Exception $e) {
    error_log("AI Feedback Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
