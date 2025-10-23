# Enhanced Data Sync Pipeline

## Overview

The `sync_tickets_to_vector_db.py` script is a comprehensive data synchronization pipeline that:

1. **Queries MySQL** with rich data extraction (dynamic fields, comments, CI relations)
2. **Performs semantic chunking** of ticket content for better embedding quality
3. **Generates embeddings** using sentence-transformers with batch processing
4. **Upserts to ChromaDB** with metadata and duplicate handling
5. **Populates knowledge graph** with entities and relationships
6. **Provides comprehensive logging** and error handling

## Features

### Rich Data Extraction
- Tickets WITH dynamic fields (JSON aggregation)
- Tickets WITH comments (JSON aggregation)
- Tickets WITH related CI items (JSON aggregation)
- KB articles with full content
- CI items with specifications

### Semantic Chunking
Each ticket is split into logical chunks:
- **Header**: Ticket number, title, category, priority, status
- **Description**: Problem description
- **Dynamic Fields**: Category-specific fields (brand, model, location, etc.)
- **Comments**: Each comment as separate chunk with author and timestamp
- **Resolution**: Solution text
- **Related CIs**: Configuration items affected by the ticket

### Embedding Generation
- Uses `sentence-transformers/all-mpnet-base-v2` model (768 dimensions)
- Batch processing (100 chunks at a time)
- Progress bars for long-running operations
- Error handling with zero-vector fallback

### ChromaDB Integration
- Three collections: `tickets`, `knowledge_base`, `configuration_items`
- Upsert operations (update if exists, insert if new)
- Batch processing with progress tracking
- Metadata preservation for filtering

### Knowledge Graph Population
- Extracts entities using spaCy NER
- Creates nodes for tickets, users, CI items, KB articles, entities
- Builds relationships: CREATED_BY, AFFECTS, SIMILAR_TO, RESOLVED_BY, BELONGS_TO, MENTIONS
- Confidence scoring for all relationships
- Persistent storage in MySQL

## Installation

### Prerequisites

```bash
# Install Python dependencies
pip install mysql-connector-python chromadb sentence-transformers spacy tqdm numpy

# Download spaCy Dutch model
python -m spacy download nl_core_news_lg
```

### Directory Structure

```
ai_module/
├── scripts/
│   ├── sync_tickets_to_vector_db.py  # Main sync script
│   ├── entity_extractor.py           # Entity extraction
│   ├── relationship_extractor.py     # Relationship extraction
│   └── knowledge_graph.py            # Graph manager
├── chromadb_data/                    # ChromaDB storage
└── logs/                             # Sync logs
```

## Usage

### Basic Usage

```bash
# Sync tickets from last 24 hours (default)
python sync_tickets_to_vector_db.py

# Sync all tickets
python sync_tickets_to_vector_db.py --since-hours 0

# Sync last 7 days
python sync_tickets_to_vector_db.py --since-hours 168

# Sync with limit
python sync_tickets_to_vector_db.py --limit 100

# Incremental sync (last 1 hour)
python sync_tickets_to_vector_db.py --incremental
```

### Command-Line Arguments

- `--since-hours N`: Sync tickets updated in last N hours (default: 24, use 0 for all)
- `--limit N`: Maximum number of tickets to sync (default: no limit)
- `--incremental`: Run incremental sync (last 1 hour)

### Examples

```bash
# Daily full sync (run at 2 AM via Task Scheduler)
python sync_tickets_to_vector_db.py --since-hours 24

# Hourly incremental sync
python sync_tickets_to_vector_db.py --incremental

# Initial full sync (first time setup)
python sync_tickets_to_vector_db.py --since-hours 0

# Test with small dataset
python sync_tickets_to_vector_db.py --limit 10
```

## Output

### Console Output

```
[2024-10-23 02:00:15] [INFO] [SYNC] Starting full sync pipeline
[2024-10-23 02:00:16] [INFO] [SYNC] Loading embedding model: sentence-transformers/all-mpnet-base-v2
[2024-10-23 02:00:45] [INFO] [SYNC] Embedding model loaded successfully
[2024-10-23 02:00:46] [INFO] [SYNC] Fetching tickets (since_hours=24, limit=None)...
[2024-10-23 02:00:47] [INFO] [SYNC] Fetched 47 tickets
Processing tickets: 100%|████████████████| 47/47 [00:15<00:00, 3.13ticket/s]
Generating embeddings: 100%|████████████| 235/235 [00:30<00:00, 7.83chunk/s]
Upserting to tickets: 100%|████████████| 235/235 [00:05<00:00, 47.00doc/s]
[2024-10-23 02:01:52] [INFO] [SYNC] Ticket sync completed: 47 tickets synced
[2024-10-23 02:01:53] [INFO] [SYNC] Fetching KB articles...
[2024-10-23 02:01:54] [INFO] [SYNC] Fetched 12 KB articles
Processing KB articles: 100%|████████████| 12/12 [00:02<00:00, 6.00article/s]
[2024-10-23 02:02:10] [INFO] [SYNC] KB sync completed: 12 articles synced
[2024-10-23 02:02:11] [INFO] [SYNC] Fetching CI items...
[2024-10-23 02:02:12] [INFO] [SYNC] Fetched 5 CI items
Processing CI items: 100%|████████████████| 5/5 [00:01<00:00, 5.00item/s]
[2024-10-23 02:02:20] [INFO] [SYNC] CI sync completed: 5 items synced
[2024-10-23 02:02:21] [INFO] [SYNC] SYNC COMPLETED SUCCESSFULLY
[2024-10-23 02:02:21] [INFO] [SYNC] Duration: 126.3 seconds
[2024-10-23 02:02:21] [INFO] [SYNC] Tickets synced: 47
[2024-10-23 02:02:21] [INFO] [SYNC] KB articles synced: 12
[2024-10-23 02:02:21] [INFO] [SYNC] CI items synced: 5
[2024-10-23 02:02:21] [INFO] [SYNC] Entities extracted: 234
[2024-10-23 02:02:21] [INFO] [SYNC] Relationships created: 189
[2024-10-23 02:02:21] [INFO] [SYNC] Errors: 0
```

### Log Files

Logs are written to `ai_module/logs/sync_YYYY-MM-DD.log`:

```
[2024-10-23 02:00:15] [INFO] [SYNC] Starting full sync pipeline
[2024-10-23 02:00:16] [INFO] [SYNC] Loading embedding model...
[2024-10-23 02:00:45] [INFO] [SYNC] Embedding model loaded successfully
[2024-10-23 02:00:47] [INFO] [SYNC] Fetched 47 tickets
[2024-10-23 02:01:52] [INFO] [SYNC] Ticket sync completed: 47 tickets synced
[2024-10-23 02:02:21] [INFO] [SYNC] SYNC COMPLETED SUCCESSFULLY
```

## Performance

### Expected Performance

- **Embedding generation**: ~100 chunks/minute (CPU)
- **ChromaDB upsert**: ~50 documents/second
- **Knowledge graph**: ~20 nodes+edges/second
- **Full sync (1000 tickets)**: ~15 minutes

### Resource Usage

- **RAM**: ~2-4 GB (embedding model + ChromaDB)
- **CPU**: 50-80% during embedding generation
- **Disk**: ~10 MB per 1000 tickets (embeddings + metadata)

## Troubleshooting

### Common Issues

**1. spaCy model not found**
```bash
python -m spacy download nl_core_news_lg
```

**2. MySQL connection error**
- Check database credentials in script
- Ensure MySQL server is running
- Verify database name is correct

**3. ChromaDB permission error**
- Ensure `chromadb_data` directory exists
- Check write permissions

**4. Out of memory**
- Reduce batch size in `generate_embeddings_batch()`
- Use `--limit` to process fewer tickets
- Close other applications

**5. Slow performance**
- Use `--incremental` for hourly syncs
- Increase batch size if you have more RAM
- Consider GPU acceleration for embeddings

### Error Handling

The script includes comprehensive error handling:
- Database connection failures: Retry with exponential backoff
- Embedding generation errors: Skip chunk with zero vector
- ChromaDB write failures: Log error and continue
- Knowledge graph errors: Log error and continue

All errors are logged to the log file with full stack traces.

## Integration with Windows Task Scheduler

### Daily Full Sync (2 AM)

```powershell
# Task Name: TicketportaalAISync
# Trigger: Daily at 02:00
# Action: Run program
Program: C:\TicketportaalAI\venv\Scripts\python.exe
Arguments: C:\TicketportaalAI\scripts\sync_tickets_to_vector_db.py --since-hours 24
Start in: C:\TicketportaalAI\scripts
```

### Hourly Incremental Sync

```powershell
# Task Name: TicketportaalAISyncHourly
# Trigger: Every hour
# Action: Run program
Program: C:\TicketportaalAI\venv\Scripts\python.exe
Arguments: C:\TicketportaalAI\scripts\sync_tickets_to_vector_db.py --incremental
Start in: C:\TicketportaalAI\scripts
```

## Monitoring

### Check Sync Status

```bash
# View latest log
tail -f ai_module/logs/sync_$(date +%Y-%m-%d).log

# Check for errors
grep ERROR ai_module/logs/sync_*.log

# Count synced items
grep "synced:" ai_module/logs/sync_$(date +%Y-%m-%d).log
```

### Verify ChromaDB

```python
import chromadb
from chromadb.config import Settings

client = chromadb.Client(Settings(persist_directory='ai_module/chromadb_data'))

# Check collections
print(client.list_collections())

# Get collection stats
tickets = client.get_collection('tickets')
print(f"Tickets: {tickets.count()} documents")

kb = client.get_collection('knowledge_base')
print(f"KB: {kb.count()} documents")

ci = client.get_collection('configuration_items')
print(f"CI: {ci.count()} documents")
```

### Verify Knowledge Graph

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

## Next Steps

After running the sync script:

1. **Verify data**: Check ChromaDB collections and knowledge graph
2. **Test queries**: Use ChromaDB query API to test vector search
3. **Build RAG API**: Implement FastAPI service for query endpoints
4. **Integrate with PHP**: Create AIHelper class for ticketportaal integration

## Support

For issues or questions:
- Check logs in `ai_module/logs/`
- Review error messages in console output
- Verify database connectivity
- Ensure all dependencies are installed
