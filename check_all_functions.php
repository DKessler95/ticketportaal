<?php
/**
 * Comprehensive Function Check
 * Verifies all required functions exist before running the application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Comprehensive Function Check</h1>";
echo "<hr>";

// Load functions
$basePath = 'D:/xampp/htdocs/ticketportaal';
require_once $basePath . '/includes/functions.php';

// List of all required functions
$requiredFunctions = [
    // Core functions
    'logError',
    'sanitizeInput',
    'validateEmail',
    'escapeOutput',
    
    // Session functions
    'initSession',
    'checkLogin',
    'requireLogin',
    'checkRole',
    'requireRole',
    'getCurrentUser',
    'getCurrentUserId',
    'getCurrentUserRole',
    
    // CSRF functions
    'generateCSRFToken',
    'validateCSRFToken',
    'outputCSRFField',
    'verifyCSRFToken',
    
    // Navigation functions
    'redirectTo',
    'redirectToDashboard',
    'getDashboardUrl',
    'getBaseUrl',
    'isActive',
    
    // Display functions
    'formatDate',
    'getStatusBadge',
    'getPriorityBadge',
    'getRoleBadge',
    
    // Validation functions
    'validateRequired',
    'validateLength',
    'validatePassword',
    'validateInteger',
    'validateEnum',
    
    // File functions
    'generateRandomFilename',
    'validateFileUpload',
    
    // Sanitization functions
    'sanitizeText',
    'sanitizeHTML',
];

$missing = [];
$existing = [];

foreach ($requiredFunctions as $func) {
    if (function_exists($func)) {
        $existing[] = $func;
    } else {
        $missing[] = $func;
    }
}

echo "<h2>Function Check Results</h2>";
echo "<p>Total functions checked: <strong>" . count($requiredFunctions) . "</strong></p>";
echo "<p>Existing: <strong style='color: green;'>" . count($existing) . "</strong></p>";
echo "<p>Missing: <strong style='color: " . (count($missing) > 0 ? 'red' : 'green') . ";'>" . count($missing) . "</strong></p>";

if (count($missing) > 0) {
    echo "<hr>";
    echo "<h3 style='color: red;'>❌ Missing Functions:</h3>";
    echo "<ul>";
    foreach ($missing as $func) {
        echo "<li><code>$func()</code></li>";
    }
    echo "</ul>";
    echo "<p style='color: red; font-weight: bold;'>⚠ Application will NOT work correctly!</p>";
} else {
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ All Required Functions Exist!</h3>";
    echo "<p>The application should work correctly.</p>";
}

echo "<hr>";
echo "<h2>Class Check</h2>";

$requiredClasses = [
    'Database',
    'User',
    'Ticket',
    'Category',
    'KnowledgeBase',
    'EmailHandler',
    'Report',
];

require_once $basePath . '/classes/Database.php';
require_once $basePath . '/classes/User.php';
require_once $basePath . '/classes/Ticket.php';

$missingClasses = [];
$existingClasses = [];

foreach ($requiredClasses as $class) {
    try {
        require_once $basePath . "/classes/$class.php";
        if (class_exists($class)) {
            $existingClasses[] = $class;
        } else {
            $missingClasses[] = $class;
        }
    } catch (Exception $e) {
        $missingClasses[] = $class . " (Error: " . $e->getMessage() . ")";
    }
}

echo "<p>Existing classes: <strong style='color: green;'>" . count($existingClasses) . "</strong></p>";
echo "<p>Missing classes: <strong style='color: " . (count($missingClasses) > 0 ? 'orange' : 'green') . ";'>" . count($missingClasses) . "</strong></p>";

if (count($missingClasses) > 0) {
    echo "<h4 style='color: orange;'>⚠ Missing Classes (may be optional):</h4>";
    echo "<ul>";
    foreach ($missingClasses as $class) {
        echo "<li><code>$class</code></li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Configuration Check</h2>";

$configChecks = [
    'SITE_URL' => defined('SITE_URL'),
    'DB_HOST' => defined('DB_HOST'),
    'DB_NAME' => defined('DB_NAME'),
    'DB_USER' => defined('DB_USER'),
    'DB_PASS' => defined('DB_PASS'),
    'DEBUG_MODE' => defined('DEBUG_MODE'),
    'SESSION_TIMEOUT' => defined('SESSION_TIMEOUT'),
];

$allConfigOk = true;
foreach ($configChecks as $const => $exists) {
    $status = $exists ? '✓' : '✗';
    $color = $exists ? 'green' : 'red';
    echo "<p style='color: $color;'>$status <code>$const</code></p>";
    if (!$exists) $allConfigOk = false;
}

if ($allConfigOk) {
    echo "<p style='color: green; font-weight: bold;'>✅ All configuration constants defined!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Some configuration constants missing!</p>";
}

echo "<hr>";

if (count($missing) == 0 && $allConfigOk) {
    echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 5px;'>";
    echo "<h2 style='color: #155724; margin-top: 0;'>✅ ALL CHECKS PASSED!</h2>";
    echo "<p>The application is ready to use.</p>";
    echo "<p><a href='admin/index.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; border-radius: 5px;'>";
    echo "<h2 style='color: #721c24; margin-top: 0;'>❌ CHECKS FAILED</h2>";
    echo "<p>Please fix the issues above before using the application.</p>";
    echo "</div>";
}
?>
