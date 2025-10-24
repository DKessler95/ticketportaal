<?php
/**
 * Run Remaining Database Migrations
 * Including populate scripts for category fields
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Run Remaining Database Migrations</h1>";
echo "<p>Checking and running any missing migrations...</p>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    $success = 0;
    $failed = 0;
    $skipped = 0;
    
    echo "<table border='1' cellpadding='10' style='width: 100%; margin-top: 20px;'>";
    echo "<tr><th>Migration</th><th>Status</th><th>Message</th></tr>";
    
    // Check if populate_category_fields_for_ai.sql needs to run
    echo "<tr><td colspan='3' style='background: #f0f0f0; font-weight: bold;'>Category Fields Population</td></tr>";
    
    $file = __DIR__ . '/database/migrations/populate_category_fields_for_ai.sql';
    if (file_exists($file)) {
        // Check if category fields already exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM category_fields");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            $sql = file_get_contents($file);
            try {
                $pdo->exec($sql);
                echo "<tr><td>populate_category_fields_for_ai.sql</td><td style='color: green;'>✓ SUCCESS</td><td>Category fields populated</td></tr>";
                $success++;
            } catch (PDOException $e) {
                echo "<tr><td>populate_category_fields_for_ai.sql</td><td style='color: red;'>✗ FAILED</td><td>" . htmlspecialchars($e->getMessage()) . "</td></tr>";
                $failed++;
            }
        } else {
            echo "<tr><td>populate_category_fields_for_ai.sql</td><td style='color: blue;'>ℹ SKIPPED</td><td>Category fields already exist ($result[count] fields)</td></tr>";
            $skipped++;
        }
    } else {
        echo "<tr><td>populate_category_fields_for_ai.sql</td><td style='color: orange;'>⚠ NOT FOUND</td><td>File does not exist</td></tr>";
        $skipped++;
    }
    
    // Check other potential missing migrations
    echo "<tr><td colspan='3' style='background: #f0f0f0; font-weight: bold;'>Other Migrations</td></tr>";
    
    $otherMigrations = [
        'update_software_category_complete.sql' => 'Complete software category update',
    ];
    
    foreach ($otherMigrations as $migration => $description) {
        $file = __DIR__ . '/database/migrations/' . $migration;
        
        if (!file_exists($file)) {
            echo "<tr><td>$migration</td><td style='color: orange;'>⚠ SKIPPED</td><td>File not found</td></tr>";
            $skipped++;
            continue;
        }
        
        $sql = file_get_contents($file);
        
        if (trim($sql) === '') {
            echo "<tr><td>$migration</td><td style='color: orange;'>⚠ SKIPPED</td><td>Empty file</td></tr>";
            $skipped++;
            continue;
        }
        
        try {
            $pdo->exec($sql);
            echo "<tr><td>$migration</td><td style='color: green;'>✓ SUCCESS</td><td>$description</td></tr>";
            $success++;
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
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
    
    // Show current state
    echo "<h2>Current Database State</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Table</th><th>Count</th></tr>";
    
    $tables = ['categories', 'category_fields', 'users', 'tickets', 'configuration_items', 'changes', 'ticket_templates'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "<tr><td>$table</td><td>{$result['count']}</td></tr>";
        } catch (PDOException $e) {
            echo "<tr><td>$table</td><td style='color: red;'>Table not found</td></tr>";
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
        echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ All migrations completed!</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠ Some migrations failed. Check the errors above.</p>";
    }
    
    echo "<br><br>";
    echo "<p><a href='admin/category_fields.php'>Manage Category Fields</a> | ";
    echo "<a href='admin/category_fields_preview.php?category_id=2'>Preview Category Fields</a> | ";
    echo "<a href='login.php'>Go to Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>";
}
?>
