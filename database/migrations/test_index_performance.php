<?php
/**
 * Performance Test for AI Indexes
 * 
 * This script tests query performance improvements after adding indexes
 * Task: 6. Create Database Indexes for Performance
 * Requirements: 6.3
 */

require_once __DIR__ . '/../../config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Performance Index Testing ===\n";
echo "Testing at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "✓ Database connection established\n\n";
    
    // ============================================
    // Test 1: Tickets updated_at query
    // ============================================
    echo "Test 1: Query tickets by updated_at (last 24 hours)\n";
    echo "Query: SELECT * FROM tickets WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)\n";
    
    $start = microtime(true);
    $result = $conn->query("
        SELECT ticket_id, ticket_number, title, updated_at 
        FROM tickets 
        WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
    
    $count = $result ? $result->num_rows : 0;
    echo "  Results: $count tickets\n";
    echo "  Duration: " . number_format($duration, 2) . " ms\n";
    
    // Check if index is being used
    $explain = $conn->query("
        EXPLAIN SELECT ticket_id, ticket_number, title, updated_at 
        FROM tickets 
        WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    
    if ($explain) {
        $explainRow = $explain->fetch_assoc();
        echo "  Index used: " . ($explainRow['key'] ?? 'NULL') . "\n";
        echo "  Rows examined: " . ($explainRow['rows'] ?? 'N/A') . "\n";
    }
    echo "\n";
    
    // ============================================
    // Test 2: Knowledge base published query
    // ============================================
    echo "Test 2: Query published KB articles ordered by updated_at\n";
    echo "Query: SELECT * FROM knowledge_base WHERE is_published = 1 ORDER BY updated_at DESC\n";
    
    $start = microtime(true);
    $result = $conn->query("
        SELECT kb_id, title, updated_at 
        FROM knowledge_base 
        WHERE is_published = 1 
        ORDER BY updated_at DESC
    ");
    $duration = (microtime(true) - $start) * 1000;
    
    $count = $result ? $result->num_rows : 0;
    echo "  Results: $count articles\n";
    echo "  Duration: " . number_format($duration, 2) . " ms\n";
    
    $explain = $conn->query("
        EXPLAIN SELECT kb_id, title, updated_at 
        FROM knowledge_base 
        WHERE is_published = 1 
        ORDER BY updated_at DESC
    ");
    
    if ($explain) {
        $explainRow = $explain->fetch_assoc();
        echo "  Index used: " . ($explainRow['key'] ?? 'NULL') . "\n";
        echo "  Rows examined: " . ($explainRow['rows'] ?? 'N/A') . "\n";
    }
    echo "\n";
    
    // ============================================
    // Test 3: Configuration items status query
    // ============================================
    echo "Test 3: Query active CI items ordered by updated_at\n";
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'configuration_items'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        echo "Query: SELECT * FROM configuration_items WHERE status != 'Afgeschreven' ORDER BY updated_at DESC\n";
        
        $start = microtime(true);
        $result = $conn->query("
            SELECT ci_id, ci_number, name, status, updated_at 
            FROM configuration_items 
            WHERE status != 'Afgeschreven' 
            ORDER BY updated_at DESC
        ");
        $duration = (microtime(true) - $start) * 1000;
        
        $count = $result ? $result->num_rows : 0;
        echo "  Results: $count CI items\n";
        echo "  Duration: " . number_format($duration, 2) . " ms\n";
        
        $explain = $conn->query("
            EXPLAIN SELECT ci_id, ci_number, name, status, updated_at 
            FROM configuration_items 
            WHERE status != 'Afgeschreven' 
            ORDER BY updated_at DESC
        ");
        
        if ($explain) {
            $explainRow = $explain->fetch_assoc();
            echo "  Index used: " . ($explainRow['key'] ?? 'NULL') . "\n";
            echo "  Rows examined: " . ($explainRow['rows'] ?? 'N/A') . "\n";
        }
    } else {
        echo "  ⚠ Table 'configuration_items' does not exist, skipping test\n";
    }
    echo "\n";
    
    // ============================================
    // Test 4: Ticket field values query
    // ============================================
    echo "Test 4: Query ticket field values with JOIN\n";
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'ticket_field_values'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        echo "Query: SELECT * FROM ticket_field_values JOIN with recent tickets\n";
        
        $start = microtime(true);
        $result = $conn->query("
            SELECT tfv.ticket_id, tfv.field_id, tfv.field_value, cf.field_label
            FROM ticket_field_values tfv
            JOIN category_fields cf ON tfv.field_id = cf.field_id
            JOIN (
                SELECT ticket_id FROM tickets ORDER BY updated_at DESC LIMIT 10
            ) t ON tfv.ticket_id = t.ticket_id
        ");
        $duration = (microtime(true) - $start) * 1000;
        
        $count = $result ? $result->num_rows : 0;
        echo "  Results: $count field values\n";
        echo "  Duration: " . number_format($duration, 2) . " ms\n";
        
        $explain = $conn->query("
            EXPLAIN SELECT tfv.ticket_id, tfv.field_id, tfv.field_value, cf.field_label
            FROM ticket_field_values tfv
            JOIN category_fields cf ON tfv.field_id = cf.field_id
            JOIN (
                SELECT ticket_id FROM tickets ORDER BY updated_at DESC LIMIT 10
            ) t ON tfv.ticket_id = t.ticket_id
        ");
        
        if ($explain) {
            $explainRow = $explain->fetch_assoc();
            echo "  Index used: " . ($explainRow['key'] ?? 'NULL') . "\n";
            echo "  Rows examined: " . ($explainRow['rows'] ?? 'N/A') . "\n";
        }
    } else {
        echo "  ⚠ Table 'ticket_field_values' does not exist, skipping test\n";
    }
    echo "\n";
    
    // ============================================
    // Test 5: Complex sync query simulation
    // ============================================
    echo "Test 5: Simulate full sync query (tickets with dynamic fields)\n";
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'ticket_field_values'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        echo "Query: Complex JOIN with tickets, users, and dynamic fields\n";
        
        $start = microtime(true);
        $result = $conn->query("
            SELECT 
                t.ticket_id,
                t.ticket_number,
                t.title,
                t.description,
                t.category_id,
                t.priority,
                t.status,
                t.updated_at,
                u.first_name,
                u.last_name,
                (SELECT COUNT(*) 
                 FROM ticket_field_values tfv 
                 WHERE tfv.ticket_id = t.ticket_id) as field_count
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.user_id
            WHERE t.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY t.updated_at DESC
            LIMIT 50
        ");
        $duration = (microtime(true) - $start) * 1000;
        
        $count = $result ? $result->num_rows : 0;
        echo "  Results: $count tickets\n";
        echo "  Duration: " . number_format($duration, 2) . " ms\n";
    } else {
        echo "  ⚠ Skipping complex query test (tables not available)\n";
    }
    echo "\n";
    
    // ============================================
    // Index Summary
    // ============================================
    echo "=== Index Summary ===\n";
    echo "Checking all AI-related indexes...\n\n";
    
    $indexes = $conn->query("
        SELECT 
            TABLE_NAME,
            INDEX_NAME,
            GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as COLUMNS,
            NON_UNIQUE,
            INDEX_TYPE
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND INDEX_NAME IN ('idx_updated_at', 'idx_published_updated', 'idx_status_updated', 'idx_ticket_field')
        GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE
        ORDER BY TABLE_NAME, INDEX_NAME
    ");
    
    if ($indexes && $indexes->num_rows > 0) {
        while ($row = $indexes->fetch_assoc()) {
            echo sprintf("✓ %s.%s\n", $row['TABLE_NAME'], $row['INDEX_NAME']);
            echo sprintf("  Columns: %s\n", $row['COLUMNS']);
            echo sprintf("  Type: %s, Unique: %s\n\n", 
                $row['INDEX_TYPE'], 
                $row['NON_UNIQUE'] == 0 ? 'Yes' : 'No'
            );
        }
    } else {
        echo "⚠ No AI indexes found\n";
    }
    
    $conn->close();
    
    echo "=== Performance Testing Complete ===\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed: " . $e->getMessage() . "\n";
    exit(1);
}
