<?php
/**
 * API Endpoint: Update Field Order
 * 
 * Updates the display order of category fields (for drag & drop)
 * Admin only
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/CategoryField.php';

// Initialize session
initSession();

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Admin access required.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'CSRF validation failed'
    ]);
    exit;
}

// Validate field_order parameter
if (!isset($_POST['field_order'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'field_order parameter is required'
    ]);
    exit;
}

// Decode field order JSON
$fieldOrder = json_decode($_POST['field_order'], true);

if ($fieldOrder === null || !is_array($fieldOrder)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid field_order format. Expected JSON array.'
    ]);
    exit;
}

// Validate that all keys are numeric (field IDs)
foreach ($fieldOrder as $fieldId => $order) {
    if (!is_numeric($fieldId) || !is_numeric($order)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid field_order data. All keys and values must be numeric.'
        ]);
        exit;
    }
}

try {
    $categoryField = new CategoryField();
    
    // Update field order
    $result = $categoryField->updateFieldOrder($fieldOrder);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Field order updated successfully',
            'updated_count' => count($fieldOrder)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $categoryField->getError() ?: 'Failed to update field order'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
    
    // Log error
    if (function_exists('logError')) {
        logError('API', 'update_field_order failed', [
            'error' => $e->getMessage(),
            'field_order' => $fieldOrder
        ]);
    }
}
