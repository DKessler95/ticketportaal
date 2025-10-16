<?php
/**
 * Email Diagnostics
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(60);

require_once 'config/email.php';

echo "<h1>Email Diagnostics</h1>";
echo "<hr>";

echo "<h2>1. Configuration Check</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>SMTP_HOST</td><td>" . SMTP_HOST . "</td></tr>";
echo "<tr><td>SMTP_PORT</td><td>" . SMTP_PORT . "</td></tr>";
echo "<tr><td>SMTP_USER</td><td>" . SMTP_USER . "</td></tr>";
echo "<tr><td>SMTP_PASS</td><td>" . (SMTP_PASS ? str_repeat('*', strlen(SMTP_PASS)) : 'NOT SET') . "</td></tr>";
echo "<tr><td>SMTP_SECURE</td><td>" . SMTP_SECURE . "</td></tr>";
echo "</table>";

echo "<hr>";
echo "<h2>2. Network Connectivity Tests</h2>";

// Test different ports
$ports = [
    25 => 'SMTP (unencrypted)',
    587 => 'SMTP with STARTTLS',
    465 => 'SMTP with SSL',
    993 => 'IMAP with SSL'
];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Port</th><th>Description</th><th>Status</th></tr>";

foreach ($ports as $port => $description) {
    echo "<tr>";
    echo "<td>$port</td>";
    echo "<td>$description</td>";
    
    $connection = @fsockopen(SMTP_HOST, $port, $errno, $errstr, 5);
    
    if ($connection) {
        echo "<td style='color: green;'>✓ Open</td>";
        fclose($connection);
    } else {
        echo "<td style='color: red;'>✗ Blocked ($errstr)</td>";
    }
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>3. DNS Resolution</h2>";

$ip = gethostbyname(SMTP_HOST);
if ($ip === SMTP_HOST) {
    echo "<p style='color: red;'>✗ Cannot resolve hostname: " . SMTP_HOST . "</p>";
    echo "<p><strong>Possible issues:</strong></p>";
    echo "<ul>";
    echo "<li>DNS server not configured</li>";
    echo "<li>Not connected to company network</li>";
    echo "<li>Hostname is incorrect</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✓ Hostname resolves to: <strong>$ip</strong></p>";
}

echo "<hr>";
echo "<h2>4. Ping Test</h2>";

$output = [];
$return = 0;
exec("ping -n 2 " . SMTP_HOST . " 2>&1", $output, $return);

echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";

if ($return === 0) {
    echo "<p style='color: green;'>✓ Server is reachable</p>";
} else {
    echo "<p style='color: red;'>✗ Server is not reachable</p>";
}

echo "<hr>";
echo "<h2>5. Firewall Check</h2>";

echo "<p><strong>Windows Firewall Status:</strong></p>";
$output = [];
exec("netsh advfirewall show allprofiles state 2>&1", $output);
echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";

echo "<hr>";
echo "<h2>6. Alternative SMTP Settings to Try</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
echo "<h3>Option A: Try Port 25 (No Encryption)</h3>";
echo "<pre>";
echo "define('SMTP_PORT', 25);\n";
echo "define('SMTP_SECURE', ''); // No encryption\n";
echo "</pre>";

echo "<h3>Option B: Try Port 465 (SSL)</h3>";
echo "<pre>";
echo "define('SMTP_PORT', 465);\n";
echo "define('SMTP_SECURE', 'ssl');\n";
echo "</pre>";

echo "<h3>Option C: Try without Authentication</h3>";
echo "<pre>";
echo "define('SMTP_AUTH', false);\n";
echo "</pre>";

echo "<h3>Option D: Use Internal Mail Server</h3>";
echo "<p>If you have an internal mail relay server:</p>";
echo "<pre>";
echo "define('SMTP_HOST', 'localhost'); // or internal mail server IP\n";
echo "define('SMTP_PORT', 25);\n";
echo "define('SMTP_AUTH', false);\n";
echo "define('SMTP_SECURE', '');\n";
echo "</pre>";
echo "</div>";

echo "<hr>";
echo "<h2>7. Collax Specific Settings</h2>";

echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #0066cc; border-radius: 5px;'>";
echo "<p><strong>For Collax Email Server, try these settings:</strong></p>";

echo "<h4>SMTP (Sending):</h4>";
echo "<pre>";
echo "define('SMTP_HOST', 'mail.kruit-en-kramer.nl');\n";
echo "define('SMTP_PORT', 587); // or 25\n";
echo "define('SMTP_SECURE', 'tls'); // or empty for port 25\n";
echo "define('SMTP_AUTH', true);\n";
echo "</pre>";

echo "<h4>Check with IT Department:</h4>";
echo "<ul>";
echo "<li>What is the correct SMTP server hostname?</li>";
echo "<li>Which port should be used? (25, 587, or 465)</li>";
echo "<li>Is SMTP authentication required?</li>";
echo "<li>Is there a firewall blocking SMTP ports?</li>";
echo "<li>Is there an internal mail relay server?</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<h2>Recommendation</h2>";

if ($ip === SMTP_HOST) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #dc3545; border-radius: 5px;'>";
    echo "<h3>✗ Cannot Reach Mail Server</h3>";
    echo "<p><strong>The mail server hostname cannot be resolved.</strong></p>";
    echo "<p>Please check with your IT department:</p>";
    echo "<ol>";
    echo "<li>Confirm the correct mail server hostname</li>";
    echo "<li>Ensure you're connected to the company network</li>";
    echo "<li>Check if VPN is required</li>";
    echo "</ol>";
    echo "</div>";
} else {
    $anyPortOpen = false;
    foreach ($ports as $port => $desc) {
        $conn = @fsockopen(SMTP_HOST, $port, $errno, $errstr, 2);
        if ($conn) {
            $anyPortOpen = true;
            fclose($conn);
            break;
        }
    }
    
    if (!$anyPortOpen) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #dc3545; border-radius: 5px;'>";
        echo "<h3>✗ All SMTP Ports Blocked</h3>";
        echo "<p><strong>The server is reachable but all SMTP ports are blocked.</strong></p>";
        echo "<p>This is likely a firewall issue. Contact your IT department to:</p>";
        echo "<ol>";
        echo "<li>Open SMTP ports (25, 587, or 465) in the firewall</li>";
        echo "<li>Or provide an internal mail relay server</li>";
        echo "<li>Or whitelist your IP address</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #28a745; border-radius: 5px;'>";
        echo "<h3>✓ Server is Reachable</h3>";
        echo "<p>Some ports are open. Try adjusting the SMTP settings above.</p>";
        echo "</div>";
    }
}
?>
