<?php
/**
 * Simple Database Connection Test
 * Tests different username/password combinations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";
echo "<hr>";

// Test configurations
$configs = [
    ['user' => 'root', 'pass' => '', 'label' => 'root with empty password'],
    ['user' => 'root', 'pass' => 'root', 'label' => 'root with password "root"'],
    ['user' => 'root', 'pass' => 'password', 'label' => 'root with password "password"'],
    ['user' => 'ticketuser', 'pass' => 'secure_password', 'label' => 'ticketuser with "secure_password"'],
];

$host = 'localhost';
$dbname = 'ticketportaal';

echo "<h2>Testing connections to database: <code>$dbname</code></h2>";
echo "<p>Host: <code>$host</code></p>";
echo "<hr>";

$successConfig = null;

foreach ($configs as $config) {
    echo "<h3>Testing: {$config['label']}</h3>";
    echo "<p>User: <code>{$config['user']}</code>, Password: <code>" . ($config['pass'] ? str_repeat('*', strlen($config['pass'])) : '(empty)') . "</code></p>";
    
    try {
        $pdo = new PDO(
            "mysql:host=$host;charset=utf8mb4",
            $config['user'],
            $config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        
        echo "<p style='color: green; font-weight: bold;'>✓ Connection successful!</p>";
        
        // Check if database exists
        $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Database '$dbname' exists</p>";
            
            // Try to connect to the specific database
            try {
                $pdo = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $config['user'],
                    $config['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]
                );
                echo "<p style='color: green; font-weight: bold;'>✓ Successfully connected to database '$dbname'!</p>";
                
                // Check if users table exists
                $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
                if ($stmt->rowCount() > 0) {
                    echo "<p style='color: green;'>✓ Users table exists</p>";
                    
                    // Count users
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "<p>Users in database: <strong>{$result['count']}</strong></p>";
                } else {
                    echo "<p style='color: orange;'>⚠ Users table does not exist - you need to import schema.sql</p>";
                }
                
                $successConfig = $config;
                
            } catch (PDOException $e) {
                echo "<p style='color: red;'>✗ Cannot connect to database '$dbname': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Database '$dbname' does not exist - you need to create it</p>";
            echo "<p>Run this SQL: <code>CREATE DATABASE ticketportaal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code></p>";
        }
        
        echo "<hr>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Connection failed: " . $e->getMessage() . "</p>";
        echo "<hr>";
    }
}

if ($successConfig) {
    echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: #155724; margin-top: 0;'>✓ Working Configuration Found!</h2>";
    echo "<p><strong>Update your config/database.php with these settings:</strong></p>";
    echo "<pre style='background: #fff; padding: 10px; border-radius: 3px;'>";
    echo "define('DB_USER', '{$successConfig['user']}');\n";
    echo "define('DB_PASS', '{$successConfig['pass']}');";
    echo "</pre>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: #721c24; margin-top: 0;'>✗ No Working Configuration Found</h2>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Is MySQL running in XAMPP?</li>";
    echo "<li>What is your MySQL root password?</li>";
    echo "<li>Does the database 'ticketportaal' exist?</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Update <code>config/database.php</code> with the working credentials above</li>";
echo "<li>If database doesn't exist, create it in phpMyAdmin or run: <code>CREATE DATABASE ticketportaal;</code></li>";
echo "<li>Import the schema: Go to phpMyAdmin → ticketportaal → Import → select <code>database/schema.sql</code></li>";
echo "<li>Import the seed data: Import <code>database/seed.sql</code></li>";
echo "<li>Run <a href='fix_admin_password.php'>fix_admin_password.php</a> to set the admin password</li>";
echo "</ol>";
?>
