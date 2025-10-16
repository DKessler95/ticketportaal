<?php
/**
 * Create Test User Script
 * Creates a test user for testing the application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';

echo "<h1>Create Test User</h1>";
echo "<hr>";

// Test user details
$testUsers = [
    [
        'email' => 'user@kruit-en-kramer.nl',
        'password' => 'User123!',
        'first_name' => 'Test',
        'last_name' => 'User',
        'department' => 'Sales',
        'role' => 'user'
    ],
    [
        'email' => 'agent@kruit-en-kramer.nl',
        'password' => 'Agent123!',
        'first_name' => 'Test',
        'last_name' => 'Agent',
        'department' => 'ICT',
        'role' => 'agent'
    ]
];

$userObj = new User();

foreach ($testUsers as $userData) {
    echo "<h2>Creating {$userData['role']}: {$userData['email']}</h2>";
    
    // Check if user already exists
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        
        $stmt = $pdo->prepare("SELECT user_id, email FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            echo "<p style='color: orange;'>⚠ User already exists (ID: {$existing['user_id']})</p>";
            echo "<p>You can login with:</p>";
            echo "<ul>";
            echo "<li>Email: <code>{$userData['email']}</code></li>";
            echo "<li>Password: <code>{$userData['password']}</code></li>";
            echo "</ul>";
            continue;
        }
        
        // Create user
        $userId = $userObj->register(
            $userData['email'],
            $userData['password'],
            $userData['first_name'],
            $userData['last_name'],
            $userData['department'],
            $userData['role']
        );
        
        if ($userId) {
            echo "<p style='color: green; font-weight: bold;'>✓ User created successfully! (ID: $userId)</p>";
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Login Credentials:</strong><br>";
            echo "Email: <code>{$userData['email']}</code><br>";
            echo "Password: <code>{$userData['password']}</code><br>";
            echo "Role: <strong>{$userData['role']}</strong>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create user</p>";
            echo "<p>Error: " . htmlspecialchars($userObj->getError()) . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
}

echo "<h2>Summary</h2>";
echo "<p>Test users have been created. You can now login with:</p>";

echo "<div style='background: #e7f3ff; padding: 20px; border: 2px solid #0066cc; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>👤 Regular User</h3>";
echo "<p><strong>Email:</strong> <code>user@kruit-en-kramer.nl</code></p>";
echo "<p><strong>Password:</strong> <code>User123!</code></p>";
echo "<p><strong>Role:</strong> User (can create and view own tickets)</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border: 2px solid #ffc107; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🛠️ Agent</h3>";
echo "<p><strong>Email:</strong> <code>agent@kruit-en-kramer.nl</code></p>";
echo "<p><strong>Password:</strong> <code>Agent123!</code></p>";
echo "<p><strong>Role:</strong> Agent (can view and manage assigned tickets)</p>";
echo "</div>";

echo "<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>👑 Admin</h3>";
echo "<p><strong>Email:</strong> <code>admin@kruit-en-kramer.nl</code></p>";
echo "<p><strong>Password:</strong> <code>Admin123!</code></p>";
echo "<p><strong>Role:</strong> Admin (full system access)</p>";
echo "</div>";

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li><a href='logout.php'>Logout from admin account</a></li>";
echo "<li><a href='login.php'>Login as test user</a></li>";
echo "<li>Test creating tickets, viewing dashboard, etc.</li>";
echo "</ol>";

echo "<p><a href='login.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
?>
