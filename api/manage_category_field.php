<?php
/**
 * API Endpoint: Manage Category Field
 * 
 * Handles CRUD operations for category fields
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

$action = $_POST['action'] ?? '';
$categoryField = new CategoryField();

try {
    switch ($action) {
        case 'create':
            // Validate required fields
            if (empty($_POST['category_id']) || empty($_POST['field_name']) || 
                empty($_POST['field_label']) || empty($_POST['field_type'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required fields'
                ]);
                exit;
            }
            
            $categoryId = (int)$_POST['category_id'];
            $fieldData = [
                'field_name' => sanitizeFieldName($_POST['field_name']),
                'field_label' => trim($_POST['field_label']),
                'field_type' => $_POST['field_type'],
                'is_required' => isset($_POST['is_required']) && $_POST['is_required'] == '1',
                'placeholder' => trim($_POST['placeholder'] ?? ''),
                'help_text' => trim($_POST['help_text'] ?? '')
            ];
            
            // Handle field options (for select, radio, checkbox)
            if (!empty($_POST['field_options'])) {
                $options = json_decode($_POST['field_options'], true);
                if ($options !== null) {
                    $fieldData['field_options'] = $options;
                }
            }
            
            // Handle conditional logic
            if (!empty($_POST['conditional_logic'])) {
                $conditional = json_decode($_POST['conditional_logic'], true);
                if ($conditional !== null) {
                    $fieldData['conditional_logic'] = $conditional;
                }
            }
            
            $fieldId = $categoryField->createField($categoryId, $fieldData);
            
            if ($fieldId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Field created successfully',
                    'field_id' => $fieldId
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $categoryField->getError() ?: 'Failed to create field'
                ]);
            }
            break;
            
        case 'update':
            // Validate required fields
            if (empty($_POST['field_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Field ID is required'
                ]);
                exit;
            }
            
            $fieldId = (int)$_POST['field_id'];
            $fieldData = [];
            
            // Only update provided fields
            if (isset($_POST['field_name'])) {
                $fieldData['field_name'] = sanitizeFieldName($_POST['field_name']);
            }
            if (isset($_POST['field_label'])) {
                $fieldData['field_label'] = trim($_POST['field_label']);
            }
            if (isset($_POST['field_type'])) {
                $fieldData['field_type'] = $_POST['field_type'];
            }
            if (isset($_POST['is_required'])) {
                $fieldData['is_required'] = $_POST['is_required'] == '1';
            }
            if (isset($_POST['placeholder'])) {
                $fieldData['placeholder'] = trim($_POST['placeholder']);
            }
            if (isset($_POST['help_text'])) {
                $fieldData['help_text'] = trim($_POST['help_text']);
            }
            if (isset($_POST['is_active'])) {
                $fieldData['is_active'] = $_POST['is_active'] == '1';
            }
            
            // Handle field options
            if (isset($_POST['field_options'])) {
                $options = json_decode($_POST['field_options'], true);
                if ($options !== null) {
                    $fieldData['field_options'] = $options;
                }
            }
            
            // Handle conditional logic
            if (isset($_POST['conditional_logic'])) {
                $conditional = json_decode($_POST['conditional_logic'], true);
                if ($conditional !== null) {
                    $fieldData['conditional_logic'] = $conditional;
                }
            }
            
            $result = $categoryField->updateField($fieldId, $fieldData);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Field updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $categoryField->getError() ?: 'Failed to update field'
                ]);
            }
            break;
            
        case 'delete':
            // Validate required fields
            if (empty($_POST['field_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Field ID is required'
                ]);
                exit;
            }
            
            $fieldId = (int)$_POST['field_id'];
            $result = $categoryField->deleteField($fieldId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Field deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $categoryField->getError() ?: 'Failed to delete field'
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
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
        logError('API', 'manage_category_field failed', [
            'error' => $e->getMessage(),
            'action' => $action
        ]);
    }
}

/**
 * Sanitize field name (alphanumeric and underscore only)
 */
function sanitizeFieldName($name) {
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9_]/', '_', $name);
    $name = preg_replace('/_+/', '_', $name);
    $name = trim($name, '_');
    return $name;
}
