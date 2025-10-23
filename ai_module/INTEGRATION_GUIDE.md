# Sync Pipeline Integration Guide

## Overview

This guide explains how the sync pipeline integrates with the existing ticketportaal system and prepares data for the RAG API.

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    K&K Windows Server                            │
│                                                                   │
│  ┌──────────────────┐         ┌─────────────────┐              │
│  │  IIS + PHP       │         │  MySQL Database │              │
│  │  Ticketportaal   │◄────────┤  (Source)       │              │
│  └──────────────────┘         └────────┬────────┘              │
│                                         │                        │
│                                         │ Read                   │
│                                         ▼                        │
│                          ┌──────────────────────┐               │
│                          │  Sync Pipeline       │               │
│                          │  (This Component)    │               │
│                          └──────────┬───────────┘               │
│                                     │                            │
│                    ┌────────────────┼────────────────┐          │
│                    │                │                │          │
│                    ▼                ▼                ▼          │
│           ┌────────────┐  ┌────────────┐  ┌────────────┐      │
│           │  ChromaDB  │  │  Knowledge │  │  Logs      │      │
│           │  (Vector)  │  │  Graph     │  │            │      │
│           └────────────┘  └────────────┘  └────────────┘      │
│                    │                │                            │
│                    └────────────────┘                            │
│                             │                                    │
│                             ▼                                    │
│                    ┌──────────────────┐                         │
│                    │  RAG API         │  (Next Phase)           │
│                    │  (FastAPI)       │                         │
│                    └──────────────────┘                         │
│                                                                   │
└───────────────────────────────────────────────────────────────────┘
```

## Data Flow

### 1. Source Data (MySQL)

The sync pipeline reads from these tables:

**Primary Tables:**
- `tickets` - Main ticket data
- `knowledge_base` - KB articles
- `configuration_items` - CI items

**Related Tables:**
- `ticket_field_values` - Dynamic category fields
- `category_fields` - Field definitions
- `ticket_comments` - Ticket comments
- `ticket_ci_relations` - Ticket-CI relationships
- `users` - User information

**Graph Tables (Written):**
- `graph_nodes` - Entity nodes
- `graph_edges` - Relationships

### 2. Processing Pipeline

```
MySQL Data
    │
    ├─► Rich Query (JSON aggregation)
    │   ├─ Dynamic fields
    │   ├─ Comments
    │   └─ Related CIs
    │
    ├─► Semantic Chunking
    │   ├─ Header
    │   ├─ Description
    │   ├─ Dynamic fields
    │   ├─ Comments (each separate)
    │   ├─ Resolution
    │   └─ Related CIs
    │
    ├─► Entity Extraction (spaCy NER)
    │   ├─ Products
    │   ├─ Errors
    │   ├─ Locations
    │   ├─ Persons
    │   └─ Organizations
    │
    ├─► Embedding Generation (sentence-transformers)
    │   └─ 768-dimensional vectors
    │
    ├─► ChromaDB Upsert
    │   ├─ tickets collection
    │   ├─ knowledge_base collection
    │   └─ configuration_items collection
    │
    └─► Knowledge Graph Population
        ├─ Create nodes (tickets, users, CIs, entities)
        └─ Create edges (relationships)
```

### 3. Output Data

**ChromaDB Collections:**

```python
# tickets collection
{
    "id": "ticket_123_header_0",
    "document": "Ticket T-2024-001: Laptop start niet op\nCategory: Hardware\n...",
    "metadata": {
        "ticket_id": 123,
        "ticket_number": "T-2024-001",
        "category": "Hardware",
        "priority": "High",
        "status": "Open",
        "chunk_type": "header"
    },
    "embedding": [0.123, -0.456, ...]  # 768 dimensions
}
```

**Knowledge Graph:**

```sql
-- Nodes
INSERT INTO graph_nodes (node_id, node_type, properties)
VALUES ('ticket_123', 'ticket', '{"ticket_number": "T-2024-001", ...}');

-- Edges
INSERT INTO graph_edges (source_id, target_id, edge_type, confidence)
VALUES ('ticket_123', 'user_45', 'CREATED_BY', 1.0);
```

## Integration Points

### 1. Database Schema Requirements

The sync pipeline requires these tables to exist:

```sql
-- Already exists in ticketportaal
tickets
knowledge_base
configuration_items
ticket_field_values
category_fields
ticket_comments
ticket_ci_relations
users

-- Created by migration 007
graph_nodes
graph_edges
```

### 2. Data Quality Requirements

For optimal AI performance:

**Dynamic Fields:**
- All categories should have fields configured
- Agents should fill fields consistently
- Use dropdowns instead of free text where possible

**Comments:**
- Include troubleshooting steps
- Document solutions
- Add context for images

**CI Relations:**
- Link tickets to relevant CI items
- Keep CI specifications up to date

### 3. Existing Components Used

The sync pipeline uses these existing modules:

```python
from entity_extractor import EntityExtractor
from relationship_extractor import RelationshipExtractor
from knowledge_graph import KnowledgeGraph
```

These were implemented in previous tasks (7, 8, 9).

## Configuration

### Database Configuration

Located in `sync_tickets_to_vector_db.py`:

```python
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # XAMPP default
    'database': 'ticketportaal'
}
```

Update if your database credentials differ.

### ChromaDB Path

```python
chromadb_path = os.path.join(
    os.path.dirname(os.path.dirname(__file__)),
    'chromadb_data'
)
```

Default: `ai_module/chromadb_data/`

### Embedding Model

```python
embedding_model_name = 'sentence-transformers/all-mpnet-base-v2'
```

Can be changed to other sentence-transformer models if needed.

## Usage Patterns

### Initial Setup (First Time)

```bash
# 1. Test with small dataset
python sync_tickets_to_vector_db.py --limit 10

# 2. Verify results
python test_sync_pipeline.py

# 3. Run full sync
python sync_tickets_to_vector_db.py --since-hours 0
```

### Daily Operations

```bash
# Automated via Task Scheduler at 2 AM
python sync_tickets_to_vector_db.py --since-hours 24
```

### Incremental Updates

```bash
# Automated via Task Scheduler every hour
python sync_tickets_to_vector_db.py --incremental
```

### Manual Sync

```bash
# Sync specific time range
python sync_tickets_to_vector_db.py --since-hours 168  # Last week

# Sync with limit
python sync_tickets_to_vector_db.py --limit 100
```

## Monitoring

### Log Files

Location: `ai_module/logs/sync_YYYY-MM-DD.log`

```bash
# View today's log
type ai_module\logs\sync_2024-10-23.log

# Check for errors
findstr /C:"ERROR" ai_module\logs\sync_*.log

# Check sync statistics
findstr /C:"SYNC COMPLETED" ai_module\logs\sync_*.log
```

### ChromaDB Verification

```python
import chromadb
from chromadb.config import Settings

client = chromadb.Client(Settings(
    persist_directory='ai_module/chromadb_data',
    anonymized_telemetry=False
))

# Check collections
for collection in client.list_collections():
    print(f"{collection.name}: {collection.count()} documents")
```

### Knowledge Graph Verification

```python
from knowledge_graph import KnowledgeGraph

db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ticketportaal'
}

kg = KnowledgeGraph(db_config)
kg.load_from_db()

stats = kg.get_stats()
print(f"Nodes: {stats['total_nodes']}")
print(f"Edges: {stats['total_edges']}")
print(f"Node types: {stats['node_types']}")
print(f"Edge types: {stats['edge_types']}")
```

## Performance Considerations

### Resource Usage

**During Sync:**
- RAM: 2-4 GB
- CPU: 50-80%
- Disk I/O: Moderate

**At Rest:**
- Disk: ~10 MB per 1000 tickets
- No CPU/RAM usage

### Optimization Tips

1. **Use incremental sync** for regular updates (hourly)
2. **Schedule full sync** during off-hours (2 AM)
3. **Limit batch size** if memory constrained
4. **Close other applications** during sync
5. **Use SSD** for ChromaDB storage

### Expected Duration

- 10 tickets: ~2-3 minutes
- 100 tickets: ~10-15 minutes
- 1000 tickets: ~15-20 minutes
- Full database: ~1 minute per 50 tickets

## Error Handling

### Common Errors

**1. Database Connection Error**
```
[ERROR] Error fetching tickets: Can't connect to MySQL server
```
**Solution:** Check MySQL is running, verify credentials

**2. spaCy Model Not Found**
```
[ERROR] Model nl_core_news_lg not found
```
**Solution:** `python -m spacy download nl_core_news_lg`

**3. ChromaDB Permission Error**
```
[ERROR] Permission denied: chromadb_data
```
**Solution:** Check directory permissions, create manually if needed

**4. Out of Memory**
```
[ERROR] MemoryError during embedding generation
```
**Solution:** Reduce batch size or use `--limit`

### Error Recovery

The sync pipeline is designed to be resilient:

- **Database errors**: Logs error, continues with other operations
- **Embedding errors**: Uses zero vectors, continues processing
- **ChromaDB errors**: Logs error, continues with next batch
- **Graph errors**: Logs error, continues with next ticket

All errors are logged with full context for debugging.

## Next Steps

After successful sync:

### 1. Verify Data Quality

```bash
# Run test script
python test_sync_pipeline.py

# Check logs
type ai_module\logs\sync_2024-10-23.log

# Verify ChromaDB
python -c "import chromadb; print(chromadb.Client(...).list_collections())"
```

### 2. Build RAG API (Task 16-19)

The sync pipeline prepares data for the RAG API:
- ChromaDB collections for vector search
- Knowledge graph for relationship queries
- Embeddings for semantic similarity

### 3. Integrate with PHP (Task 26-30)

The RAG API will be called from PHP:
- AIHelper class for API communication
- AI suggestion widget for display
- Admin dashboard for monitoring

## Support

### Troubleshooting Steps

1. **Check logs** in `ai_module/logs/`
2. **Run test script** to verify setup
3. **Verify database** connectivity
4. **Check ChromaDB** directory permissions
5. **Review error messages** in console

### Getting Help

- Review documentation in `SYNC_PIPELINE_README.md`
- Check quick start guide in `SYNC_QUICK_START.md`
- Run test suite: `python test_sync_pipeline.py`
- Check task completion summary in `TASK_10_COMPLETION_SUMMARY.md`

## Maintenance

### Regular Tasks

**Daily:**
- Check sync logs for errors
- Verify sync completed successfully

**Weekly:**
- Review sync statistics
- Check disk space usage
- Verify data quality

**Monthly:**
- Archive old logs
- Review performance metrics
- Update documentation if needed

### Backup

**ChromaDB Data:**
```bash
# Backup
xcopy /E /I ai_module\chromadb_data ai_module\backups\chromadb_2024-10-23

# Restore
xcopy /E /I ai_module\backups\chromadb_2024-10-23 ai_module\chromadb_data
```

**Knowledge Graph:**
```sql
-- Backup
mysqldump ticketportaal graph_nodes graph_edges > graph_backup.sql

-- Restore
mysql ticketportaal < graph_backup.sql
```

## Conclusion

The sync pipeline is a critical component that:
- Extracts rich data from MySQL
- Generates high-quality embeddings
- Populates ChromaDB for vector search
- Builds knowledge graph for relationships
- Prepares data for RAG API

It runs automatically via Task Scheduler and requires minimal maintenance once configured.
