<?php
/**
 * Ticket AI Integration
 * Include this file in ticket detail pages to add AI suggestions
 * 
 * Required variables before including:
 * - $ticket: Array with ticket data (id, title, description)
 * - $current_user_id: Current user ID
 * 
 * Optional variables:
 * - $compact_mode: Use compact display (default: false)
 */

// Check if AI is enabled
if (!defined('AI_ENABLED') || !AI_ENABLED) {
    return;
}

// Check if required variables are set
if (!isset($ticket) || !isset($current_user_id)) {
    return;
}

// Check if user is in beta group (if beta rollout is active)
require_once __DIR__ . '/ai_helper.php';
$ai = new AIHelper();

if (!$ai->isUserInBeta($current_user_id)) {
    return;
}

// Check if AI is available
if (!$ai->isEnabled()) {
    // Show a subtle message that AI is temporarily unavailable
    echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 20px 0; border-radius: 4px;">';
    echo '<span style="color: #856404;">ℹ️ AI suggesties zijn tijdelijk niet beschikbaar.</span>';
    echo '</div>';
    return;
}

// Format ticket query
$query = $ai->formatTicketQuery($ticket);

if (empty($query)) {
    return; // No content to query
}

// Get AI suggestions
$start_time = microtime(true);
$ai_response = $ai->getSuggestions($query, [
    'top_k' => 10,
    'include_tickets' => true,
    'include_kb' => true,
    'include_ci' => false
]);
$response_time = microtime(true) - $start_time;

// Log interaction
$ai->logInteraction(
    $ticket['id'],
    $current_user_id,
    $ai_response['success'],
    $response_time
);

// Display widget if successful
if ($ai_response['success']) {
    $compact_mode = isset($compact_mode) ? $compact_mode : false;
    include __DIR__ . '/ai_suggestion_widget.php';
} else {
    // Show error message only to admins
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        echo '<div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px; margin: 20px 0; border-radius: 4px;">';
        echo '<span style="color: #721c24;">⚠️ AI fout: ' . htmlspecialchars($ai_response['error']) . '</span>';
        echo '</div>';
    }
}
?>
