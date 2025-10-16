<?php
/**
 * Password Hash Test Script
 * Test if password verification works correctly
 */

// The hash from the database
$hash = '$2y$12$LQv3c1yycEir3LtJlTjkKuHPqVyJpbGlmKq7FZ8U9XvVkKvPX.Ks6';

// Test passwords
$passwords = [
    'Admin123!',
    'admin123!',
    'Admin123',
    'admin@kruit-en-kramer.nl'
];

echo "<h2>Password Hash Verification Test</h2>";
echo "<p>Testing hash: <code>" . htmlspecialchars($hash) . "</code></p>";
echo "<hr>";

foreach ($passwords as $password) {
    $result = password_verify($password, $hash);
    $status = $result ? '<span style="color: green;">✓ MATCH</span>' : '<span style="color: red;">✗ NO MATCH</span>';
    echo "<p>Password: <strong>" . htmlspecialchars($password) . "</strong> - $status</p>";
}

echo "<hr>";
echo "<h3>Generate New Hash</h3>";
$newPassword = 'Admin123!';
$newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
echo "<p>New hash for '$newPassword':</p>";
echo "<code>" . htmlspecialchars($newHash) . "</code>";

echo "<hr>";
echo "<h3>Database Connection Test</h3>";

require_once __DIR__ . '/config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Fetch admin user
    $stmt = $pdo->prepare("SELECT user_id, email, password, first_name, last_name, role, is_active FROM users WHERE email = ?");
    $stmt->execute(['admin@kruit-en-kramer.nl']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h4>Admin User Found:</h4>";
        echo "<ul>";
        echo "<li>User ID: " . $user['user_id'] . "</li>";
        echo "<li>Email: " . htmlspecialchars($user['email']) . "</li>";
        echo "<li>Name: " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</li>";
        echo "<li>Role: " . htmlspecialchars($user['role']) . "</li>";
        echo "<li>Active: " . ($user['is_active'] ? 'Yes' : 'No') . "</li>";
        echo "<li>Password Hash: <code>" . htmlspecialchars($user['password']) . "</code></li>";
        echo "</ul>";
        
        // Test password verification with database hash
        echo "<h4>Password Verification with Database Hash:</h4>";
        $testPassword = 'Admin123!';
        $verified = password_verify($testPassword, $user['password']);
        if ($verified) {
            echo "<p style='color: green; font-weight: bold;'>✓ Password '$testPassword' VERIFIED successfully!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>✗ Password '$testPassword' FAILED verification</p>";
            echo "<p>This means the hash in the database doesn't match the expected password.</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin user not found in database</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
