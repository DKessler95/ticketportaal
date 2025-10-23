<?php
/**
 * Universal AI Chat Handler
 * Handles AI queries for all user roles (user/agent/admin)
 * Routes to appropriate access level based on role
 */

require_once '../config/config.php';

// TEMPORARY: Use PHPSESSID for compatibility
session_name('PHPSESSID');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');

session_start();
require_once '../config/database.php';
require_once '../includes/ai_helper.php';

header('Content-Type: application/json');

// Debug: Log session info
error_log("=== AI Chat Handler Debug ===");
error_log("Session ID: " . session_id());
error_log("Session Name: " . session_name());
error_log("Cookie: " . print_r($_COOKIE, true));
error_log("Session Data: " . print_r($_SESSION, true));
error_log("============================");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Niet ingelogd',
        'debug' => [
            'session_id' => session_id(),
            'session_name' => session_name(),
            'has_user_id' => isset($_SESSION['user_id']),
            'session_data' => $_SESSION,
            'cookies_received' => array_keys($_COOKIE)
        ]
    ]);
    exit;
}

// Get user role
$userRole = $_SESSION['role'] ?? 'user';

// Get query from POST
$query = $_POST['query'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'Geen vraag opgegeven']);
    exit;
}

// Get search options from POST
$search_tickets = isset($_POST['search_tickets']) && $_POST['search_tickets'] === 'true';
$search_kb = isset($_POST['search_kb']) && $_POST['search_kb'] === 'true';
$search_ci = isset($_POST['search_ci']) && $_POST['search_ci'] === 'true';

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
    // Configure access based on role
    $options = [
        'user_id' => $_SESSION['user_id'],
        'access_level' => $userRole
    ];
    
    // Role-specific settings
    switch ($userRole) {
        case 'admin':
            $options['top_k'] = 15;
            $options['search_tickets'] = $search_tickets;
            $options['search_kb'] = $search_kb;
            $options['search_cis'] = $search_ci;
            $options['include_analytics'] = true;
            break;
            
        case 'agent':
            $options['top_k'] = 10;
            $options['search_tickets'] = $search_tickets;
            $options['search_kb'] = $search_kb;
            $options['search_cis'] = $search_ci;
            break;
            
        case 'user':
        default:
            $options['top_k'] = 5;
            $options['search_tickets'] = false; // Users don't see other users' tickets
            $options['search_kb'] = $search_kb;
            $options['search_cis'] = $search_ci;
            break;
    }
    
    // Get AI suggestions
    $result = $aiHelper->getSuggestions($query, $options);
    
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
    error_log("AI Assistant Error ({$userRole}): " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Er is een fout opgetreden bij het verwerken van je vraag'
    ]);
}
