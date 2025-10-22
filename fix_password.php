<?php
/**
 * Fix Admin Password
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Fix Admin Password</h1>";

$password = 'Admin123!';
$email = 'admin@kruit-en-kramer.nl';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    // Generate new hash
    $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    echo "<p>Generating new password hash...</p>";
    echo "<p>New hash: <code>$newHash</code></p>";
    
    // Verify it works
    if (password_verify($password, $newHash)) {
        echo "<p style='color: green;'>✓ New hash verified successfully</p>";
        
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $result = $stmt->execute([$newHash, $email]);
        
        if ($result) {
            echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Password updated successfully!</p>";
            echo "<p>You can now login with:</p>";
            echo "<ul>";
            echo "<li>Email: <code>$email</code></li>";
            echo "<li>Password: <code>$password</code></li>";
            echo "</ul>";
            echo "<p><a href='login.php'>Go to Login Page</a></p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to update password</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ New hash verification failed!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
