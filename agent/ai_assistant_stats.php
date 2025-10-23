<?php
/**
 * AI Assistant Stats - Get database statistics
 */

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if agent is logged in
if (!isset($_SESSION['agent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Niet ingelogd']);
    exit;
}

try {
    // Get ticket count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets");
    $ticket_count = $stmt->fetch()['count'];
    
    // Get KB article count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM knowledge_base WHERE is_published = 1");
    $kb_count = $stmt->fetch()['count'];
    
    // Get CI item count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM configuration_items WHERE status != 'Afgeschreven'");
    $ci_count = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'tickets' => $ticket_count,
            'kb_articles' => $kb_count,
            'ci_items' => $ci_count
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Stats Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kan statistieken niet ophalen'
    ]);
}
