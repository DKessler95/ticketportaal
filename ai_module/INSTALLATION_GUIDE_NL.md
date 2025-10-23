# K&K Ticketportaal AI - Installatie Handleiding

## Overzicht

Deze handleiding beschrijft hoe je het complete AI systeem installeert op een Windows Server of Windows PC.

## Vereisten

### Hardware
- **CPU**: Minimaal 4 cores (8 aanbevolen)
- **RAM**: Minimaal 16GB (32GB aanbevolen)
- **Schijfruimte**: Minimaal 60GB vrij
- **Netwerk**: Stabiele internetverbinding voor downloads

### Software
- **Windows**: Windows 10/11 of Windows Server 2019/2022
- **Python**: Versie 3.11 of hoger
- **MySQL**: 5.7+ of MariaDB 10.3+ (XAMPP is prima)
- **IIS**: Voor PHP/ticketportaal (optioneel tijdens AI installatie)
- **Administrator rechten**: Vereist voor installatie

## Installatie Methoden

### Methode 1: Automatische Installatie (Aanbevolen)

1. **Download het project**
   ```
   Pak het project uit naar een locatie, bijv: C:\ticketportaal\
   ```

2. **Open PowerShell als Administrator**
   - Klik rechts op Start menu
   - Kies "Windows PowerShell (Admin)" of "Terminal (Admin)"

3. **Navigeer naar ai_module directory**
   ```powershell
   cd C:\ticketportaal\ai_module
   ```

4. **Voer installatie script uit**
   ```powershell
   .\install_complete_system.ps1
   ```

   Of dubbelklik op `install.bat` (zorg dat je Administrator rechten hebt)

5. **Volg de instructies op het scherm**

### Methode 2: Handmatige Installatie

Als de automatische installatie niet werkt, volg dan deze stappen:

#### Stap 1: Python Virtual Environment

```powershell
cd C:\ticketportaal\ai_module
python -m venv venv
.\venv\Scripts\activate
```

#### Stap 2: Installeer Dependencies

```powershell
pip install mysql-connector-python chromadb sentence-transformers fastapi uvicorn spacy rank-bm25 networkx numpy tqdm psutil requests
python -m spacy download nl_core_news_lg
```

#### Stap 3: Installeer Ollama

1. Download Ollama van https://ollama.com/download
2. Installeer Ollama
3. Download Llama model:
   ```powershell
   ollama pull llama3.1:8b
   ```
   (Dit duurt 10-30 minuten, 4.7GB download)

#### Stap 4: Database Migraties

```powershell
cd C:\ticketportaal
mysql -u root ticketportaal < database\migrations\007_create_knowledge_graph_schema.sql
```

#### Stap 5: Installeer Windows Services

```powershell
cd C:\ticketportaal\ai_module\scripts
.\install_rag_service.ps1
```

#### Stap 6: Configureer Scheduled Tasks

```powershell
.\setup_scheduled_tasks.ps1
```

## Configuratie

### AI Configuratie

Bewerk `config/ai_config.php`:

```php
// Enable/disable AI
define('AI_ENABLED', true);

// RAG API URL
define('RAG_API_URL', 'http://localhost:5005');

// Beta users (leeg = iedereen)
define('AI_BETA_USERS', []);
```

### Database Configuratie

Controleer `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticketportaal');
define('DB_USER', 'root');
define('DB_PASS', '');  // Pas aan indien nodig
```

## Verificatie

### 1. Check Services

```powershell
# Check Ollama
Get-Service Ollama

# Check RAG API
Get-Service TicketportaalRAG

# Start services indien nodig
Start-Service Ollama
Start-Service TicketportaalRAG
```

### 2. Test RAG API

Open browser: http://localhost:5005/health

Verwachte output:
```json
{
  "status": "healthy",
  "ollama_available": true,
  "chromadb_available": true,
  "graph_available": true
}
```

### 3. Test Sync

```powershell
cd C:\ticketportaal\ai_module\scripts
python sync_tickets_to_vector_db.py --limit 10
```

### 4. Check AI Dashboard

Open browser: http://localhost/admin/ai_dashboard.php

## Eerste Gebruik

### 1. Initiële Data Sync

```powershell
cd C:\ticketportaal\ai_module\scripts
python sync_tickets_to_vector_db.py --since-hours 0
```

Dit synchroniseert alle tickets naar de vector database (kan 15-30 minuten duren).

### 2. Test AI Suggesties

1. Open een ticket in het ticketportaal
2. AI suggesties verschijnen automatisch onder de ticket beschrijving
3. Klik op "👍 Nuttig" of "👎 Niet nuttig" voor feedback

### 3. Monitor Systeem

Bekijk het AI Dashboard voor:
- Service status
- Query statistieken
- Performance metrics
- System resources

## Onderhoud

### Dagelijkse Taken (Automatisch)

- **02:00**: Volledige sync van alle tickets (laatste 24 uur)
- **Elk uur**: Incrementele sync van nieuwe tickets
- **Elke 30 min**: Health monitoring

### Wekelijkse Taken (Handmatig)

- Check logs voor errors
- Bekijk AI Dashboard statistieken
- Controleer disk space

### Maandelijkse Taken (Handmatig)

- Archiveer oude logs
- Review AI performance metrics
- Update dependencies indien nodig

## Troubleshooting

### Probleem: RAG API start niet

**Oplossing:**
```powershell
# Check logs
Get-Content C:\TicketportaalAI\logs\rag_api_stderr.log -Tail 50

# Herstart service
Restart-Service TicketportaalRAG

# Test handmatig
cd C:\TicketportaalAI\scripts
python rag_api.py
```

### Probleem: Ollama niet beschikbaar

**Oplossing:**
```powershell
# Check service
Get-Service Ollama

# Start service
Start-Service Ollama

# Test Ollama
ollama list
ollama run llama3.1:8b "test"
```

### Probleem: Sync faalt

**Oplossing:**
```powershell
# Check database connectie
mysql -u root -e "USE ticketportaal; SELECT COUNT(*) FROM tickets;"

# Check Python dependencies
cd C:\TicketportaalAI
.\venv\Scripts\python.exe -m pip list

# Run sync met debug
cd scripts
python sync_tickets_to_vector_db.py --limit 1
```

### Probleem: Geen AI suggesties zichtbaar

**Oplossing:**
1. Check `config/ai_config.php`: `AI_ENABLED` moet `true` zijn
2. Check RAG API: http://localhost:5005/health
3. Check browser console voor JavaScript errors
4. Check of gebruiker in beta groep zit (indien geconfigureerd)

### Probleem: Trage performance

**Oplossing:**
- Check CPU/RAM gebruik in AI Dashboard
- Verhoog `query_semaphore` in `rag_api.py` (standaard: 5)
- Schakel graph search uit voor snellere queries
- Overweeg hardware upgrade (meer RAM/CPU)

## Deinstallatie

### Services Verwijderen

```powershell
# Stop services
Stop-Service TicketportaalRAG
Stop-Service Ollama

# Verwijder services
nssm remove TicketportaalRAG confirm
nssm remove Ollama confirm
```

### Scheduled Tasks Verwijderen

```powershell
Unregister-ScheduledTask -TaskName "TicketportaalAISync" -Confirm:$false
Unregister-ScheduledTask -TaskName "TicketportaalAISyncHourly" -Confirm:$false
Unregister-ScheduledTask -TaskName "TicketportaalAIHealthMonitor" -Confirm:$false
```

### Bestanden Verwijderen

```powershell
Remove-Item -Path "C:\TicketportaalAI" -Recurse -Force
```

## Support

### Logs Locaties

- **RAG API**: `C:\TicketportaalAI\logs\rag_api_*.log`
- **Sync**: `C:\TicketportaalAI\logs\sync_*.log`
- **Health Monitor**: `C:\TicketportaalAI\logs\health_monitor_*.log`
- **AI Interactions**: `C:\TicketportaalAI\logs\ai_interactions.log`

### Nuttige Commando's

```powershell
# Service status
Get-Service | Where-Object {$_.Name -like '*Ticketportaal*' -or $_.Name -like 'Ollama'}

# Logs bekijken
Get-Content C:\TicketportaalAI\logs\rag_api_*.log -Tail 50 -Wait

# Scheduled tasks bekijken
Get-ScheduledTask | Where-Object {$_.TaskName -like 'Ticketportaal*'}

# Disk space
Get-PSDrive C

# Resource usage
Get-Process | Where-Object {$_.Name -like '*python*' -or $_.Name -like '*ollama*'}
```

## Veelgestelde Vragen

**Q: Hoeveel schijfruimte heb ik nodig?**
A: Minimaal 60GB. Ollama model = 4.7GB, ChromaDB data = ~10MB per 1000 tickets, logs = variabel.

**Q: Kan ik dit op mijn laptop installeren?**
A: Ja, maar zorg voor minimaal 16GB RAM en goede CPU. Performance kan trager zijn dan op een server.

**Q: Moet ik Ollama gebruiken of kan ik een andere LLM gebruiken?**
A: Momenteel is Ollama vereist. In de toekomst kunnen we andere LLMs ondersteunen.

**Q: Hoe update ik het systeem?**
A: Pull de laatste code, run `pip install --upgrade` voor dependencies, herstart services.

**Q: Kan ik meerdere instanties draaien?**
A: Ja, maar ze moeten verschillende poorten gebruiken. Pas `RAG_API_URL` aan in config.

**Q: Hoe backup ik de AI data?**
A: Backup `C:\TicketportaalAI\chromadb_data\` en de MySQL `graph_nodes` en `graph_edges` tabellen.

## Licentie & Credits

Dit systeem is ontwikkeld voor K&K Kruit en Kramer.

Gebruikt open-source componenten:
- Ollama (MIT License)
- ChromaDB (Apache 2.0)
- FastAPI (MIT License)
- spaCy (MIT License)
- sentence-transformers (Apache 2.0)

---

**Laatste update**: 2024-10-23
**Versie**: 1.0.0
