-- Performance Indexes for AI RAG System
-- Created: 2025-10-22
-- Description: Add indexes to optimize sync queries for the RAG AI system
-- Task: 6. Create Database Indexes for Performance
-- Requirements: 6.3

-- NOTE: Run this script using the PHP migration runner (run_performance_indexes_migration.php)
-- which handles checking for existing indexes before creating them.
-- Direct execution in phpMyAdmin may fail if indexes already exist.

-- ============================================
-- TICKETS TABLE INDEXES
-- ============================================

-- Index for sync queries that filter by updated_at
-- Used by: Sync Pipeline to fetch recently updated tickets
-- DROP INDEX IF EXISTS idx_updated_at ON tickets;
ALTER TABLE tickets ADD INDEX idx_updated_at (updated_at);

-- ============================================
-- KNOWLEDGE_BASE TABLE INDEXES
-- ============================================

-- Composite index for published KB articles ordered by update time
-- Used by: Sync Pipeline to fetch published KB articles
-- DROP INDEX IF EXISTS idx_published_updated ON knowledge_base;
ALTER TABLE knowledge_base ADD INDEX idx_published_updated (is_published, updated_at);

-- ============================================
-- CONFIGURATION_ITEMS TABLE INDEXES
-- ============================================

-- Composite index for active CI items ordered by update time
-- Used by: Sync Pipeline to fetch active CI items (excluding 'Afgeschreven')
-- DROP INDEX IF EXISTS idx_status_updated ON configuration_items;
ALTER TABLE configuration_items ADD INDEX idx_status_updated (status, updated_at);

-- ============================================
-- TICKET_FIELD_VALUES TABLE INDEXES
-- ============================================

-- Composite index for efficient ticket field value lookups
-- Used by: Sync Pipeline to fetch dynamic field values for tickets
-- Note: unique_ticket_field already exists, this adds a non-unique version for better performance
-- DROP INDEX IF EXISTS idx_ticket_field ON ticket_field_values;
ALTER TABLE ticket_field_values ADD INDEX idx_ticket_field (ticket_id, field_id);
