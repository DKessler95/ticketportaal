<?php
/**
 * Quick database connection test
 */

require_once 'config/database.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
    
    echo "✓ Database connection successful!\n\n";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Check for sample data
    echo "\nSample data check:\n";
    
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "  - Users: $userCount\n";
    
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    echo "  - Categories: $categoryCount\n";
    
    $ticketCount = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    echo "  - Tickets: $ticketCount\n";
    
    echo "\n✓ Database setup complete!\n";
    
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
