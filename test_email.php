<?php
/**
 * Test Email Sending
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/email.php';

echo "<h1>Email Test</h1>";
echo "<hr>";

echo "<h2>Email Configuration</h2>";
echo "<ul>";
echo "<li>SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT DEFINED') . "</li>";
echo "<li>SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT DEFINED') . "</li>";
echo "<li>SMTP User: " . (defined('SMTP_USER') ? SMTP_USER : 'NOT DEFINED') . "</li>";
echo "<li>SMTP Pass: " . (defined('SMTP_PASS') ? (SMTP_PASS ? '***SET***' : 'EMPTY') : 'NOT DEFINED') . "</li>";
echo "<li>From Email: " . (defined('FROM_EMAIL') ? FROM_EMAIL : 'NOT DEFINED') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>Test 1: PHP mail() function</h2>";

$to = 'test@example.com';
$subject = 'Test Email';
$message = 'This is a test email';
$headers = 'From: ' . FROM_EMAIL;

echo "<p>Attempting to send email using PHP mail() function...</p>";
echo "<p><strong>Note:</strong> PHP mail() requires proper SMTP configuration in php.ini</p>";

$result = @mail($to, $subject, $message, $headers);

if ($result) {
    echo "<p style='color: green;'>✓ mail() returned true</p>";
} else {
    echo "<p style='color: red;'>✗ mail() returned false</p>";
    echo "<p>This is expected - PHP mail() doesn't work well with SMTP authentication</p>";
}

echo "<hr>";
echo "<h2>Test 2: Check if PHPMailer is available</h2>";

if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "<p style='color: green;'>✓ PHPMailer is installed</p>";
} else {
    echo "<p style='color: orange;'>⚠ PHPMailer is NOT installed</p>";
    echo "<p>PHPMailer is recommended for SMTP email sending</p>";
    echo "<p>Install with: <code>composer require phpmailer/phpmailer</code></p>";
}

echo "<hr>";
echo "<h2>Test 3: SMTP Connection Test</h2>";

echo "<p>Testing connection to SMTP server...</p>";

$smtp_host = SMTP_HOST;
$smtp_port = SMTP_PORT;

$connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);

if ($connection) {
    echo "<p style='color: green;'>✓ Successfully connected to $smtp_host:$smtp_port</p>";
    
    // Read server response
    $response = fgets($connection, 515);
    echo "<p>Server response: <code>" . htmlspecialchars($response) . "</code></p>";
    
    fclose($connection);
} else {
    echo "<p style='color: red;'>✗ Failed to connect to $smtp_host:$smtp_port</p>";
    echo "<p>Error: $errstr ($errno)</p>";
    echo "<p><strong>Possible issues:</strong></p>";
    echo "<ul>";
    echo "<li>SMTP server is not reachable</li>";
    echo "<li>Port is blocked by firewall</li>";
    echo "<li>Incorrect SMTP host or port</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Recommendation</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
echo "<p><strong>For reliable SMTP email sending, we need to implement PHPMailer.</strong></p>";
echo "<p>The current EmailHandler uses PHP's mail() function which doesn't support SMTP authentication properly.</p>";
echo "<p>Would you like me to:</p>";
echo "<ol>";
echo "<li>Update EmailHandler to use PHPMailer (recommended)</li>";
echo "<li>Or provide alternative SMTP configuration</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<h2>Quick Fix Options</h2>";
echo "<p><strong>Option A:</strong> Install PHPMailer via Composer</p>";
echo "<pre>composer require phpmailer/phpmailer</pre>";

echo "<p><strong>Option B:</strong> Download PHPMailer manually</p>";
echo "<ol>";
echo "<li>Download from: <a href='https://github.com/PHPMailer/PHPMailer' target='_blank'>GitHub</a></li>";
echo "<li>Extract to: <code>includes/PHPMailer/</code></li>";
echo "<li>I'll update the EmailHandler to use it</li>";
echo "</ol>";

echo "<p><strong>Option C:</strong> Disable email notifications temporarily</p>";
echo "<p>Set in config/email.php: <code>define('ENABLE_EMAIL_NOTIFICATIONS', false);</code></p>";
?>
