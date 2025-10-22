<?php
/**
 * Debug Login Issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$email = 'admin@kruit-en-kramer.nl';
$password = 'Admin123!';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "<h1>Debug Login Issue</h1>";
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id, email, password, first_name, last_name, role, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color: red;'>✗ User not found in database!</p>";
        echo "<p>Let's check all users:</p>";
        
        $stmt = $pdo->query("SELECT user_id, email, first_name, last_name, role FROM users");
        $users = $stmt->fetchAll();
        
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th></tr>";
        foreach ($users as $u) {
            echo "<tr><td>{$u['user_id']}</td><td>{$u['email']}</td><td>{$u['first_name']} {$u['last_name']}</td><td>{$u['role']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✓ User found!</p>";
        echo "<ul>";
        echo "<li>User ID: {$user['user_id']}</li>";
        echo "<li>Email: {$user['email']}</li>";
        echo "<li>Name: {$user['first_name']} {$user['last_name']}</li>";
        echo "<li>Role: {$user['role']}</li>";
        echo "<li>Active: " . ($user['is_active'] ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
        
        echo "<h2>Password Verification</h2>";
        echo "<p>Current password hash in DB: <code>" . substr($user['password'], 0, 30) . "...</code></p>";
        
        // Test password verification
        if (password_verify($password, $user['password'])) {
            echo "<p style='color: green; font-weight: bold;'>✓ Password verification SUCCESSFUL!</p>";
            echo "<p>The password <code>$password</code> matches the hash in the database.</p>";
            echo "<p style='color: orange;'>⚠ If login still fails, there might be an issue with the login code or session.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>✗ Password verification FAILED!</p>";
            echo "<p>The password <code>$password</code> does NOT match the hash in the database.</p>";
            
            echo "<h3>Generating new password hash...</h3>";
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            echo "<p>New hash: <code>" . substr($newHash, 0, 30) . "...</code></p>";
            
            // Verify new hash works
            if (password_verify($password, $newHash)) {
                echo "<p style='color: green;'>✓ New hash verified successfully</p>";
                
                // Update database
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $result = $stmt->execute([$newHash, $email]);
                
                if ($result) {
                    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Password updated in database!</p>";
                    echo "<p>Please try logging in again with:</p>";
                    echo "<ul>";
                    echo "<li>Email: <code>$email</code></li>";
                    echo "<li>Password: <code>$password</code></li>";
                    echo "</ul>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to update password in database</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ New hash verification failed!</p>";
            }
        }
    }
    
    echo "<br><br>";
    echo "<p><a href='unlock_account.php'>Unlock Account</a> | <a href='login.php'>Go to Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
