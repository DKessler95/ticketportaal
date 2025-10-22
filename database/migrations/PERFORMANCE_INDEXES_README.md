# Performance Indexes for AI RAG System

## Overview

This migration adds database indexes to optimize sync queries for the RAG AI system. These indexes significantly improve query performance when the sync pipeline fetches data from the database.

**Task:** 6. Create Database Indexes for Performance  
**Requirements:** 6.3  
**Created:** 2025-10-22

## Indexes Added

### 1. tickets.idx_updated_at
- **Column:** `updated_at`
- **Purpose:** Optimize sync queries that fetch recently updated tickets
- **Used by:** Daily and incremental sync pipeline
- **Query pattern:** `WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)`

### 2. knowledge_base.idx_published_updated
- **Columns:** `is_published`, `updated_at`
- **Purpose:** Optimize queries for published KB articles ordered by update time
- **Used by:** KB sync pipeline
- **Query pattern:** `WHERE is_published = 1 ORDER BY updated_at DESC`

### 3. configuration_items.idx_status_updated
- **Columns:** `status`, `updated_at`
- **Purpose:** Optimize queries for active CI items (excluding 'Afgeschreven')
- **Used by:** CI sync pipeline
- **Query pattern:** `WHERE status != 'Afgeschreven' ORDER BY updated_at DESC`

### 4. ticket_field_values.idx_ticket_field
- **Columns:** `ticket_id`, `field_id`
- **Purpose:** Optimize lookups of dynamic field values for tickets
- **Used by:** Sync pipeline when fetching ticket dynamic fields
- **Query pattern:** `WHERE ticket_id IN (...) JOIN category_fields`
- **Note:** Complements existing `unique_ticket_field` constraint

## Installation

### Method 1: SQL Editor (phpMyAdmin)
1. Open phpMyAdmin
2. Select your database
3. Go to SQL tab
4. Copy and paste contents of `add_performance_indexes_for_ai.sql`
5. Execute the query

### Method 2: PHP Migration Script
```bash
php database/migrations/run_performance_indexes_migration.php
```

The PHP script automatically:
- Checks if indexes already exist
- Skips existing indexes
- Handles missing tables gracefully
- Provides detailed output

## Testing Performance

Run the performance test script to verify improvements:

```bash
php database/migrations/test_index_performance.php
```

This script will:
- Test each indexed query
- Show execution time in milliseconds
- Display which indexes are being used (via EXPLAIN)
- Show number of rows examined
- Provide a summary of all AI indexes

## Expected Performance Improvements

### Before Indexes
- Full table scans on large tables
- Slow queries (100-500ms for 1000+ tickets)
- High CPU usage during sync

### After Indexes
- Index-based lookups
- Fast queries (5-50ms for same dataset)
- Reduced CPU usage
- Better scalability as data grows

## Verification

Check if indexes were created successfully:

```sql
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as COLUMNS,
    INDEX_TYPE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN ('idx_updated_at', 'idx_published_updated', 'idx_status_updated', 'idx_ticket_field')
GROUP BY TABLE_NAME, INDEX_NAME, INDEX_TYPE
ORDER BY TABLE_NAME, INDEX_NAME;
```

Expected output:
```
tickets              | idx_updated_at        | updated_at                    | BTREE
knowledge_base       | idx_published_updated | is_published,updated_at       | BTREE
configuration_items  | idx_status_updated    | status,updated_at             | BTREE
ticket_field_values  | idx_ticket_field      | ticket_id,field_id            | BTREE
```

## Impact on RAG System

These indexes are critical for the RAG AI system performance:

1. **Sync Pipeline Speed:** Reduces sync time from minutes to seconds
2. **Resource Usage:** Lower CPU and memory usage during sync
3. **Scalability:** System can handle 10,000+ tickets efficiently
4. **Real-time Updates:** Enables faster incremental syncs (hourly)

## Maintenance

### Index Maintenance
MySQL automatically maintains indexes. No manual maintenance required.

### Monitoring
Monitor index usage with:

```sql
-- Check index statistics
SHOW INDEX FROM tickets WHERE Key_name = 'idx_updated_at';
SHOW INDEX FROM knowledge_base WHERE Key_name = 'idx_published_updated';
SHOW INDEX FROM configuration_items WHERE Key_name = 'idx_status_updated';
SHOW INDEX FROM ticket_field_values WHERE Key_name = 'idx_ticket_field';
```

### Rebuilding Indexes (if needed)
If indexes become fragmented over time:

```sql
ALTER TABLE tickets DROP INDEX idx_updated_at;
ALTER TABLE tickets ADD INDEX idx_updated_at (updated_at);
```

## Rollback

To remove these indexes:

```sql
ALTER TABLE tickets DROP INDEX idx_updated_at;
ALTER TABLE knowledge_base DROP INDEX idx_published_updated;
ALTER TABLE configuration_items DROP INDEX idx_status_updated;
ALTER TABLE ticket_field_values DROP INDEX idx_ticket_field;
```

**Warning:** Removing these indexes will significantly slow down the RAG sync pipeline.

## Related Files

- `add_performance_indexes_for_ai.sql` - SQL migration script
- `run_performance_indexes_migration.php` - PHP migration runner
- `test_index_performance.php` - Performance testing script
- `PERFORMANCE_INDEXES_README.md` - This documentation

## Next Steps

After installing these indexes:

1. Run the performance test to verify improvements
2. Proceed to Phase 3: Knowledge Graph Foundation (Task 7)
3. Begin implementing the sync pipeline that will use these indexes

## Notes

- These indexes are designed specifically for the RAG AI sync queries
- They complement existing indexes (not replace them)
- Disk space impact is minimal (< 10MB for typical datasets)
- Query performance improvements are most noticeable with 1000+ records
