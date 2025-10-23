<?php
/**
 * AI Assistant Handler - Agent Level
 * Handles AI queries for agents with full access to tickets, KB, and CI items
 */

require_once '../config/config.php';
require_once '../config/session.php';
session_start();
require_once '../config/database.php';
require_once '../includes/ai_helper.php';

header('Content-Type: application/json');

// Check if agent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    echo json_encode(['success' => false, 'error' => 'Niet ingelogd']);
    exit;
}

// Get query and search options from POST
$query = $_POST['query'] ?? '';
$search_tickets = isset($_POST['search_tickets']) && $_POST['search_tickets'] === 'true';
$search_kb = isset($_POST['search_kb']) && $_POST['search_kb'] === 'true';
$search_ci = isset($_POST['search_ci']) && $_POST['search_ci'] === 'true';

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
    // Get AI suggestions with agent-level access
    // Agents have full access to all tickets, KB articles, and CI items
    $result = $aiHelper->getSuggestions($query, [
        'top_k' => 10,
        'search_tickets' => $search_tickets,
        'search_kb' => $search_kb,
        'search_cis' => $search_ci,
        'user_id' => $_SESSION['user_id'],
        'access_level' => 'agent'
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
    error_log("AI Assistant Error (Agent): " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Er is een fout opgetreden bij het verwerken van je vraag'
    ]);
}
