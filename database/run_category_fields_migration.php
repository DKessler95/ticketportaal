<?php
/**
 * Category Fields Migration Runner
 * 
 * Executes the populate_category_fields_for_ai.sql migration
 * and provides detailed feedback on the results.
 * 
 * Requirements: 2.1, 11.1
 */

require_once __DIR__ . '/../config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=================================================================\n";
echo "Category Fields Migration for AI RAG System\n";
echo "=================================================================\n\n";

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "✓ Database connection established\n\n";
    
    // Check if tables exist
    echo "Checking database structure...\n";
    $tables = ['categories', 'category_fields', 'ticket_field_values'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows === 0) {
            die("✗ Error: Table '$table' does not exist. Please run the base migration first.\n");
        }
        echo "  ✓ Table '$table' exists\n";
    }
    echo "\n";
    
    // Get current field counts before migration
    echo "Current field counts by category:\n";
    $result = $conn->query("
        SELECT c.category_id, c.name, COUNT(cf.field_id) as field_count
        FROM categories c
        LEFT JOIN category_fields cf ON c.category_id = cf.category_id
        GROUP BY c.category_id, c.name
        ORDER BY c.category_id
    ");
    
    $beforeCounts = [];
    while ($row = $result->fetch_assoc()) {
        $beforeCounts[$row['category_id']] = $row['field_count'];
        echo "  {$row['name']}: {$row['field_count']} fields\n";
    }
    echo "\n";
    
    // Read and execute migration file
    echo "Reading migration file...\n";
    $migrationFile = __DIR__ . '/migrations/populate_category_fields_for_ai.sql';
    
    if (!file_exists($migrationFile)) {
        die("✗ Error: Migration file not found at: $migrationFile\n");
    }
    
    $sql = file_get_contents($migrationFile);
    echo "✓ Migration file loaded\n\n";
    
    // Split SQL into individual statements
    echo "Executing migration...\n";
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // Filter out comments and empty statements
            $stmt = trim($stmt);
            return !empty($stmt) && 
                   !str_starts_with($stmt, '--') && 
                   !str_starts_with($stmt, '/*');
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Disable foreign key checks temporarily
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Execute statement
        if ($conn->multi_query($statement . ';')) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            $successCount++;
        } else {
            $errorCount++;
            $errors[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $conn->error
            ];
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "  Executed $successCount statements successfully\n";
    if ($errorCount > 0) {
        echo "  ✗ $errorCount statements failed\n";
        foreach ($errors as $error) {
            echo "    - {$error['statement']}\n";
            echo "      Error: {$error['error']}\n";
        }
    }
    echo "\n";
    
    // Get field counts after migration
    echo "Field counts after migration:\n";
    $result = $conn->query("
        SELECT c.category_id, c.name, COUNT(cf.field_id) as field_count
        FROM categories c
        LEFT JOIN category_fields cf ON c.category_id = cf.category_id
        GROUP BY c.category_id, c.name
        ORDER BY c.category_id
    ");
    
    $afterCounts = [];
    $totalFieldsAdded = 0;
    
    while ($row = $result->fetch_assoc()) {
        $afterCounts[$row['category_id']] = $row['field_count'];
        $before = $beforeCounts[$row['category_id']] ?? 0;
        $added = $row['field_count'] - $before;
        $totalFieldsAdded += $added;
        
        $status = $added > 0 ? "✓" : " ";
        echo "  $status {$row['name']}: {$row['field_count']} fields (+$added)\n";
    }
    echo "\n";
    
    // Detailed breakdown by category
    echo "Detailed field breakdown:\n";
    $result = $conn->query("
        SELECT c.name as category, cf.field_label, cf.field_type, cf.is_required
        FROM categories c
        JOIN category_fields cf ON c.category_id = cf.category_id
        WHERE cf.is_active = 1
        ORDER BY c.category_id, cf.field_order
    ");
    
    $currentCategory = '';
    while ($row = $result->fetch_assoc()) {
        if ($currentCategory !== $row['category']) {
            $currentCategory = $row['category'];
            echo "\n  {$currentCategory}:\n";
        }
        $required = $row['is_required'] ? '[Required]' : '[Optional]';
        echo "    - {$row['field_label']} ({$row['field_type']}) $required\n";
    }
    echo "\n";
    
    // Summary
    echo "=================================================================\n";
    echo "Migration Summary\n";
    echo "=================================================================\n";
    echo "Total fields added: $totalFieldsAdded\n";
    echo "Total active fields: " . array_sum($afterCounts) . "\n";
    
    if ($errorCount === 0) {
        echo "\n✓ Migration completed successfully!\n";
        echo "\nNext steps:\n";
        echo "1. Review the field configuration in admin/category_fields.php\n";
        echo "2. Test ticket creation with dynamic fields\n";
        echo "3. Train agents on filling out category fields correctly\n";
        echo "4. Begin data collection for AI training\n";
    } else {
        echo "\n⚠ Migration completed with $errorCount errors\n";
        echo "Please review the errors above and fix any issues.\n";
    }
    
    echo "\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
