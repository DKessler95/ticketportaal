<?php
/**
 * Unlock Admin Account
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$email = 'admin@kruit-en-kramer.nl';

echo "<h1>Unlock Admin Account</h1>";

// Clear the failed login session data
$key = 'failed_login_' . md5($email);

if (isset($_SESSION[$key])) {
    unset($_SESSION[$key]);
    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Account unlocked successfully!</p>";
    echo "<p>The failed login attempts for <code>$email</code> have been cleared.</p>";
} else {
    echo "<p style='color: blue;'>ℹ No active lockout found for this account.</p>";
}

echo "<p>You can now login with:</p>";
echo "<ul>";
echo "<li>Email: <code>$email</code></li>";
echo "<li>Password: <code>Admin123!</code></li>";
echo "</ul>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
