<?php
/**
 * Add K&K Knowledge Base Articles
 * Run this script to populate the knowledge base with company-specific information
 */

require_once '../../config/config.php';
require_once '../../includes/db.php';

echo "Adding K&K Knowledge Base Articles...\n\n";

try {
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/008_add_kk_knowledge_base_articles.sql');
    
    // Execute SQL
    $pdo->exec($sql);
    
    echo "✓ Successfully added KB articles!\n\n";
    
    // Count articles
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM knowledge_base WHERE is_published = 1");
    $count = $stmt->fetch()['count'];
    
    echo "Total KB articles in database: $count\n\n";
    
    // Show added articles
    $stmt = $pdo->query("
        SELECT kb_id, title, created_at 
        FROM knowledge_base 
        WHERE DATE(created_at) = CURDATE()
        ORDER BY kb_id DESC
    ");
    
    echo "Newly added articles:\n";
    while ($row = $stmt->fetch()) {
        echo "  - [{$row['kb_id']}] {$row['title']}\n";
    }
    
    echo "\n✓ Done! Now run the sync script to add these to the vector database:\n";
    echo "  python ai_module/scripts/sync_tickets_to_vector_db.py\n\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
