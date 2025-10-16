<?php
/**
 * Check for PHP errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Error Check</h1>";
echo "<hr>";

echo "<h2>1. Check PHP Syntax</h2>";

$files = [
    'login.php',
    'includes/functions.php',
    'includes/language.php',
    'includes/header.php',
    'includes/sidebar.php',
    'includes/footer.php',
    'config/config.php',
    'config/database.php',
    'config/email.php',
    'config/session.php',
    'classes/User.php',
    'classes/Database.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $output = [];
        $return = 0;
        exec("php -l $file 2>&1", $output, $return);
        
        if ($return === 0) {
            echo "<p style='color: green;'>✓ $file - OK</p>";
        } else {
            echo "<p style='color: red;'>✗ $file - ERROR</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ $file - NOT FOUND</p>";
    }
}

echo "<hr>";
echo "<h2>2. Try Loading Files</h2>";

try {
    echo "<p>Loading config/config.php...</p>";
    require_once 'config/config.php';
    echo "<p style='color: green;'>✓ config.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

try {
    echo "<p>Loading config/database.php...</p>";
    require_once 'config/database.php';
    echo "<p style='color: green;'>✓ database.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

try {
    echo "<p>Loading config/email.php...</p>";
    require_once 'config/email.php';
    echo "<p style='color: green;'>✓ email.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

try {
    echo "<p>Loading includes/functions.php...</p>";
    require_once 'includes/functions.php';
    echo "<p style='color: green;'>✓ functions.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>3. Apache Error Log</h2>";
echo "<p>Check: <code>C:\\xampp\\apache\\logs\\error.log</code></p>";
echo "<p>Last 20 lines:</p>";

$errorLog = 'C:\\xampp\\apache\\logs\\error.log';
if (file_exists($errorLog)) {
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -20);
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow: auto;'>";
    echo htmlspecialchars(implode('', $lastLines));
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>Error log not found at default location</p>";
}

echo "<hr>";
echo "<h2>4. PHP Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Loaded Extensions:</p>";
echo "<ul>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'imap'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $color = $loaded ? 'green' : 'red';
    $status = $loaded ? '✓' : '✗';
    echo "<li style='color: $color;'>$status $ext</li>";
}
echo "</ul>";
?>
