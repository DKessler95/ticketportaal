<?php
/**
 * Test Email with PHPMailer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/email.php';

echo "<h1>PHPMailer Email Test</h1>";
echo "<hr>";

// Check if PHPMailer is installed
if (!file_exists('includes/PHPMailer/src/PHPMailer.php')) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; border-radius: 5px;'>";
    echo "<h2>✗ PHPMailer Not Installed</h2>";
    echo "<p>Please run <a href='download_phpmailer.php'>download_phpmailer.php</a> first</p>";
    echo "</div>";
    exit;
}

echo "<p style='color: green;'>✓ PHPMailer is installed</p>";
echo "<hr>";

// Load EmailHandler with PHPMailer
require_once 'classes/EmailHandler_PHPMailer.php';

echo "<h2>Test Email Sending</h2>";

$emailHandler = new EmailHandler();

// Test connection first
echo "<p>Testing SMTP connection...</p>";
if ($emailHandler->testConnection()) {
    echo "<p style='color: green;'>✓ SMTP connection successful!</p>";
} else {
    echo "<p style='color: red;'>✗ SMTP connection failed: " . $emailHandler->getError() . "</p>";
    echo "<p><strong>Note:</strong> This is expected if you're not on the company network</p>";
}

echo "<hr>";
echo "<h2>Send Test Email</h2>";

// You can change this to your email for testing
$testEmail = SMTP_USER; // Send to yourself for testing

echo "<p>Sending test email to: <strong>$testEmail</strong></p>";

$subject = "Test Email from ICT Ticketportaal";
$body = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { padding: 20px; background: #f5f5f5; }
        .content { background: white; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='content'>
            <h2>Test Email</h2>
            <p>This is a test email from the ICT Ticketportaal system.</p>
            <p>If you receive this email, the email system is working correctly!</p>
            <hr>
            <p><small>Sent at: " . date('Y-m-d H:i:s') . "</small></p>
        </div>
    </div>
</body>
</html>
";

$altBody = "Test Email\n\nThis is a test email from the ICT Ticketportaal system.\n\nIf you receive this email, the email system is working correctly!";

$result = $emailHandler->sendEmail($testEmail, $subject, $body, $altBody);

if ($result) {
    echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 5px;'>";
    echo "<h3 style='color: #155724;'>✓ Email Sent Successfully!</h3>";
    echo "<p>Check your inbox at: <strong>$testEmail</strong></p>";
    echo "<p>If you don't see it, check your spam folder.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>✗ Failed to Send Email</h3>";
    echo "<p>Error: " . htmlspecialchars($emailHandler->getError()) . "</p>";
    echo "<p><strong>Common Issues:</strong></p>";
    echo "<ul>";
    echo "<li>Not connected to company network (VPN required?)</li>";
    echo "<li>SMTP server blocks external connections</li>";
    echo "<li>Incorrect SMTP credentials</li>";
    echo "<li>Firewall blocking port " . SMTP_PORT . "</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<p>Once email sending works:</p>";
echo "<ol>";
echo "<li>The system will automatically send notifications for new tickets</li>";
echo "<li>Users will receive confirmation emails</li>";
echo "<li>Agents will be notified of assignments</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> Email will only work when you're on the company network or connected via VPN.</p>";
?>
