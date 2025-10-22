<?php
/**
 * Performance Indexes Migration Runner
 * 
 * This script adds database indexes to optimize sync queries for the RAG AI system
 * Task: 6. Create Database Indexes for Performance
 * Requirements: 6.3
 */

require_once __DIR__ . '/../../config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Performance Indexes Migration ===\n";
echo "Starting migration at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "✓ Database connection established\n\n";
    
    // Track migration results
    $results = [
        'success' => [],
        'skipped' => [],
        'failed' => []
    ];
    
    // ============================================
    // 1. Add index on tickets(updated_at)
    // ============================================
    echo "1. Adding index on tickets(updated_at)...\n";
    
    // Check if index already exists
    $check = $conn->query("
        SELECT COUNT(*) as count 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'tickets' 
          AND INDEX_NAME = 'idx_updated_at'
    ");
    
    if ($check && $check->fetch_assoc()['count'] > 0) {
        echo "   ⚠ Index already exists, skipping\n";
        $results['skipped'][] = 'tickets.idx_updated_at';
    } else {
        if ($conn->query("ALTER TABLE tickets ADD INDEX idx_updated_at (updated_at)")) {
            echo "   ✓ Index created successfully\n";
            $results['success'][] = 'tickets.idx_updated_at';
        } else {
            echo "   ✗ Failed: " . $conn->error . "\n";
            $results['failed'][] = 'tickets.idx_updated_at';
        }
    }
    echo "\n";
    
    // ============================================
    // 2. Add index on knowledge_base(is_published, updated_at)
    // ============================================
    echo "2. Adding index on knowledge_base(is_published, updated_at)...\n";
    
    $check = $conn->query("
        SELECT COUNT(*) as count 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'knowledge_base' 
          AND INDEX_NAME = 'idx_published_updated'
    ");
    
    if ($check && $check->fetch_assoc()['count'] > 0) {
        echo "   ⚠ Index already exists, skipping\n";
        $results['skipped'][] = 'knowledge_base.idx_published_updated';
    } else {
        if ($conn->query("ALTER TABLE knowledge_base ADD INDEX idx_published_updated (is_published, updated_at)")) {
            echo "   ✓ Index created successfully\n";
            $results['success'][] = 'knowledge_base.idx_published_updated';
        } else {
            echo "   ✗ Failed: " . $conn->error . "\n";
            $results['failed'][] = 'knowledge_base.idx_published_updated';
        }
    }
    echo "\n";
    
    // ============================================
    // 3. Add index on configuration_items(status, updated_at)
    // ============================================
    echo "3. Adding index on configuration_items(status, updated_at)...\n";
    
    // Check if table exists first
    $tableCheck = $conn->query("SHOW TABLES LIKE 'configuration_items'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $check = $conn->query("
            SELECT COUNT(*) as count 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'configuration_items' 
              AND INDEX_NAME = 'idx_status_updated'
        ");
        
        if ($check && $check->fetch_assoc()['count'] > 0) {
            echo "   ⚠ Index already exists, skipping\n";
            $results['skipped'][] = 'configuration_items.idx_status_updated';
        } else {
            if ($conn->query("ALTER TABLE configuration_items ADD INDEX idx_status_updated (status, updated_at)")) {
                echo "   ✓ Index created successfully\n";
                $results['success'][] = 'configuration_items.idx_status_updated';
            } else {
                echo "   ✗ Failed: " . $conn->error . "\n";
                $results['failed'][] = 'configuration_items.idx_status_updated';
            }
        }
    } else {
        echo "   ⚠ Table 'configuration_items' does not exist, skipping\n";
        $results['skipped'][] = 'configuration_items.idx_status_updated (table not found)';
    }
    echo "\n";
    
    // ============================================
    // 4. Add index on ticket_field_values(ticket_id, field_id)
    // ============================================
    echo "4. Adding index on ticket_field_values(ticket_id, field_id)...\n";
    
    // Check if table exists first
    $tableCheck = $conn->query("SHOW TABLES LIKE 'ticket_field_values'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $check = $conn->query("
            SELECT COUNT(*) as count 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'ticket_field_values' 
              AND INDEX_NAME = 'idx_ticket_field'
        ");
        
        if ($check && $check->fetch_assoc()['count'] > 0) {
            echo "   ⚠ Index already exists, skipping\n";
            $results['skipped'][] = 'ticket_field_values.idx_ticket_field';
        } else {
            // Note: unique_ticket_field already exists, but we add a non-unique version for better performance
            if ($conn->query("ALTER TABLE ticket_field_values ADD INDEX idx_ticket_field (ticket_id, field_id)")) {
                echo "   ✓ Index created successfully\n";
                $results['success'][] = 'ticket_field_values.idx_ticket_field';
            } else {
                echo "   ✗ Failed: " . $conn->error . "\n";
                $results['failed'][] = 'ticket_field_values.idx_ticket_field';
            }
        }
    } else {
        echo "   ⚠ Table 'ticket_field_values' does not exist, skipping\n";
        $results['skipped'][] = 'ticket_field_values.idx_ticket_field (table not found)';
    }
    echo "\n";
    
    // ============================================
    // VERIFICATION
    // ============================================
    echo "=== Verification ===\n";
    echo "Checking created indexes...\n\n";
    
    $verify = $conn->query("
        SELECT 
            TABLE_NAME,
            INDEX_NAME,
            GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as COLUMNS,
            INDEX_TYPE
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN ('tickets', 'knowledge_base', 'configuration_items', 'ticket_field_values')
          AND INDEX_NAME IN ('idx_updated_at', 'idx_published_updated', 'idx_status_updated', 'idx_ticket_field')
        GROUP BY TABLE_NAME, INDEX_NAME, INDEX_TYPE
        ORDER BY TABLE_NAME, INDEX_NAME
    ");
    
    if ($verify && $verify->num_rows > 0) {
        echo "Indexes found:\n";
        while ($row = $verify->fetch_assoc()) {
            echo sprintf("  ✓ %s.%s (%s) - Type: %s\n", 
                $row['TABLE_NAME'], 
                $row['INDEX_NAME'], 
                $row['COLUMNS'],
                $row['INDEX_TYPE']
            );
        }
    } else {
        echo "  ⚠ No indexes found (this may be normal if tables don't exist yet)\n";
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "Success: " . count($results['success']) . "\n";
    echo "Skipped: " . count($results['skipped']) . "\n";
    echo "Failed: " . count($results['failed']) . "\n";
    
    if (count($results['failed']) > 0) {
        echo "\nFailed indexes:\n";
        foreach ($results['failed'] as $failed) {
            echo "  - $failed\n";
        }
    }
    
    $conn->close();
    
    echo "\n✓ Migration completed!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
