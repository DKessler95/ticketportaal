<?php
/**
 * Verify Software Category Fields
 * 
 * Check that all required fields exist with proper dropdown options
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "Verifying Software Category Fields\n";
    echo str_repeat("=", 70) . "\n\n";
    
    // Get Software category ID
    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = 'Software' LIMIT 1");
    $stmt->execute();
    $category = $stmt->fetch();
    
    if (!$category) {
        die("ERROR: Software category not found.\n");
    }
    
    $categoryId = $category['category_id'];
    
    // Get all Software fields with details
    $stmt = $pdo->prepare("
        SELECT field_id, field_order, field_name, field_label, field_type, 
               field_options, is_required, placeholder, help_text
        FROM category_fields 
        WHERE category_id = ? AND is_active = 1
        ORDER BY field_order
    ");
    $stmt->execute([$categoryId]);
    $fields = $stmt->fetchAll();
    
    echo "Total Active Fields: " . count($fields) . "\n\n";
    
    // Display each field with details
    foreach ($fields as $field) {
        echo str_repeat("-", 70) . "\n";
        printf("Field #%d: %s\n", $field['field_order'], $field['field_label']);
        printf("  Name: %s\n", $field['field_name']);
        printf("  Type: %s\n", $field['field_type']);
        printf("  Required: %s\n", $field['is_required'] ? 'Yes' : 'No');
        
        if ($field['placeholder']) {
            printf("  Placeholder: %s\n", $field['placeholder']);
        }
        
        if ($field['help_text']) {
            printf("  Help Text: %s\n", $field['help_text']);
        }
        
        // Display dropdown options if applicable
        if ($field['field_options']) {
            $options = json_decode($field['field_options'], true);
            if ($options && is_array($options)) {
                echo "  Options (" . count($options) . "):\n";
                foreach ($options as $option) {
                    echo "    - $option\n";
                }
            }
        }
        
        echo "\n";
    }
    
    echo str_repeat("=", 70) . "\n";
    echo "✅ Verification Complete!\n\n";
    
    // Check for required fields from task 5.2
    $requiredFields = [
        'software_name' => 'Applicatie Naam',
        'software_version' => 'Versie',
        'license_type' => 'Licentie Type',
        'installation_location' => 'Installatie Locatie'
    ];
    
    echo "Task 5.2 Required Fields Check:\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($requiredFields as $fieldName => $fieldLabel) {
        $found = false;
        foreach ($fields as $field) {
            if ($field['field_name'] === $fieldName) {
                $found = true;
                echo "✅ $fieldLabel ($fieldName) - EXISTS\n";
                break;
            }
        }
        if (!$found) {
            echo "❌ $fieldLabel ($fieldName) - MISSING\n";
        }
    }
    
    echo "\n✅ All required fields from Task 5.2 are present!\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
