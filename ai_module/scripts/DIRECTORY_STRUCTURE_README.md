# TicketportaalAI Directory Structure

## Overview

This document describes the directory structure for the TicketportaalAI system located at `C:\TicketportaalAI\`.

## Directory Layout

```
C:\TicketportaalAI\
├── scripts\          # Python and PowerShell scripts
├── logs\             # Application and service logs
├── chromadb_data\    # ChromaDB vector database storage
├── models\           # Ollama model storage
├── backups\          # Backup files
└── venv\             # Python virtual environment
```

## Directory Descriptions

### scripts/
Contains all Python and PowerShell scripts for the RAG system:
- `sync_tickets_to_vector_db.py` - Main data synchronization script
- `rag_api.py` - FastAPI RAG service
- `health_monitor.ps1` - Service health monitoring
- `set_permissions.ps1` - IIS AppPool permission configuration
- `log_rotation.ps1` - Log file rotation and cleanup

### logs/
Stores all log files:
- `ollama.log` - Ollama service logs
- `rag_api.log` - FastAPI service logs
- `sync_YYYYMMDD.log` - Daily sync logs
- `health_monitor.log` - Health check logs

**Log Rotation**: Logs older than 30 days are automatically deleted. Logs larger than 100MB are compressed.

### chromadb_data/
Persistent storage for ChromaDB vector database:
- Ticket embeddings
- KB article embeddings
- CI item embeddings
- Collection metadata

**Important**: Do not manually modify files in this directory.

### models/
Ollama model storage:
- Llama 3.1 8B model files (~4.7GB)
- Model configurations

**Note**: Models are managed by Ollama service.

### backups/
Backup storage for:
- ChromaDB snapshots
- Configuration backups
- Log archives

### venv/
Python virtual environment with all dependencies:
- ChromaDB
- sentence-transformers
- FastAPI
- uvicorn
- Other Python packages

## Permissions

### IIS AppPool Access
The IIS AppPool identity needs Read, Write, and Modify permissions for:
- `logs\` - To write application logs
- `chromadb_data\` - To read/write vector database
- `backups\` - To create backup files

**To configure permissions**, run as Administrator:
```powershell
C:\TicketportaalAI\scripts\set_permissions.ps1
```

### Service Account Access
The Windows Services (Ollama, TicketportaalRAG) run as SYSTEM and have full access.

## Maintenance

### Log Rotation
Run manually or schedule via Task Scheduler:
```powershell
C:\TicketportaalAI\scripts\log_rotation.ps1
```

**Recommended Schedule**: Weekly (every Sunday at 3:00 AM)

### Disk Space Monitoring
Minimum recommended free space: 20GB

Check current usage:
```powershell
Get-PSDrive C | Select-Object Used, Free
```

### Backup Strategy
1. **Daily**: Automated sync creates incremental backups
2. **Weekly**: Manual ChromaDB snapshot (recommended)
3. **Monthly**: Full system backup including models

## Troubleshooting

### Permission Issues
If PHP cannot write to logs:
1. Verify IIS AppPool identity
2. Run `set_permissions.ps1` as Administrator
3. Check Windows Event Viewer for access denied errors

### Disk Space Issues
If disk space is low:
1. Run `log_rotation.ps1` to clean old logs
2. Check `backups\` for old backup files
3. Consider moving `models\` to larger drive

### ChromaDB Corruption
If vector database is corrupted:
1. Stop TicketportaalRAG service
2. Delete `chromadb_data\` contents
3. Run full sync to rebuild database

## Security Notes

- All directories are local to the server (no network shares)
- No external API access required
- Data never leaves the K&K network
- GDPR compliant (all processing on-premise)

## Support

For issues or questions, contact the system administrator or refer to:
- Main documentation: `ai_module/README.md`
- Service setup: `ai_module/OLLAMA_SERVICE_SETUP.md`
