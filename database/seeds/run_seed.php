<?php
/**
 * Run Database Seeds
 * 
 * Execute this file to clean and repopulate the categories table
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Database.php';

// Initialize session (for admin check)
initSession();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Error: Admin access required to run seeds.');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Seeder</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h1>Database Seeder</h1>
    <div class='alert alert-warning'>
        <strong>Warning:</strong> This will delete all existing categories and repopulate with seed data.
    </div>
";

try {
    $db = Database::getInstance();
    
    // Read SQL file
    $sqlFile = __DIR__ . '/seed_categories.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Seed file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "<div class='alert alert-info'>Executing " . count($statements) . " SQL statements...</div>";
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (stripos($statement, 'SELECT') === 0) {
            // Skip SELECT statements in transaction
            continue;
        }
        
        echo "<pre class='bg-light p-2 mb-2'>" . htmlspecialchars($statement) . "</pre>";
        $db->execute($statement);
    }
    
    $db->commit();
    
    echo "<div class='alert alert-success'><strong>Success!</strong> Categories seeded successfully.</div>";
    
    // Show results
    $categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_id");
    
    echo "<h3>Current Categories:</h3>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Priority</th><th>SLA Hours</th><th>Active</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($categories as $cat) {
        echo "<tr>";
        echo "<td>" . $cat['category_id'] . "</td>";
        echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
        echo "<td>" . htmlspecialchars($cat['description']) . "</td>";
        echo "<td><span class='badge bg-" . getPriorityColor($cat['default_priority']) . "'>" . $cat['default_priority'] . "</span></td>";
        echo "<td>" . $cat['sla_hours'] . "h</td>";
        echo "<td>" . ($cat['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>') . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    echo "<div class='mt-4'>";
    echo "<a href='" . SITE_URL . "/admin/categories.php' class='btn btn-primary'>Go to Categories</a> ";
    echo "<a href='" . SITE_URL . "/admin/category_fields.php' class='btn btn-success'>Manage Category Fields</a>";
    echo "</div>";
    
} catch (Exception $e) {
    if ($db) {
        $db->rollback();
    }
    
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<pre class='bg-light p-3'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div></body></html>";

function getPriorityColor($priority) {
    $colors = [
        'low' => 'secondary',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger'
    ];
    return $colors[$priority] ?? 'secondary';
}
