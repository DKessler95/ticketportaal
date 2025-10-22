# AI Module - Ticketportaal RAG Implementation

## Locatie

Deze AI module is onderdeel van het Ticketportaal project en bevindt zich in:
```
C:\Users\Damian\XAMPP\htdocs\ticketportaal\ai_module\
```

## Structuur

```
ai_module/
├── venv/                 # Python virtual environment
├── scripts/              # Python scripts (sync, RAG API)
├── logs/                 # Application logs
├── chromadb_data/        # Vector database storage
├── models/               # AI models (Ollama)
├── backups/              # Backup files
└── requirements.txt      # Python dependencies
```

## Development vs Production

### Development (Huidige Setup)
- **Locatie**: `C:\Users\Damian\XAMPP\htdocs\ticketportaal\ai_module\`
- **Voordeel**: Alles in één project folder, makkelijk te synchroniseren met Git
- **Gebruik**: Voor development en testing

### Production (Toekomstige Deployment)
- **Locatie**: `C:\TicketportaalAI\` (op Windows Server)
- **Voordeel**: Gescheiden van webroot, betere security en performance
- **Gebruik**: Voor live productie omgeving

## Installatie

### Stap 1: Virtual Environment
```bash
python -m venv ai_module\venv
```

### Stap 2: Dependencies Installeren
```bash
ai_module\venv\Scripts\pip.exe install -r ai_module\requirements.txt
```

### Stap 3: Verificatie
```bash
ai_module\venv\Scripts\python.exe verify_installation.py
```

## Configuratie

### Database Connectie
Maak een `.env` file aan in `ai_module/`:
```
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=ticketportaal
```

### API Endpoints
- **RAG API**: http://localhost:5005
- **Ollama**: http://localhost:11434

## Volgende Stappen

1. ✅ Task 1: Development Environment Setup (COMPLETED)
2. 🔄 Task 2: Install and Configure Ollama (IN PROGRESS)
   - Scripts created in `scripts/` directory
   - See `OLLAMA_INSTALLATION_GUIDE.md` for detailed instructions
   - Run `scripts\install_ollama.bat` to begin installation
3. ⏳ Task 3: Setup Ollama as Windows Service
4. ⏳ Task 4: Create Directory Structure

## Task 2: Ollama Installation

### Quick Start

**Option 1: Automated Installation (Recommended)**
```powershell
cd ai_module\scripts
.\install_ollama.bat
```

**Option 2: PowerShell Script**
```powershell
cd ai_module\scripts
.\install_ollama.ps1
```

### What Gets Installed

- **Ollama**: Local LLM server for running Llama models
- **Llama 3.1 8B**: 4.7GB language model for AI responses
- **Environment Variables**: OLLAMA_HOST, OLLAMA_ORIGINS, OLLAMA_MODELS
- **Models Directory**: C:\TicketportaalAI\models

### Verification

After installation, verify everything works:
```powershell
cd ai_module\scripts
.\verify_ollama.bat
```

### Testing

Test the Ollama API endpoints:
```powershell
cd ai_module\scripts
.\test_ollama_api.bat
```

### Documentation

- **Installation Guide**: `OLLAMA_INSTALLATION_GUIDE.md`
- **Scripts README**: `scripts/README.md`
- **Task Details**: `.kiro/specs/rag-ai-local-implementation/tasks.md`

## Notities

- Voor development gebruik je de lokale `ai_module` folder
- Bij deployment naar productie kopieer je alles naar `C:\TicketportaalAI\`
- De PHP integratie blijft in `includes/ai_helper.php`
