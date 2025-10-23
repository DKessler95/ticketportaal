# ✅ Installatie Checklist

Print deze lijst uit en vink af wat je hebt gedaan!

## Voor Je Begint

- [ ] Windows PC/Server met Administrator rechten
- [ ] Python 3.11+ geïnstalleerd (check: `python --version`)
- [ ] MySQL/XAMPP draait
- [ ] Minimaal 20GB vrije schijfruimte
- [ ] Goede internetverbinding (voor downloads)

## Installatie Stappen

### Stap 1: Voorbereiding
- [ ] Ticketportaal werkt normaal
- [ ] Database is bereikbaar
- [ ] Command Prompt geopend als Administrator
- [ ] Navigeer naar: `cd C:\...\ticketportaal\ai_module`

### Stap 2: Installatie Uitvoeren
- [ ] Run: `install.bat` (of dubbelklik als Administrator)
- [ ] Python packages installeren (5-10 min) ⏱️
- [ ] Ollama downloaden (5 min) ⏱️
- [ ] Llama model downloaden (10-30 min) ⏱️ ☕
- [ ] Services configureren
- [ ] Geen rode errors gezien

### Stap 3: Verificatie
- [ ] Run: `python verify_installation.py`
- [ ] Alle tests zijn groen ✅
- [ ] Eventuele waarschuwingen genoteerd

### Stap 4: Database Setup
- [ ] Open MySQL/phpMyAdmin
- [ ] Importeer: `database/migrations/007_create_knowledge_graph_schema.sql`
- [ ] Check of tabellen bestaan: `graph_nodes`, `graph_edges`

### Stap 5: Services Starten
- [ ] Ollama service draait: `Get-Service Ollama`
- [ ] RAG API service draait: `Get-Service TicketportaalRAG`
- [ ] Of handmatig gestart: `python scripts/rag_api.py`

### Stap 6: Health Check
- [ ] Browser open: http://localhost:5005/health
- [ ] Status is "healthy" of "degraded"
- [ ] Ollama available: true
- [ ] ChromaDB available: true

### Stap 7: Eerste Sync
- [ ] Run: `cd scripts`
- [ ] Run: `python sync_tickets_to_vector_db.py --limit 10`
- [ ] Sync voltooid zonder errors
- [ ] Check logs: `ai_module/logs/sync_*.log`

### Stap 8: AI Dashboard
- [ ] Browser open: http://localhost/admin/ai_dashboard.php
- [ ] Dashboard laadt correct
- [ ] Services zijn groen
- [ ] Statistieken worden getoond

### Stap 9: Test AI Suggesties
- [ ] Open een ticket in ticketportaal
- [ ] Scroll naar beneden
- [ ] AI suggesties widget verschijnt 🤖
- [ ] Suggesties zijn relevant
- [ ] Bronnen worden getoond

### Stap 10: Configuratie
- [ ] Check: `config/ai_config.php`
- [ ] `AI_ENABLED` = true
- [ ] `RAG_API_URL` = http://localhost:5005
- [ ] Beta users ingesteld (of leeg voor iedereen)

## Optioneel: Automatisering

### Scheduled Tasks (Optioneel)
- [ ] Run: `scripts/setup_scheduled_tasks.ps1`
- [ ] Daily sync task aangemaakt (2:00 AM)
- [ ] Hourly sync task aangemaakt
- [ ] Health monitor task aangemaakt (elke 30 min)

### Windows Services (Optioneel)
- [ ] RAG API als service geïnstalleerd
- [ ] Service start automatisch bij boot
- [ ] Service recovery geconfigureerd

## Troubleshooting Checklist

Als iets niet werkt, check:

- [ ] Python versie is 3.11+ (`python --version`)
- [ ] Alle Python packages geïnstalleerd (`pip list`)
- [ ] spaCy model gedownload (`python -m spacy info nl_core_news_lg`)
- [ ] Ollama draait (`ollama list`)
- [ ] MySQL draait (`Get-Service MySQL*`)
- [ ] Poort 5005 is vrij (`netstat -an | findstr 5005`)
- [ ] Firewall blokkeert niet
- [ ] Logs gecontroleerd (`ai_module/logs/`)

## Performance Check

Na 24 uur gebruik:

- [ ] AI Dashboard bekeken
- [ ] Success rate > 90%
- [ ] Gemiddelde response tijd < 5 seconden
- [ ] CPU gebruik < 80%
- [ ] RAM gebruik < 80%
- [ ] Disk space > 20GB vrij
- [ ] Geen errors in logs

## Onderhoud Schema

### Dagelijks (Automatisch)
- [ ] Sync draait om 2:00 AM
- [ ] Health monitor checkt elke 30 min
- [ ] Logs worden geschreven

### Wekelijks (Handmatig)
- [ ] Check AI Dashboard statistieken
- [ ] Bekijk logs voor errors
- [ ] Check disk space

### Maandelijks (Handmatig)
- [ ] Archiveer oude logs
- [ ] Review AI performance
- [ ] Update dependencies indien nodig

## Notities

Schrijf hier je bevindingen:

```
Installatie datum: _______________

Problemen ondervonden:
_________________________________
_________________________________
_________________________________

Oplossingen:
_________________________________
_________________________________
_________________________________

Performance na 1 week:
_________________________________
_________________________________
_________________________________
```

## Contact

Bij problemen, stuur:
1. ✅ Deze checklist (wat is afgevinkt?)
2. 📋 Output van `python verify_installation.py`
3. 📸 Screenshot van error
4. 📄 Laatste 50 regels van log: `Get-Content logs\rag_api_*.log -Tail 50`

---

**Status:** 
- [ ] Installatie gestart
- [ ] Installatie voltooid
- [ ] Systeem in productie
- [ ] Team getraind

**Handtekening:** _______________  **Datum:** _______________
