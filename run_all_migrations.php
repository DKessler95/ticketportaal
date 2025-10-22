<?php
/**
 * Run All Database Migrations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Database Migration Runner</h1>";
echo "<p>Running all SQL migrations...</p>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    // List of migrations in order
    $migrations = [
        'add_location_to_users.sql',
        'add_user_departments.sql',
        'add_templates_table.sql',
        'add_default_templates.sql',
        'add_review_comment.sql',
        'add_sales_facilitair_departments.sql',
        'create_change_requests_table.sql',
        'add_category_fields.sql',
        'add_company_specific_software.sql',
        'add_company_software_details.sql',
        'update_software_category_simple.sql',
        'add_performance_indexes_for_ai.sql',
        '007_create_knowledge_graph_schema.sql',
    ];
    
    $success = 0;
    $failed = 0;
    $skipped = 0;
    
    echo "<table border='1' cellpadding='10' style='width: 100%; margin-top: 20px;'>";
    echo "<tr><th>Migration</th><th>Status</th><th>Message</th></tr>";
    
    foreach ($migrations as $migration) {
        $file = __DIR__ . '/database/migrations/' . $migration;
        
        if (!file_exists($file)) {
            echo "<tr><td>$migration</td><td style='color: orange;'>⚠ SKIPPED</td><td>File not found</td></tr>";
            $skipped++;
            continue;
        }
        
        $sql = file_get_contents($file);
        
        // Skip empty files
        if (trim($sql) === '') {
            echo "<tr><td>$migration</td><td style='color: orange;'>⚠ SKIPPED</td><td>Empty file</td></tr>";
            $skipped++;
            continue;
        }
        
        try {
            // Execute the SQL (handle multiple statements)
            $pdo->exec($sql);
            echo "<tr><td>$migration</td><td style='color: green;'>✓ SUCCESS</td><td>Migration applied</td></tr>";
            $success++;
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Check if it's a "duplicate column" or "table exists" error (which means already migrated)
            if (strpos($errorMsg, 'Duplicate column') !== false || 
                strpos($errorMsg, 'Duplicate key') !== false ||
                strpos($errorMsg, 'already exists') !== false) {
                echo "<tr><td>$migration</td><td style='color: blue;'>ℹ ALREADY APPLIED</td><td>Already migrated</td></tr>";
                $skipped++;
            } else {
                echo "<tr><td>$migration</td><td style='color: red;'>✗ FAILED</td><td>" . htmlspecialchars($errorMsg) . "</td></tr>";
                $failed++;
            }
        }
    }
    
    echo "</table>";
    
    echo "<h2>Summary</h2>";
    echo "<ul>";
    echo "<li style='color: green;'>✓ Successful: $success</li>";
    echo "<li style='color: blue;'>ℹ Skipped/Already Applied: $skipped</li>";
    echo "<li style='color: red;'>✗ Failed: $failed</li>";
    echo "</ul>";
    
    if ($failed === 0) {
        echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ All migrations completed successfully!</p>";
        echo "<p>You can now try logging in again.</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠ Some migrations failed. Check the errors above.</p>";
    }
    
    echo "<br><br>";
    echo "<p><a href='test_connection.php'>Test Database</a> | <a href='unlock_account.php'>Unlock Account</a> | <a href='login.php'>Go to Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>";
}
?>
