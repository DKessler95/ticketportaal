# AI Chat Assistant - K&K Ticketportaal

## Overzicht

De AI Chat Assistant is een intelligente chatbot interface die medewerkers van Kruit & Kramer helpt met vragen over systemen, procedures, tickets en bedrijfsinformatie.

## Drie Toegangsniveaus

### 1. User Level (`user/ai_assistant.php`)
**Toegang voor:** Eindgebruikers

**Mogelijkheden:**
- Vragen over procedures en systemen
- Toegang tot kennisbank artikelen
- Toegang tot CI items (hardware/software info)
- GEEN toegang tot tickets van andere gebruikers (privacy)

**Voorbeeldvragen:**
- "Hoe reset ik mijn wachtwoord?"
- "Mijn laptop start niet op, wat moet ik doen?"
- "Hoe vraag ik nieuwe software aan?"

### 2. Agent Level (`agent/ai_assistant.php`)
**Toegang voor:** Support agents

**Mogelijkheden:**
- Volledige toegang tot ALLE tickets
- Volledige toegang tot kennisbank
- Volledige toegang tot CI items
- Zie relaties tussen tickets, users en CI items
- Zoek in historische tickets en resoluties

**Voorbeeldvragen:**
- "Wat zijn veelvoorkomende laptop problemen?"
- "Hoe los ik printer verbindingsproblemen op?"
- "Welke Dell laptops hebben we in voorraad?"
- "Toon me tickets over netwerk problemen van deze week"

### 3. Admin Level (`admin/ai_assistant.php`)
**Toegang voor:** Administrators

**Mogelijkheden:**
- Alles wat agents kunnen
- Analytics en trends
- Management rapportages
- Strategische vragen
- K&K bedrijfsinformatie
- Chat export functionaliteit

**Voorbeeldvragen:**
- "Wat zijn de meest voorkomende ticket categorieën deze maand?"
- "Welke agents hebben de hoogste resolutie rate?"
- "Welke hardware moet binnenkort vervangen worden?"
- "Wat zijn de trends in software problemen?"
- "Wat zijn de standaard procedures voor nieuwe medewerkers?"

## Technische Architectuur

```
User Interface (PHP)
    ↓
Handler (PHP) - Validates session & permissions
    ↓
AIHelper Class - Manages RAG API calls
    ↓
RAG API (Python FastAPI) - Port 5005
    ↓
Hybrid Retrieval System
    ├── Vector Search (ChromaDB)
    ├── BM25 Keyword Search
    └── Knowledge Graph Traversal
    ↓
Ollama LLM (Llama 3.1 8B)
    ↓
Response with sources & confidence
```

## Features

### Real-time Chat Interface
- Modern chat UI met typing indicators
- Message history
- Timestamp voor elk bericht
- Auto-scroll naar nieuwe berichten

### Source Attribution
- Elke AI response toont bronnen
- Icons voor verschillende bron types (ticket/KB/CI)
- Confidence scores (Hoog/Gemiddeld/Laag)
- Relationship chains (hoe data gerelateerd is)

### Search Filters
- Toggle voor Tickets/KB/CI zoeken
- Agents en admins kunnen selecteren waar ze in willen zoeken
- Users hebben beperkte filters (geen tickets van anderen)

### Example Questions
- Vooraf gedefinieerde vragen per rol
- Klik om direct te gebruiken
- Helpt users om goede vragen te stellen

### Statistics Dashboard
- Live stats van database
- Aantal tickets, KB articles, CI items
- Voor admins: ook user counts

### Admin Extra Features
- **Chat Export**: Download chat geschiedenis als .txt
- **Clear Chat**: Wis chat geschiedenis
- **Analytics**: Toegang tot trends en patronen

## Bestandsstructuur

```
user/
├── ai_assistant.php              # User chat interface
└── ai_assistant_handler.php      # User request handler

agent/
├── ai_assistant.php              # Agent chat interface
├── ai_assistant_handler.php      # Agent request handler
└── ai_assistant_stats.php        # Stats API

admin/
├── ai_assistant.php              # Admin chat interface
├── ai_assistant_handler.php      # Admin request handler
└── ai_assistant_stats.php        # Stats API (extended)

includes/
└── ai_helper.php                 # AIHelper class (shared)
```

## Gebruik

### Voor Users
1. Log in op het ticketportaal
2. Ga naar "AI Assistent" in het menu
3. Stel je vraag in het tekstveld
4. Klik op "Verstuur" of druk op Enter
5. Wacht op het AI antwoord met bronnen

### Voor Agents
1. Log in als agent
2. Ga naar "AI Assistent" in het agent menu
3. Selecteer waar je in wilt zoeken (Tickets/KB/CI)
4. Stel je vraag
5. Bekijk antwoord met bronnen en relaties

### Voor Admins
1. Log in als admin
2. Ga naar "AI Assistent" in het admin menu
3. Gebruik advanced filters
4. Stel strategische of operationele vragen
5. Exporteer chat indien nodig voor rapportages

## Configuratie

### AIHelper Class Settings
```php
// In includes/ai_helper.php
private $rag_api_url = 'http://localhost:5005';
private $timeout = 30;  // seconds
```

### Access Control
- User: Alleen eigen tickets + KB + CI
- Agent: Alle tickets + KB + CI + relationships
- Admin: Alles + analytics + management queries

## Toekomstige Uitbreidingen

### Fase 1: Data Verrijking (NU)
- [ ] KB articles toevoegen met K&K specifieke info
- [ ] Bedrijfsprocessen documenteren
- [ ] Systeem handleidingen toevoegen
- [ ] Veelgestelde vragen uitbreiden

### Fase 2: Ecoro Integratie
- [ ] Product catalogi synchroniseren
- [ ] Prijsinformatie beschikbaar maken
- [ ] Voorraad status queries
- [ ] Bestelling tracking

### Fase 3: Advanced Features
- [ ] Multi-turn conversations (context behouden)
- [ ] User feedback (thumbs up/down)
- [ ] Suggested follow-up questions
- [ ] Voice input (spraakherkenning)
- [ ] Image upload voor visuele problemen

### Fase 4: Analytics & Learning
- [ ] Track welke vragen het meest gesteld worden
- [ ] Identificeer knowledge gaps
- [ ] Auto-generate KB articles uit succesvolle resoluties
- [ ] Personalized suggestions per user

## Monitoring

### Health Checks
- RAG API status: `http://localhost:5005/health`
- Ollama status: Geïntegreerd in health check
- ChromaDB status: Geïntegreerd in health check

### Logs
- User queries: Logged in PHP error log
- AI responses: Logged in Python RAG API
- Errors: Beide PHP en Python logs

### Metrics
- Response times
- Confidence scores
- Source types gebruikt
- Query success rate

## Troubleshooting

### "AI service is momenteel niet beschikbaar"
**Oorzaak:** RAG API is offline
**Oplossing:** 
```powershell
# Check service status
Get-Service TicketportaalRAG

# Restart if needed
Restart-Service TicketportaalRAG
```

### "Kan geen verbinding maken met de AI service"
**Oorzaak:** Network/firewall issue
**Oplossing:**
```powershell
# Test connection
curl http://localhost:5005/health

# Check firewall
netsh advfirewall firewall show rule name="TicketportaalRAG"
```

### Lage confidence scores
**Oorzaak:** Onvoldoende data of slechte data kwaliteit
**Oplossing:**
1. Voeg meer KB articles toe
2. Verbeter ticket beschrijvingen
3. Vul dynamic fields in
4. Run sync opnieuw: `python sync_tickets_to_vector_db.py`

### Geen bronnen gevonden
**Oorzaak:** Query te vaag of data niet gesynchroniseerd
**Oplossing:**
1. Wees specifieker in je vraag
2. Check of data gesynchroniseerd is
3. Verifieer ChromaDB collections

## Support

Voor vragen of problemen:
1. Check deze README
2. Check logs in `ai_module/logs/`
3. Test RAG API health endpoint
4. Contact IT admin

## Changelog

### v1.0.0 (2025-10-23)
- ✅ Initial release
- ✅ Three-tier access control (User/Agent/Admin)
- ✅ Real-time chat interface
- ✅ Source attribution
- ✅ Confidence scoring
- ✅ Relationship chains
- ✅ Search filters
- ✅ Example questions
- ✅ Statistics dashboard
- ✅ Chat export (admin only)
