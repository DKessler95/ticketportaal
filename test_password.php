<?php
/**
 * Password Hash Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Password Hash Test</h1>";

// The password from seed.sql
$password = 'Admin123!';

// The hash from seed.sql
$hashFromSeed = '$2y$12$LQv3c1yycEir3LtJlTjkKuHPqVyJpbGlmKq7FZ8U9XvVkKvPX.Ks6';

echo "<h2>Test 1: Verify seed hash</h2>";
echo "<p>Password: <code>$password</code></p>";
echo "<p>Hash from seed: <code>$hashFromSeed</code></p>";

$result = password_verify($password, $hashFromSeed);
echo "<p>Result: " . ($result ? "<strong style='color: green;'>✓ MATCH</strong>" : "<strong style='color: red;'>✗ NO MATCH</strong>") . "</p>";

// Generate a new hash
echo "<hr><h2>Test 2: Generate new hash</h2>";
$newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "<p>New hash: <code>$newHash</code></p>";

$result2 = password_verify($password, $newHash);
echo "<p>Verify new hash: " . ($result2 ? "<strong style='color: green;'>✓ MATCH</strong>" : "<strong style='color: red;'>✗ NO MATCH</strong>") . "</p>";

// Check database
echo "<hr><h2>Test 3: Check database hash</h2>";
require_once 'config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute(['admin@kruit-en-kramer.nl']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p>Hash from database: <code>" . htmlspecialchars($user['password']) . "</code></p>";
        
        $result3 = password_verify($password, $user['password']);
        echo "<p>Verify database hash: " . ($result3 ? "<strong style='color: green;'>✓ MATCH</strong>" : "<strong style='color: red;'>✗ NO MATCH</strong>") . "</p>";
        
        // Compare hashes
        echo "<hr><h2>Test 4: Compare hashes</h2>";
        echo "<p>Seed hash matches DB hash: " . ($hashFromSeed === $user['password'] ? "<strong style='color: green;'>✓ YES</strong>" : "<strong style='color: red;'>✗ NO</strong>") . "</p>";
        
        // Show hash details
        echo "<hr><h2>Test 5: Hash details</h2>";
        echo "<p>Seed hash length: " . strlen($hashFromSeed) . "</p>";
        echo "<p>DB hash length: " . strlen($user['password']) . "</p>";
        
        // Character by character comparison
        if ($hashFromSeed !== $user['password']) {
            echo "<h3>Character differences:</h3>";
            $maxLen = max(strlen($hashFromSeed), strlen($user['password']));
            for ($i = 0; $i < $maxLen; $i++) {
                $seedChar = isset($hashFromSeed[$i]) ? $hashFromSeed[$i] : 'MISSING';
                $dbChar = isset($user['password'][$i]) ? $user['password'][$i] : 'MISSING';
                if ($seedChar !== $dbChar) {
                    echo "<p>Position $i: Seed='$seedChar' DB='$dbChar'</p>";
                }
            }
        }
        
        // Try updating with new hash
        echo "<hr><h2>Test 6: Update with new hash</h2>";
        echo "<p>Would you like to update the database with a new working hash?</p>";
        echo "<p><a href='fix_password.php'>Click here to fix the password</a></p>";
        
    } else {
        echo "<p style='color: red;'>User not found in database</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
