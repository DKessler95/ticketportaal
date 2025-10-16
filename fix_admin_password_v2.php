<?php
/**
 * Fix Admin Password Script V2
 * Resets the admin password to Admin123!
 * Uses inline config to avoid cache issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Admin Password V2</h1>";
echo "<hr>";

// Database credentials (inline to avoid cache)
$dbHost = 'localhost';
$dbName = 'ticketportaal';
$dbUser = 'root';
$dbPass = '';  // Empty password for XAMPP default

echo "<p>Using credentials:</p>";
echo "<ul>";
echo "<li>Host: <code>$dbHost</code></li>";
echo "<li>Database: <code>$dbName</code></li>";
echo "<li>User: <code>$dbUser</code></li>";
echo "<li>Password: <code>" . ($dbPass ? str_repeat('*', strlen($dbPass)) : '(empty)') . "</code></li>";
echo "</ul>";
echo "<hr>";

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    
    echo "<p style='color: green; font-weight: bold;'>✓ Database connected successfully!</p>";
    echo "<hr>";
    
    // Check current admin user
    $stmt = $pdo->prepare("SELECT user_id, email, password, first_name, last_name, role, is_active FROM users WHERE email = ?");
    $stmt->execute(['admin@kruit-en-kramer.nl']);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color: red;'>✗ Admin user not found!</p>";
        echo "<p>Creating admin user...</p>";
        
        // Create admin user
        $newPassword = 'Admin123!';
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, department, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            'admin@kruit-en-kramer.nl',
            $hashedPassword,
            'System',
            'Administrator',
            'ICT',
            'admin',
            1
        ]);
        
        if ($result) {
            echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Admin user created successfully!</p>";
            echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2 style='color: #155724; margin-top: 0;'>Login Credentials:</h2>";
            echo "<p><strong>Email:</strong> <code>admin@kruit-en-kramer.nl</code></p>";
            echo "<p><strong>Password:</strong> <code>Admin123!</code></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create admin user</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Admin user found</p>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>User ID</td><td>" . $user['user_id'] . "</td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
        echo "<tr><td>Name</td><td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td></tr>";
        echo "<tr><td>Role</td><td>" . htmlspecialchars($user['role']) . "</td></tr>";
        echo "<tr><td>Active</td><td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td></tr>";
        echo "</table>";
        echo "<hr>";
        
        // Test current password
        $testPassword = 'Admin123!';
        echo "<h3>Testing Password: <code>$testPassword</code></h3>";
        $verified = password_verify($testPassword, $user['password']);
        
        if ($verified) {
            echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Password is CORRECT!</p>";
            echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2 style='color: #155724; margin-top: 0;'>You can login now!</h2>";
            echo "<p><strong>Email:</strong> <code>admin@kruit-en-kramer.nl</code></p>";
            echo "<p><strong>Password:</strong> <code>Admin123!</code></p>";
            echo "</div>";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>⚠ Current password does NOT match 'Admin123!'</p>";
            echo "<p>Updating password...</p>";
            
            // Generate new hash
            $newPassword = 'Admin123!';
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            echo "<p>Old hash: <code style='font-size: 10px;'>" . htmlspecialchars($user['password']) . "</code></p>";
            echo "<p>New hash: <code style='font-size: 10px;'>" . htmlspecialchars($hashedPassword) . "</code></p>";
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $result = $stmt->execute([$hashedPassword, $user['user_id']]);
            
            if ($result) {
                echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ PASSWORD UPDATED SUCCESSFULLY!</p>";
                
                // Verify the new password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                $updatedUser = $stmt->fetch();
                
                $verifyNew = password_verify($newPassword, $updatedUser['password']);
                
                if ($verifyNew) {
                    echo "<p style='color: green; font-weight: bold;'>✓ New password verified successfully!</p>";
                    echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 5px; margin: 20px 0;'>";
                    echo "<h2 style='color: #155724; margin-top: 0;'>Login Credentials:</h2>";
                    echo "<p><strong>Email:</strong> <code>admin@kruit-en-kramer.nl</code></p>";
                    echo "<p><strong>Password:</strong> <code>Admin123!</code></p>";
                    echo "</div>";
                } else {
                    echo "<p style='color: red;'>✗ Verification failed after update!</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ Failed to update password</p>";
            }
        }
    }
    
    // Show all users in database
    echo "<hr>";
    echo "<h2>All Users in Database:</h2>";
    $stmt = $pdo->query("SELECT user_id, email, first_name, last_name, role, is_active FROM users ORDER BY user_id");
    $allUsers = $stmt->fetchAll();
    
    if ($allUsers) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Active</th></tr>";
        foreach ($allUsers as $u) {
            echo "<tr>";
            echo "<td>" . $u['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($u['email']) . "</td>";
            echo "<td>" . htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($u['role']) . "</td>";
            echo "<td>" . ($u['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Is MySQL running in XAMPP?</li>";
    echo "<li>Are the database credentials correct?</li>";
    echo "<li>Does the database 'ticketportaal' exist?</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='login.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
?>
