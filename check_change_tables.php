<?php
/**
 * Quick check if Change Management tables exist
 */

require_once 'config/database.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    
    $tables = ['changes', 'change_history', 'change_ci_relations', 'ticket_change_relations'];
    
    echo "Checking Change Management tables:\n\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table - EXISTS\n";
        } else {
            echo "✗ $table - MISSING\n";
        }
    }
    
    echo "\n";
    
    // Check if Change class exists
    if (file_exists('classes/Change.php')) {
        echo "✓ Change.php class - EXISTS\n";
    } else {
        echo "✗ Change.php class - MISSING\n";
    }
    
    // Check for Change Management admin pages
    $adminPages = ['admin/changes.php', 'admin/change_detail.php'];
    foreach ($adminPages as $page) {
        if (file_exists($page)) {
            echo "✓ $page - EXISTS\n";
        } else {
            echo "✗ $page - MISSING\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
