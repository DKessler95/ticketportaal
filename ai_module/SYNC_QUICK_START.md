# Sync Pipeline Quick Start Guide

## Prerequisites Check

Before running the sync pipeline, ensure you have:

1. **Python 3.11+** installed
2. **Virtual environment** activated
3. **All dependencies** installed
4. **MySQL database** running with ticketportaal data
5. **spaCy Dutch model** downloaded

## Step 1: Install Dependencies

```bash
# Navigate to ai_module directory
cd ai_module

# Activate virtual environment (if not already activated)
venv\Scripts\activate  # Windows
# or
source venv/bin/activate  # Linux/Mac

# Install required packages
pip install mysql-connector-python chromadb sentence-transformers spacy tqdm numpy

# Download spaCy Dutch model
python -m spacy download nl_core_news_lg
```

## Step 2: Verify Setup

Run the test script to verify all components are working:

```bash
cd scripts
python test_sync_pipeline.py
```

Expected output:
```
============================================================
SYNC PIPELINE TEST SUITE
============================================================
Testing imports...
✓ mysql-connector-python
✓ chromadb
✓ sentence-transformers
✓ spacy
✓ tqdm
✓ numpy

Testing spaCy model...
✓ nl_core_news_lg model loaded
  Extracted 2 entities: [('Dell Latitude', 'PRODUCT'), ('Kantoor Hengelo', 'LOC')]

Testing database connection...
✓ Database connected: 150 tickets found
✓ All required tables exist

Testing ChromaDB...
✓ ChromaDB initialized at: C:\path\to\ai_module\chromadb_data

Testing embedding model...
  Loading model (this may take a minute)...
✓ Embedding model loaded
  Generated embedding: shape=(768,), dtype=float32

Testing custom modules...
✓ entity_extractor
✓ relationship_extractor
✓ knowledge_graph

Testing entity extraction...
✓ Entity extraction working
  Products: 1
  Errors: 1
  Locations: 1

Testing knowledge graph...
✓ Knowledge graph loaded
  Nodes: 0
  Edges: 0

============================================================
TEST SUMMARY
============================================================
✓ PASS: Imports
✓ PASS: spaCy Model
✓ PASS: Database Connection
✓ PASS: ChromaDB
✓ PASS: Embedding Model
✓ PASS: Custom Modules
✓ PASS: Entity Extraction
✓ PASS: Knowledge Graph
============================================================
Results: 8/8 tests passed

✓ All tests passed! Ready to run sync pipeline.
```

## Step 3: Run Initial Sync

### Test with Small Dataset

First, test with a small number of tickets:

```bash
python sync_tickets_to_vector_db.py --limit 10
```

This will:
- Sync only 10 tickets
- Generate embeddings
- Populate ChromaDB
- Build knowledge graph
- Take ~2-3 minutes

### Run Full Sync

Once the test succeeds, run a full sync:

```bash
# Sync all tickets (initial setup)
python sync_tickets_to_vector_db.py --since-hours 0

# Or sync last 24 hours (daily sync)
python sync_tickets_to_vector_db.py --since-hours 24
```

Expected output:
```
============================================================
STARTING FULL SYNC PIPELINE
============================================================
Start time: 2024-10-23 14:30:00
Parameters: since_hours=24, ticket_limit=None
[2024-10-23 14:30:01] [INFO] [SYNC] Initializing sync pipeline...
[2024-10-23 14:30:02] [INFO] [SYNC] Loading embedding model: sentence-transformers/all-mpnet-base-v2
[2024-10-23 14:30:45] [INFO] [SYNC] Embedding model loaded successfully
[2024-10-23 14:30:46] [INFO] [SYNC] Initializing ChromaDB at: C:\...\chromadb_data
[2024-10-23 14:30:47] [INFO] [SYNC] ChromaDB initialized successfully
[2024-10-23 14:30:48] [INFO] [SYNC] Initializing entity extractor...
[2024-10-23 14:30:50] [INFO] [SYNC] Entity extractor initialized
[2024-10-23 14:30:51] [INFO] [SYNC] Initializing relationship extractor...
[2024-10-23 14:30:52] [INFO] [SYNC] Relationship extractor initialized
[2024-10-23 14:30:53] [INFO] [SYNC] Initializing knowledge graph...
[2024-10-23 14:30:54] [INFO] [SYNC] Knowledge graph initialized
============================================================
Starting ticket sync...
============================================================
[2024-10-23 14:30:55] [INFO] [SYNC] Fetching tickets (since_hours=24, limit=None)...
[2024-10-23 14:30:56] [INFO] [SYNC] Fetched 47 tickets
[2024-10-23 14:30:57] [INFO] [SYNC] Processing 47 tickets...
Processing tickets: 100%|████████████████████| 47/47 [00:15<00:00, 3.13ticket/s]
[2024-10-23 14:31:12] [INFO] [SYNC] Generating embeddings for 235 chunks (batch_size=100)...
Generating embeddings: 100%|████████████████| 235/235 [00:30<00:00, 7.83chunk/s]
[2024-10-23 14:31:42] [INFO] [SYNC] Generated 235 embeddings
[2024-10-23 14:31:43] [INFO] [SYNC] Upserting 235 documents to collection 'tickets'...
Upserting to tickets: 100%|████████████████| 235/235 [00:05<00:00, 47.00doc/s]
[2024-10-23 14:31:48] [INFO] [SYNC] Successfully upserted 235 documents to 'tickets'
[2024-10-23 14:31:49] [INFO] [SYNC] Ticket sync completed: 47 tickets synced
============================================================
Starting KB article sync...
============================================================
[2024-10-23 14:31:50] [INFO] [SYNC] Fetching KB articles...
[2024-10-23 14:31:51] [INFO] [SYNC] Fetched 12 KB articles
Processing KB articles: 100%|████████████████| 12/12 [00:02<00:00, 6.00article/s]
Generating embeddings: 100%|██████████████████| 12/12 [00:02<00:00, 6.00chunk/s]
Upserting to knowledge_base: 100%|████████████| 12/12 [00:01<00:00, 12.00doc/s]
[2024-10-23 14:32:05] [INFO] [SYNC] KB sync completed: 12 articles synced
============================================================
Starting CI item sync...
============================================================
[2024-10-23 14:32:06] [INFO] [SYNC] Fetching CI items...
[2024-10-23 14:32:07] [INFO] [SYNC] Fetched 5 CI items
Processing CI items: 100%|████████████████████| 5/5 [00:01<00:00, 5.00item/s]
Generating embeddings: 100%|████████████████████| 5/5 [00:01<00:00, 5.00chunk/s]
Upserting to configuration_items: 100%|██████████| 5/5 [00:01<00:00, 5.00doc/s]
[2024-10-23 14:32:15] [INFO] [SYNC] CI sync completed: 5 items synced
============================================================
SYNC COMPLETED SUCCESSFULLY
============================================================
Duration: 140.5 seconds
Tickets synced: 47
KB articles synced: 12
CI items synced: 5
Entities extracted: 234
Relationships created: 189
Errors: 0
============================================================
```

## Step 4: Verify Results

### Check ChromaDB Collections

```python
import chromadb
from chromadb.config import Settings

client = chromadb.Client(Settings(
    persist_directory='../chromadb_data',
    anonymized_telemetry=False
))

# List collections
collections = client.list_collections()
print(f"Collections: {[c.name for c in collections]}")

# Get counts
tickets = client.get_collection('tickets')
print(f"Tickets: {tickets.count()} documents")

kb = client.get_collection('knowledge_base')
print(f"KB: {kb.count()} documents")

ci = client.get_collection('configuration_items')
print(f"CI: {ci.count()} documents")
```

### Check Knowledge Graph

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

### Check Logs

```bash
# View latest log
type ..\logs\sync_2024-10-23.log

# Check for errors
findstr /C:"ERROR" ..\logs\sync_*.log
```

## Step 5: Schedule Automated Syncs

### Daily Full Sync (Windows Task Scheduler)

1. Open Task Scheduler
2. Create Basic Task
3. Name: `TicketportaalAISync`
4. Trigger: Daily at 2:00 AM
5. Action: Start a program
   - Program: `C:\TicketportaalAI\venv\Scripts\python.exe`
   - Arguments: `C:\TicketportaalAI\scripts\sync_tickets_to_vector_db.py --since-hours 24`
   - Start in: `C:\TicketportaalAI\scripts`
6. Run whether user is logged on or not
7. Run with highest privileges

### Hourly Incremental Sync (Optional)

1. Create another task: `TicketportaalAISyncHourly`
2. Trigger: Every hour
3. Arguments: `C:\TicketportaalAI\scripts\sync_tickets_to_vector_db.py --incremental`

## Troubleshooting

### Issue: spaCy model not found

```bash
python -m spacy download nl_core_news_lg
```

### Issue: MySQL connection error

Check database credentials in the script:
```python
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # Update if needed
    'database': 'ticketportaal'
}
```

### Issue: ChromaDB permission error

```bash
# Create directory manually
mkdir ..\chromadb_data

# Check permissions
icacls ..\chromadb_data
```

### Issue: Out of memory

Reduce batch size or use limit:
```bash
python sync_tickets_to_vector_db.py --limit 100
```

### Issue: Slow performance

- Close other applications
- Use incremental sync for regular updates
- Consider GPU acceleration for embeddings

## Next Steps

After successful sync:

1. **Verify data quality**: Check that embeddings are generated correctly
2. **Test vector search**: Query ChromaDB to find similar tickets
3. **Build RAG API**: Implement FastAPI service (Task 16-19)
4. **Integrate with PHP**: Create AIHelper class (Task 26-30)

## Support

For issues:
- Check logs in `ai_module/logs/`
- Run test script: `python test_sync_pipeline.py`
- Review error messages in console
- Verify all dependencies are installed

## Performance Expectations

- **Small dataset (10 tickets)**: ~2-3 minutes
- **Medium dataset (100 tickets)**: ~10-15 minutes
- **Large dataset (1000 tickets)**: ~15-20 minutes
- **Full database (all tickets)**: Depends on size, estimate ~1 minute per 50 tickets

## Resource Usage

- **RAM**: 2-4 GB during sync
- **CPU**: 50-80% during embedding generation
- **Disk**: ~10 MB per 1000 tickets
- **Network**: None (all local processing)
