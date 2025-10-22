# Requirements Document

## Introduction

Dit document beschrijft de requirements voor de implementatie van een volledig lokale RAG (Retrieval-Augmented Generation) AI module voor het ICT Ticketportaal van Kruit & Kramer. Het systeem moet 100% on-premise draaien zonder externe API's, met automatische data synchronisatie en intelligente suggesties voor agents en gebruikers.

## Glossary

- **RAG System**: Het complete Retrieval-Augmented Generation systeem bestaande uit Ollama, Llama 3.1, ChromaDB en FastAPI
- **Ollama Service**: De lokale LLM server die Llama modellen host
- **Vector Database**: ChromaDB database die embeddings opslaat voor semantic search
- **Sync Pipeline**: Het geautomatiseerde proces dat ticket/KB/CI data synchroniseert naar de Vector Database
- **RAG API**: De FastAPI service die query endpoints exposed voor PHP integratie
- **Embedding Model**: Het sentence-transformers model dat tekst omzet naar vector embeddings
- **AI Helper**: De PHP class die integratie verzorgt tussen ticketportaal en RAG API
- **Ticketportaal**: Het bestaande PHP-based support ticket systeem
- **Agent**: Een ICT medewerker die tickets behandelt
- **User**: Een eindgebruiker die tickets aanmaakt

## Requirements

### Requirement 1: Lokale AI Infrastructure

**User Story:** Als systeembeheerder wil ik dat alle AI componenten lokaal draaien, zodat gevoelige bedrijfsdata nooit het netwerk verlaat en er geen maandelijkse API kosten zijn.

#### Acceptance Criteria

1. WHEN het RAG System wordt geïnstalleerd, THEN SHALL het Ollama Service lokaal op de Windows Server draaien zonder externe internet verbindingen
2. WHEN een AI query wordt uitgevoerd, THEN SHALL het RAG System alle processing on-premise uitvoeren zonder data naar cloud services te sturen
3. WHEN het Embedding Model embeddings genereert, THEN SHALL het sentence-transformers model lokaal draaien zonder API calls naar externe services
4. WHEN de Vector Database wordt geraadpleegd, THEN SHALL ChromaDB alle data persistent opslaan op lokale disk storage
5. WHERE het systeem operationeel is, THE RAG System SHALL geen maandelijkse kosten genereren voor API usage

### Requirement 2: Automatische Data Synchronisatie

**User Story:** Als systeembeheerder wil ik dat ticket, KB en CI data automatisch gesynchroniseerd wordt naar de vector database, zodat AI suggesties altijd gebaseerd zijn op actuele informatie.

#### Acceptance Criteria

1. WHEN de Sync Pipeline dagelijks om 02:00 draait, THEN SHALL het systeem alle nieuwe en gewijzigde tickets van de laatste 24 uur synchroniseren naar de Vector Database
2. WHEN een ticket wordt aangemaakt of gewijzigd, THEN SHALL het systeem binnen 1 uur de wijziging reflecteren in de Vector Database via incremental sync
3. WHEN KB artikelen worden gepubliceerd, THEN SHALL de Sync Pipeline deze artikelen embedden en opslaan in de kb_collection
4. WHEN Configuration Items worden toegevoegd, THEN SHALL de Sync Pipeline deze items embedden en opslaan in de ci_collection
5. IF de Sync Pipeline faalt, THEN SHALL het systeem een error log genereren en een email alert sturen naar de systeembeheerder

### Requirement 3: Intelligent Query Processing

**User Story:** Als agent wil ik bij het bekijken van een ticket automatisch relevante suggesties zien, zodat ik sneller tot een oplossing kom.

#### Acceptance Criteria

1. WHEN een agent een ticket detail pagina opent, THEN SHALL het RAG System binnen 5 seconden een AI-gegenereerd antwoord tonen gebaseerd op de ticket beschrijving
2. WHEN het RAG API een query ontvangt, THEN SHALL het systeem de top 5 meest relevante vergelijkbare tickets retourneren met similarity scores
3. WHEN het RAG System suggesties genereert, THEN SHALL het systeem relevante KB artikelen includeren in de context voor het LLM
4. WHEN een query wordt verwerkt, THEN SHALL het Embedding Model de query tekst omzetten naar een vector embedding voor semantic search
5. WHERE de query resultaten worden gegenereerd, THE RAG API SHALL een gestructureerd JSON response retourneren met ai_answer, similar_tickets, similar_kb en similar_ci velden

### Requirement 4: PHP Integration Layer

**User Story:** Als developer wil ik een eenvoudige PHP interface hebben om AI functionaliteit te integreren, zodat ik snel AI features kan toevoegen aan bestaande paginas.

#### Acceptance Criteria

1. WHEN de AI Helper class wordt geïnstantieerd, THEN SHALL het systeem automatisch de health status van de RAG API checken
2. WHEN getSuggestions() wordt aangeroepen met ticket tekst, THEN SHALL de AI Helper een HTTP POST request maken naar het RAG API endpoint
3. IF de RAG API niet beschikbaar is, THEN SHALL de AI Helper gracefully degraderen en een error response retourneren zonder de pagina te breken
4. WHEN AI suggesties succesvol worden opgehaald, THEN SHALL de AI Helper een gestandaardiseerd array format retourneren met success flag en data
5. WHERE AI functionaliteit wordt gebruikt in ticket views, THE Ticketportaal SHALL de AI suggestion widget alleen tonen als isEnabled() true retourneert

### Requirement 5: Windows Service Management

**User Story:** Als systeembeheerder wil ik dat alle AI componenten als Windows Services draaien, zodat ze automatisch starten bij server reboot en 24/7 beschikbaar zijn.

#### Acceptance Criteria

1. WHEN de server opstart, THEN SHALL het Ollama Service automatisch starten als Windows Service met SERVICE_AUTO_START configuratie
2. WHEN de server opstart, THEN SHALL de RAG API automatisch starten als Windows Service genaamd TicketportaalRAG
3. IF een service crashed, THEN SHALL Windows automatisch proberen de service te herstarten binnen 1 minuut
4. WHEN een service draait, THEN SHALL het systeem stdout en stderr logs schrijven naar C:\TicketportaalAI\logs\
5. WHERE services operationeel zijn, THE RAG System SHALL health check endpoints exposen op /health voor monitoring

### Requirement 6: Performance en Resource Management

**User Story:** Als systeembeheerder wil ik dat het AI systeem efficiënt met resources omgaat, zodat de bestaande ticketportaal functionaliteit niet wordt beïnvloed.

#### Acceptance Criteria

1. WHEN het RAG System idle is, THEN SHALL de totale RAM usage niet meer dan 8GB bedragen
2. WHEN een AI query wordt verwerkt, THEN SHALL de response tijd minder dan 5 seconden bedragen voor 95% van de queries
3. WHEN de Sync Pipeline draait, THEN SHALL het systeem maximaal 50% van de beschikbare CPU cores gebruiken
4. IF de system load boven 80% CPU komt, THEN SHALL de RAG API nieuwe queries weigeren met een "System under heavy load" error
5. WHERE disk space onder 20GB komt, THE RAG System SHALL een warning email sturen naar de systeembeheerder

### Requirement 7: Monitoring en Alerting

**User Story:** Als systeembeheerder wil ik real-time inzicht in de status van het AI systeem, zodat ik proactief problemen kan oplossen voordat gebruikers impact ervaren.

#### Acceptance Criteria

1. WHEN een admin de AI dashboard pagina opent, THEN SHALL het systeem statistieken tonen voor aantal embedded tickets, KB artikelen en CI items
2. WHEN een Windows Service down gaat, THEN SHALL het health monitor script binnen 30 minuten een alert email sturen
3. WHEN de Sync Pipeline compleet is, THEN SHALL het systeem een log entry maken met timestamp, aantal gesynchroniseerde items en eventuele errors
4. IF disk space onder 20GB komt, THEN SHALL het monitoring systeem een warning email sturen naar de systeembeheerder
5. WHERE services operationeel zijn, THE AI dashboard SHALL de laatste sync tijd en service status (running/stopped) tonen

### Requirement 8: Staged Deployment en Rollback

**User Story:** Als systeembeheerder wil ik het AI systeem gefaseerd kunnen uitrollen en snel kunnen terugdraaien, zodat ik risico's minimaliseer bij productie deployment.

#### Acceptance Criteria

1. WHEN de AI module wordt gedeployed, THEN SHALL het systeem een feature flag hebben in config.php om AI functionaliteit te enable/disable
2. WHEN de feature flag op false staat, THEN SHALL de Ticketportaal geen AI widgets tonen en geen RAG API calls maken
3. IF kritieke problemen optreden, THEN SHALL de systeembeheerder binnen 5 minuten het systeem kunnen terugdraaien door services te stoppen
4. WHEN een rollback wordt uitgevoerd, THEN SHALL de Ticketportaal blijven functioneren zonder AI features alsof de module nooit geïnstalleerd was
5. WHERE staged rollout wordt uitgevoerd, THE RAG System SHALL configureerbaar zijn om alleen voor specifieke user IDs enabled te zijn

### Requirement 9: VM Testing Environment

**User Story:** Als developer wil ik het complete AI systeem kunnen testen in een VM omgeving, zodat ik performance impact kan meten voordat ik naar productie deploy.

#### Acceptance Criteria

1. WHEN een VM wordt opgezet voor testing, THEN SHALL de VM minimaal 16GB RAM, 8 CPU cores en 60GB disk space hebben
2. WHEN load tests worden uitgevoerd, THEN SHALL het monitoring script CPU, RAM en disk I/O metrics loggen naar CSV files
3. WHEN 1000 tickets worden gesynchroniseerd, THEN SHALL de Sync Pipeline binnen 15 minuten compleet zijn
4. WHEN 30 concurrent RAG queries worden uitgevoerd, THEN SHALL het systeem een success rate van minimaal 95% behalen
5. WHERE performance tests compleet zijn, THE testing framework SHALL een rapport genereren met CPU impact, RAM impact en response times

### Requirement 10: Security en Privacy

**User Story:** Als compliance officer wil ik dat alle data processing lokaal gebeurt volgens GDPR richtlijnen, zodat we voldoen aan privacy wetgeving.

#### Acceptance Criteria

1. WHEN embeddings worden gegenereerd, THEN SHALL de Vector Database alleen vector representations opslaan zonder mogelijkheid tot reverse engineering naar originele tekst
2. WHEN de RAG API draait, THEN SHALL het systeem alleen toegankelijk zijn op localhost of intern LAN netwerk, niet vanaf internet
3. WHEN data wordt verwerkt, THEN SHALL alle processing on-premise gebeuren zonder data naar externe services te sturen
4. IF een data breach wordt gedetecteerd, THEN SHALL het systeem audit logs hebben van alle API calls met timestamps en user IDs
5. WHERE gevoelige ticket data wordt gebruikt, THE RAG System SHALL alleen toegankelijk zijn voor geauthenticeerde gebruikers via PHP session management

### Requirement 11: Toekomstige Uitbreidingen

**User Story:** Als product owner wil ik dat het systeem voorbereid is op toekomstige integraties, zodat we later eenvoudig product catalogus en Ecoro data kunnen toevoegen.

#### Acceptance Criteria

1. WHEN nieuwe data bronnen worden toegevoegd, THEN SHALL de Sync Pipeline architectuur modulair zijn om nieuwe sync functies toe te voegen
2. WHEN product catalogus data beschikbaar komt, THEN SHALL het systeem een products_collection kunnen aanmaken in ChromaDB
3. WHEN Ecoro integratie wordt geïmplementeerd, THEN SHALL de RAG API queries kunnen uitvoeren over multiple collections tegelijk
4. WHERE nieuwe embeddings worden toegevoegd, THE Vector Database SHALL schaalbaar zijn tot minimaal 100.000 embedded documents
5. IF SHD koppeling wordt geïmplementeerd, THEN SHALL de RAG System een unified search interface bieden over alle systemen

### Requirement 12: Documentation en Maintenance

**User Story:** Als systeembeheerder wil ik complete documentatie hebben voor installatie en troubleshooting, zodat ik het systeem zelfstandig kan beheren.

#### Acceptance Criteria

1. WHEN de deployment compleet is, THEN SHALL er een admin manual beschikbaar zijn met installatie stappen en configuratie opties
2. WHEN troubleshooting nodig is, THEN SHALL de documentatie common issues bevatten met oplossingen voor Ollama, RAG API en sync problemen
3. WHEN maintenance wordt uitgevoerd, THEN SHALL er een maintenance schedule gedocumenteerd zijn met backup procedures
4. WHERE logs worden gegenereerd, THE RAG System SHALL gestructureerde log formats gebruiken met timestamps, log levels en error messages
5. IF een service faalt, THEN SHALL de troubleshooting guide stappen bevatten om de service te diagnosticeren en herstarten
