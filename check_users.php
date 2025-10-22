<?php
/**
 * Check existing users
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "<h1>Current Users in Database</h1>";
    
    $stmt = $pdo->query("SELECT id, email, first_name, last_name, role, is_active FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Active</th></tr>";
    
    foreach ($users as $user) {
        $active = $user['is_active'] ? 'Yes' : 'No';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['first_name']} {$user['last_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$active}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br><br>";
    echo "<p><a href='fix_password.php'>Fix Admin Password</a></p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
