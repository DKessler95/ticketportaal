<?php
/**
 * API Endpoint: Get Category Fields
 * 
 * Returns all fields configured for a specific category
 * Used by dynamic field rendering on ticket creation
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/CategoryField.php';

// Initialize session
initSession();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

// Validate category_id parameter
if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Category ID is required and must be numeric'
    ]);
    exit;
}

$categoryId = (int)$_GET['category_id'];

try {
    $db = Database::getInstance();
    $categoryField = new CategoryField();
    
    // Verify category exists
    $category = $db->fetchOne(
        "SELECT category_id, name FROM categories WHERE category_id = ? AND is_active = 1",
        [$categoryId]
    );
    
    if (!$category) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Category not found'
        ]);
        exit;
    }
    
    // Get fields for this category (only active fields)
    $fields = $categoryField->getFieldsByCategory($categoryId, true);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'category' => $category,
        'fields' => $fields,
        'count' => count($fields)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
    
    // Log error
    if (function_exists('logError')) {
        logError('API', 'get_category_fields failed', [
            'error' => $e->getMessage(),
            'category_id' => $categoryId
        ]);
    }
}
