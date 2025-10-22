<?php
/**
 * Fix Location Migration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Fix Location Migration</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "<p>Adding location column to users table...</p>";
    
    try {
        // Add location column
        $pdo->exec("ALTER TABLE users ADD COLUMN location VARCHAR(100) NULL");
        echo "<p style='color: green;'>✓ Location column added</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: blue;'>ℹ Location column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    try {
        // Add index
        $pdo->exec("ALTER TABLE users ADD INDEX idx_location (location)");
        echo "<p style='color: green;'>✓ Location index added</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "<p style='color: blue;'>ℹ Location index already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Update existing users
    $pdo->exec("UPDATE users SET location = 'Kruit en Kramer' WHERE location IS NULL");
    echo "<p style='color: green;'>✓ Default location set for existing users</p>";
    
    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Location migration completed!</p>";
    echo "<br><br>";
    echo "<p><a href='unlock_account.php'>Unlock Account</a> | <a href='login.php'>Go to Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
