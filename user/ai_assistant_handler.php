<?php
/**
 * AI Assistant Handler - User Level
 * Handles AI queries for regular users
 */

require_once '../config/config.php';
require_once '../config/session.php';
session_start();
require_once '../config/database.php';
require_once '../includes/ai_helper.php';

header('Content-Type: application/json');

// Debug logging
error_log("AI Handler - Session ID: " . session_id());
error_log("AI Handler - User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("AI Handler - Role: " . ($_SESSION['role'] ?? 'NOT SET'));
error_log("AI Handler - Session data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode([
        'success' => false, 
        'error' => 'Niet ingelogd',
        'debug' => [
            'session_id' => session_id(),
            'has_user_id' => isset($_SESSION['user_id']),
            'role' => $_SESSION['role'] ?? 'NOT SET',
            'expected_role' => 'user'
        ]
    ]);
    exit;
}

// Get query from POST
$query = $_POST['query'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'Geen vraag opgegeven']);
    exit;
}

// Initialize AI Helper
$aiHelper = new AIHelper();

// Check if AI is available
if (!$aiHelper->isEnabled()) {
    echo json_encode([
        'success' => false, 
        'error' => 'AI service is momenteel niet beschikbaar'
    ]);
    exit;
}

try {
    // Get AI suggestions with user-level restrictions
    // Users can search KB and CI items, but not all tickets (privacy)
    $result = $aiHelper->getSuggestions($query, [
        'top_k' => 5,
        'search_tickets' => false,  // Users don't see other users' tickets
        'search_kb' => true,
        'search_cis' => true,
        'user_id' => $_SESSION['user_id']  // Only their own tickets if needed
    ]);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Onbekende fout'
        ]);
    }
    
} catch (Exception $e) {
    error_log("AI Assistant Error (User): " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Er is een fout opgetreden bij het verwerken van je vraag'
    ]);
}
