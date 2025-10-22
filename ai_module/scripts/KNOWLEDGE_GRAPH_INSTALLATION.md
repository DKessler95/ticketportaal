# Knowledge Graph Installation Guide

## Quick Start

Follow these steps to set up the Knowledge Graph component of the RAG AI system.

## Prerequisites

- MySQL database (ticketportaal) already set up
- Python 3.11+ installed
- Virtual environment activated (if using one)

## Step 1: Install Python Dependencies

```bash
# Navigate to ai_module directory
cd ai_module

# Activate virtual environment (if using one)
# Windows:
venv\Scripts\activate

# Install required packages
pip install networkx mysql-connector-python
```

## Step 2: Create Database Schema

### Option A: Using MySQL Command Line

```bash
# Connect to MySQL
mysql -u root -p ticketportaal

# Run the migration script
source database/migrations/007_create_knowledge_graph_schema.sql

# Verify tables were created
SHOW TABLES LIKE 'graph_%';
```

### Option B: Using Python Test Script (Recommended)

```bash
# Navigate to scripts directory
cd ai_module/scripts

# Update database credentials in test_knowledge_graph.py
# Edit the get_db_config() function with your MySQL credentials
# Default: host='localhost', user='root', password='', database='ticketportaal'

# Run the test script (it will create the schema automatically)
python test_knowledge_graph.py
```

**Note**: The test script will automatically create the schema, add test data, and verify all functionality.

## Step 3: Verify Installation

Run the test script to verify everything is working:

```bash
cd ai_module/scripts
python test_knowledge_graph.py
```

Expected output:
```
======================================================================
KNOWLEDGE GRAPH TEST SUITE
======================================================================
STEP 1: Creating Knowledge Graph Schema
✅ Schema created successfully
✅ Found 2 graph tables: ['graph_nodes', 'graph_edges']

STEP 2: Adding Test Nodes
✅ Added ticket_1
✅ Added ticket_2
...

STEP 5: Graph Statistics
📈 Total Nodes: 8
📈 Total Edges: 8
...

✅ ALL TESTS PASSED!
```

## Step 4: Verify Database Tables

Check that the tables were created correctly:

```sql
-- Check graph_nodes table
SELECT COUNT(*) as node_count FROM graph_nodes;

-- Check graph_edges table  
SELECT COUNT(*) as edge_count FROM graph_edges;

-- View sample data
SELECT * FROM graph_nodes LIMIT 5;
SELECT * FROM graph_edges LIMIT 5;

-- Check indexes
SHOW INDEX FROM graph_edges;
```

## Step 5: Integration with RAG Pipeline

The knowledge graph is now ready to be integrated with:

1. **Sync Pipeline** (`sync_tickets_to_vector_db.py`)
   - Extract entities from tickets
   - Build relationships
   - Populate graph during sync

2. **RAG API** (`rag_api.py`)
   - Use graph traversal for enhanced queries
   - Include relationship chains in responses
   - Compute centrality for reranking

See `KNOWLEDGE_GRAPH_README.md` for detailed API usage examples.

## Troubleshooting

### Error: "Table 'graph_nodes' doesn't exist"

**Solution**: Run the migration script again:
```bash
mysql -u root -p ticketportaal < database/migrations/007_create_knowledge_graph_schema.sql
```

### Error: "Access denied for user"

**Solution**: Update database credentials in your script:
```python
db_config = {
    'host': 'localhost',
    'user': 'your_username',
    'password': 'your_password',
    'database': 'ticketportaal'
}
```

### Error: "No module named 'networkx'"

**Solution**: Install NetworkX:
```bash
pip install networkx
```

### Error: "Foreign key constraint fails"

**Solution**: This happens if you try to add an edge before adding nodes. Always add nodes first:
```python
# Correct order:
kg.add_node('ticket_1', 'ticket', {...})
kg.add_node('user_1', 'user', {...})
kg.add_edge('ticket_1', 'user_1', 'CREATED_BY')  # Now this works
```

## Performance Tuning

### For Large Graphs (>10K nodes)

1. **Load with filters**:
```python
# Only load specific node types
kg.load_from_db(node_types=['ticket', 'ci'])

# Only load high-confidence edges
kg.load_from_db(min_confidence=0.7)
```

2. **Use connection pooling**:
```python
from mysql.connector import pooling

db_pool = pooling.MySQLConnectionPool(
    pool_name="graph_pool",
    pool_size=5,
    **db_config
)
```

3. **Add more indexes** (if needed):
```sql
-- For specific query patterns
CREATE INDEX idx_node_properties ON graph_nodes((CAST(properties->>'$.category' AS CHAR(50))));
```

## Next Steps

1. ✅ Schema created
2. ✅ Test script passed
3. ⏭️ Integrate with sync pipeline (Task 8-9)
4. ⏭️ Use in RAG queries (Task 13-14)
5. ⏭️ Monitor graph growth and performance

## Additional Resources

- NetworkX Documentation: https://networkx.org/
- MySQL JSON Functions: https://dev.mysql.com/doc/refman/8.0/en/json-functions.html
- Knowledge Graph Best Practices: See `KNOWLEDGE_GRAPH_README.md`
