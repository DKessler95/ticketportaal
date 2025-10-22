<?php
/**
 * Verify Category Fields Migration
 * 
 * Verifies that all category fields have been created correctly
 * for the AI RAG system.
 */

require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "Category Fields Verification\n";
echo "=================================================================\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    // Get field counts by category
    $result = $conn->query("
        SELECT c.category_id, c.name, COUNT(cf.field_id) as field_count
        FROM categories c
        LEFT JOIN category_fields cf ON c.category_id = cf.category_id AND cf.is_active = 1
        GROUP BY c.category_id, c.name
        ORDER BY c.category_id
    ");
    
    echo "Active fields per category:\n\n";
    $totalFields = 0;
    
    while ($row = $result->fetch_assoc()) {
        $totalFields += $row['field_count'];
        $status = $row['field_count'] > 0 ? "✓" : "✗";
        echo "  $status {$row['name']}: {$row['field_count']} fields\n";
    }
    
    echo "\n";
    echo "Total active fields: $totalFields\n";
    echo "\n=================================================================\n";
    
    // Detailed breakdown
    echo "\nDetailed field list:\n\n";
    $result = $conn->query("
        SELECT c.name as category, cf.field_label, cf.field_type, 
               cf.is_required, cf.field_order
        FROM categories c
        JOIN category_fields cf ON c.category_id = cf.category_id
        WHERE cf.is_active = 1
        ORDER BY c.category_id, cf.field_order
    ");
    
    $currentCategory = '';
    while ($row = $result->fetch_assoc()) {
        if ($currentCategory !== $row['category']) {
            $currentCategory = $row['category'];
            echo "\n{$currentCategory}:\n";
        }
        $required = $row['is_required'] ? '✓ Required' : '  Optional';
        echo "  {$row['field_order']}. {$row['field_label']} ({$row['field_type']}) - $required\n";
    }
    
    echo "\n=================================================================\n";
    echo "✓ Verification complete!\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
