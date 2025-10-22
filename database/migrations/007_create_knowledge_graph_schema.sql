-- Knowledge Graph Schema Migration
-- Creates tables for storing entity nodes and relationship edges
-- Part of RAG AI Local Implementation

-- ============================================================================
-- Graph Nodes Table
-- ============================================================================
-- Stores entities extracted from tickets, KB articles, CI items, etc.
-- Each node represents a distinct entity in the knowledge graph

CREATE TABLE IF NOT EXISTS graph_nodes (
    node_id VARCHAR(255) PRIMARY KEY COMMENT 'Unique identifier (e.g., ticket_123, user_45, ci_789)',
    node_type VARCHAR(50) NOT NULL COMMENT 'Entity type: ticket, user, ci, kb, category, solution, department, location',
    properties JSON NOT NULL COMMENT 'Flexible storage for entity-specific attributes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When this node was first created',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    INDEX idx_node_type (node_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Knowledge graph nodes - entities extracted from tickets and related data';

-- ============================================================================
-- Graph Edges Table
-- ============================================================================
-- Stores relationships between entities
-- Edges are directed (source -> target) with confidence scores

CREATE TABLE IF NOT EXISTS graph_edges (
    edge_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Auto-incrementing edge identifier',
    source_id VARCHAR(255) NOT NULL COMMENT 'Source node ID',
    target_id VARCHAR(255) NOT NULL COMMENT 'Target node ID',
    edge_type VARCHAR(50) NOT NULL COMMENT 'Relationship type: CREATED_BY, AFFECTS, SIMILAR_TO, RESOLVED_BY, BELONGS_TO, etc.',
    confidence DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Confidence score 0.00-1.00 for relationship strength',
    properties JSON DEFAULT NULL COMMENT 'Additional edge metadata (e.g., similarity score, extraction method)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When this edge was created',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    -- Foreign key constraints
    FOREIGN KEY (source_id) REFERENCES graph_nodes(node_id) ON DELETE CASCADE,
    FOREIGN KEY (target_id) REFERENCES graph_nodes(node_id) ON DELETE CASCADE,
    
    -- Indexes for fast graph traversal
    INDEX idx_source_id (source_id) COMMENT 'Fast lookup of outgoing edges',
    INDEX idx_target_id (target_id) COMMENT 'Fast lookup of incoming edges',
    INDEX idx_edge_type (edge_type) COMMENT 'Filter by relationship type',
    INDEX idx_confidence (confidence) COMMENT 'Filter by confidence threshold',
    INDEX idx_source_type (source_id, edge_type) COMMENT 'Composite index for typed traversal',
    INDEX idx_target_type (target_id, edge_type) COMMENT 'Composite index for reverse typed traversal',
    
    -- Prevent duplicate edges
    UNIQUE KEY unique_edge (source_id, target_id, edge_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Knowledge graph edges - relationships between entities with confidence scores';

-- ============================================================================
-- Example Data Structure
-- ============================================================================
-- 
-- Node Examples:
-- {
--   "node_id": "ticket_123",
--   "node_type": "ticket",
--   "properties": {
--     "ticket_number": "T-2024-001",
--     "title": "Laptop start niet op",
--     "category": "Hardware",
--     "status": "Closed"
--   }
-- }
--
-- {
--   "node_id": "user_45",
--   "node_type": "user",
--   "properties": {
--     "name": "Jan Jansen",
--     "department": "Sales",
--     "location": "Kantoor Hengelo"
--   }
-- }
--
-- Edge Examples:
-- {
--   "source_id": "ticket_123",
--   "target_id": "user_45",
--   "edge_type": "CREATED_BY",
--   "confidence": 1.00,
--   "properties": {"extraction_method": "direct_db_relation"}
-- }
--
-- {
--   "source_id": "ticket_123",
--   "target_id": "ticket_456",
--   "edge_type": "SIMILAR_TO",
--   "confidence": 0.87,
--   "properties": {"similarity_score": 0.87, "method": "vector_similarity"}
-- }
-- ============================================================================

-- Verification queries
SELECT 'Knowledge graph schema created successfully' AS status;
SELECT COUNT(*) AS node_count FROM graph_nodes;
SELECT COUNT(*) AS edge_count FROM graph_edges;
