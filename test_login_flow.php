<?php
/**
 * Test Login Flow
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Login Flow</h1>";

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/User.php';

// Initialize session
initSession();

$email = 'admin@kruit-en-kramer.nl';
$password = 'Admin123!';

echo "<h2>Testing login with:</h2>";
echo "<ul>";
echo "<li>Email: <code>$email</code></li>";
echo "<li>Password: <code>$password</code></li>";
echo "</ul>";

// Clear any existing lockout
$key = 'failed_login_' . md5($email);
if (isset($_SESSION[$key])) {
    unset($_SESSION[$key]);
    echo "<p style='color: blue;'>ℹ Cleared existing lockout data</p>";
}

// Attempt login
$user = new User();
echo "<h2>Attempting login...</h2>";

if ($user->login($email, $password)) {
    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Login SUCCESSFUL!</p>";
    echo "<h3>Session Data:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo "<p><a href='admin/index.php'>Go to Admin Dashboard</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold; font-size: 18px;'>✗ Login FAILED!</p>";
    $error = $user->getError();
    echo "<p>Error: <code>$error</code></p>";
    
    echo "<h3>Debug Information:</h3>";
    
    // Check if user exists
    require_once 'config/database.php';
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            DB_OPTIONS
        );
        
        $stmt = $pdo->prepare("SELECT user_id, email, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $dbUser = $stmt->fetch();
        
        if ($dbUser) {
            echo "<p>✓ User exists in database</p>";
            echo "<p>User ID: {$dbUser['user_id']}</p>";
            echo "<p>Active: " . ($dbUser['is_active'] ? 'Yes' : 'No') . "</p>";
        } else {
            echo "<p>✗ User NOT found in database</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
    }
}

echo "<br><br>";
echo "<p><a href='debug_login.php'>Debug Login</a> | <a href='unlock_account.php'>Unlock Account</a> | <a href='login.php'>Go to Login Page</a></p>";
?>
