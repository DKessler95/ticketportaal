<?php
/**
 * Fix Template Logo URLs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Fix Template Logo URLs</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    // Update all templates to use the correct URL
    $oldUrl = 'http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg';
    $newUrl = 'http://localhost:8080/ticketportaal/assets/images/logo/Kruit/logo.svg';
    
    echo "<p>Updating template logo URLs...</p>";
    echo "<p>From: <code>$oldUrl</code></p>";
    echo "<p>To: <code>$newUrl</code></p>";
    
    $stmt = $pdo->prepare("
        UPDATE ticket_templates 
        SET content = REPLACE(content, ?, ?)
        WHERE content LIKE ?
    ");
    
    $result = $stmt->execute([$oldUrl, $newUrl, '%' . $oldUrl . '%']);
    
    if ($result) {
        $count = $stmt->rowCount();
        echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Updated $count template(s)!</p>";
        
        // Show which templates were updated
        $stmt = $pdo->query("SELECT template_id, name, template_type FROM ticket_templates WHERE content LIKE '%$newUrl%'");
        $templates = $stmt->fetchAll();
        
        if (count($templates) > 0) {
            echo "<h3>Updated Templates:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Name</th><th>Type</th></tr>";
            foreach ($templates as $template) {
                echo "<tr>";
                echo "<td>{$template['template_id']}</td>";
                echo "<td>{$template['name']}</td>";
                echo "<td>{$template['template_type']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to update templates</p>";
    }
    
    echo "<br><br>";
    echo "<p><a href='admin/templates.php'>View Templates</a> | <a href='login.php'>Go to Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
