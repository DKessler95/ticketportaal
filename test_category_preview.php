<?php
/**
 * Test Category Fields Preview
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/functions.php';
require_once 'classes/CategoryField.php';

echo "<h1>Test Category Fields Preview</h1>";

// Initialize
$categoryField = new CategoryField();

// Test getting fields for Hardware category (ID 1)
echo "<h2>Hardware Category (ID 1)</h2>";
$fields = $categoryField->getFieldsByCategory(1);

echo "<p>Found " . count($fields) . " fields</p>";

if (count($fields) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field Name</th><th>Type</th><th>Options</th><th>Required</th></tr>";
    
    foreach ($fields as $field) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($field['field_name']) . "</td>";
        echo "<td>" . htmlspecialchars($field['field_type']) . "</td>";
        
        // Check if field_options is array or string
        if (is_array($field['field_options'])) {
            echo "<td>Array with " . count($field['field_options']) . " items: " . htmlspecialchars(implode(', ', $field['field_options'])) . "</td>";
        } else if (!empty($field['field_options'])) {
            echo "<td>String: " . htmlspecialchars($field['field_options']) . "</td>";
        } else {
            echo "<td>-</td>";
        }
        
        echo "<td>" . ($field['is_required'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No fields found for this category.</p>";
}

// Test Software category (ID 2)
echo "<h2>Software Category (ID 2)</h2>";
$fields = $categoryField->getFieldsByCategory(2);

echo "<p>Found " . count($fields) . " fields</p>";

if (count($fields) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field Name</th><th>Type</th><th>Options</th><th>Required</th></tr>";
    
    foreach ($fields as $field) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($field['field_name']) . "</td>";
        echo "<td>" . htmlspecialchars($field['field_type']) . "</td>";
        
        if (is_array($field['field_options'])) {
            echo "<td>Array with " . count($field['field_options']) . " items: " . htmlspecialchars(implode(', ', array_slice($field['field_options'], 0, 3))) . "...</td>";
        } else if (!empty($field['field_options'])) {
            echo "<td>String: " . htmlspecialchars(substr($field['field_options'], 0, 50)) . "...</td>";
        } else {
            echo "<td>-</td>";
        }
        
        echo "<td>" . ($field['is_required'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<br><br>";
echo "<p><a href='admin/category_fields_preview.php?category_id=1'>Preview Hardware Fields</a> | ";
echo "<a href='admin/category_fields_preview.php?category_id=2'>Preview Software Fields</a></p>";
?>
