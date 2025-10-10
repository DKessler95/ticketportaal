# ICT Ticketportaal Programmeer Opdracht voor Kiro

## Project Overzicht

**Opdrachtgever:** Kruit & Kramer + eigen bedrijf  
**Developer:** Kiro  
**Doel:** Een volledig ICT ticketportaal ontwikkelen voor het professioneel beheren van ICT-problemen

## Huidige Situatie

Momenteel worden ICT-problemen afgehandeld via:
- E-mails naar ict@kruit-en-kramer.nl
- Telefonische meldingen
- Geen ticketlog of knowledge base aanwezig
- Geen tracking van resolutietijden

## Projectdoelstellingen

### Primaire Doelen
1. **Ticketportaal**: Een webgebaseerd systeem voor het aanmaken en beheren van tickets
2. **Dubbele toegang**: Via website login én e-mail (ict@kruit-en-kramer.nl)
3. **Ticket management**: Volledig beheer van ticket lifecycle
4. **Knowledge Base**: Kennisbank voor veelvoorkomende problemen
5. **Rapportage**: Overzichten en statistieken

### Secundaire Doelen
- Gebruikersvriendelijke interface
- Mobiele compatibiliteit
- Automatische notificaties
- SLA monitoring

## Technische Specificaties

### Tech Stack
- **Backend**: PHP 7.4+ met PDO
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **E-mail**: PHP Mail functionaliteit + Collax integratie
- **Security**: Bcrypt password hashing, CSRF bescherming

### Database Schema

#### Tabel: users
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    role ENUM('user', 'agent', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);
```

#### Tabel: tickets
```sql
CREATE TABLE tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT,
    assigned_agent_id INT NULL,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'pending', 'resolved', 'closed') DEFAULT 'open',
    source ENUM('web', 'email', 'phone') DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolution TEXT NULL,
    satisfaction_rating INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (assigned_agent_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);
```

#### Tabel: categories
```sql
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    default_priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    sla_hours INT DEFAULT 24,
    is_active BOOLEAN DEFAULT TRUE
);
```

#### Tabel: ticket_comments
```sql
CREATE TABLE ticket_comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

#### Tabel: knowledge_base
```sql
CREATE TABLE knowledge_base (
    kb_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    tags TEXT,
    author_id INT,
    views INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (author_id) REFERENCES users(user_id)
);
```

## Functionele Requirements

### 1. Gebruikersbeheer
- **Registratie/Login systeem**
  - Veilige wachtwoord versleuteling (bcrypt)
  - Session management
  - "Wachtwoord vergeten" functionaliteit
  - Gebruikersrollen (user, agent, admin)

### 2. Ticket Aanmaak
- **Via Website**
  - Inlogformulier voor geregistreerde gebruikers
  - Categorieën dropdown (Hardware, Software, Netwerk, Account, etc.)
  - Prioriteit selectie
  - File upload mogelijkheid
  - Auto-gegenereerd ticketnummer (KK-YYYY-XXXX)

- **Via E-mail**
  - Parsing van inkomende e-mails naar ict@kruit-en-kramer.nl
  - Automatische ticket aanmaak
  - Herkenning van bestaande klanten via e-mailadres
  - Auto-reply met ticketnummer

### 3. Ticket Management
- **Agent Dashboard**
  - Overzicht van alle tickets
  - Filters (status, prioriteit, categorie, datum)
  - Ticket toewijzing aan agents
  - Status updates
  - Interne notities

- **Gebruiker Portal**
  - Mijn tickets overzicht
  - Ticket status tracking
  - Commentaar toevoegen
  - Satisfaction rating na oplossing

### 4. Knowledge Base
- **Publieke kennisbank**
  - Zoekfunctionaliteit
  - Categorieën
  - FAQ sectie
  - Stap-voor-stap handleidingen
  - Video embeddings

- **Agent kennisbank**
  - Interne documentatie
  - Oplossingssjablonen
  - Escalatieprocedures

### 5. Notificaties
- **E-mail notificaties**
  - Nieuwe ticket bevestiging
  - Status wijzigingen
  - Agent toewijzing
  - Oplossing notification

- **Dashboard notificaties**
  - Real-time updates
  - Overdue tickets
  - SLA warnings

### 6. Rapportage
- **Dashboards**
  - Ticket volume per periode
  - Gemiddelde resolutietijd
  - Agent performance
  - Satisfaction scores
  - Categorie analyse

## Bestandsstructuur

```
/ticketportaal/
│
├── /assets/
│   ├── /css/
│   │   ├── style.css
│   │   └── admin.css
│   ├── /js/
│   │   ├── main.js
│   │   └── admin.js
│   └── /images/
│       └── logo.png
│
├── /config/
│   ├── database.php
│   ├── config.php
│   └── email.php
│
├── /includes/
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── functions.php
│
├── /classes/
│   ├── Database.php
│   ├── User.php
│   ├── Ticket.php
│   ├── EmailHandler.php
│   └── KnowledgeBase.php
│
├── /admin/
│   ├── index.php
│   ├── tickets.php
│   ├── users.php
│   ├── categories.php
│   ├── knowledge_base.php
│   └── reports.php
│
├── /agent/
│   ├── dashboard.php
│   ├── my_tickets.php
│   └── knowledge_base.php
│
├── /user/
│   ├── dashboard.php
│   ├── create_ticket.php
│   ├── my_tickets.php
│   └── knowledge_base.php
│
├── /api/
│   ├── tickets.php
│   └── email_handler.php
│
├── index.php
├── login.php
├── register.php
├── logout.php
├── knowledge_base.php
└── email_to_ticket.php
```

## Implementatie Fases

### Fase 1: Basis Setup (Week 1-2)
1. **Database setup**
   - Alle tabellen aanmaken
   - Test data invoeren
   - Database connectie klasse

2. **Authenticatie systeem**
   - Login/logout functionaliteit
   - Registratie formulier
   - Session management
   - Wachtwoord reset

3. **Basis ticket systeem**
   - Ticket aanmaak formulier
   - Simpel overzicht
   - Basis status updates

### Fase 2: Core Functionaliteit (Week 3-4)
1. **Advanced ticket management**
   - Categorieën systeem
   - Prioriteiten
   - Agent toewijzing
   - Commentaren systeem

2. **E-mail integratie**
   - E-mail parsing
   - Automatische ticket aanmaak
   - Reply functionaliteit

3. **Gebruikersrollen**
   - Admin panel
   - Agent dashboard
   - Gebruiker portal

### Fase 3: Knowledge Base & Rapportage (Week 5-6)
1. **Knowledge Base**
   - CRUD operaties
   - Zoekfunctionaliteit
   - Categorieën

2. **Rapportage systeem**
   - Basic dashboards
   - Statistieken
   - Export functionaliteit

3. **Notificatie systeem**
   - E-mail templates
   - Automatische verzending

### Fase 4: Polish & Deployment (Week 7-8)
1. **UI/UX verbetering**
   - Responsive design
   - Gebruiksvriendelijkheid
   - Bootstrap integratie

2. **Security & Performance**
   - Input validatie
   - CSRF bescherming
   - Performance optimalisatie

3. **Testing & Deployment**
   - Functionele tests
   - User acceptance testing
   - Live deployment

## Gebruikersverhalen

### Als ICT Medewerker wil ik:
- Een overzichtelijk dashboard met alle openstaande tickets
- Tickets kunnen toewijzen aan mezelf of collega's
- Interne notities kunnen toevoegen die klanten niet zien
- Snelle toegang tot knowledge base artikelen
- Rapportages kunnen genereren voor management

### Als Gebruiker wil ik:
- Eenvoudig een ticket kunnen aanmaken
- De status van mijn tickets kunnen volgen
- Commentaar kunnen toevoegen aan mijn tickets
- Toegang tot self-service knowledge base
- E-mail notificaties ontvangen bij updates

### Als Administrator wil ik:
- Alle systeem settings kunnen beheren
- Gebruikers en rollen kunnen beheren
- Rapportages en analytics kunnen inzien
- Knowledge base kunnen onderhouden
- SLA instellingen kunnen aanpassen

## Security Overwegingen

1. **Input Validatie**
   - Alle formulier inputs sanitizen
   - SQL injection preventie via prepared statements
   - XSS bescherming

2. **Authentication & Authorization**
   - Sterke wachtwoord vereisten
   - Session timeout
   - Role-based access control

3. **Data Bescherming**
   - GDPR compliance
   - Data encryptie voor gevoelige informatie
   - Backup strategie

## Performance Overwegingen

1. **Database Optimalisatie**
   - Juiste indexing
   - Query optimalisatie
   - Connection pooling

2. **Caching**
   - Session caching
   - Knowledge base caching
   - Static asset caching

3. **Scalabiliteit**
   - Modulaire code structuur
   - API-ready architectuur
   - Load balancing voorbereiding

## Testing Strategie

### Unit Tests
- Database klassen
- User authenticatie
- Ticket operations

### Integration Tests
- E-mail functionaliteit
- File upload
- Notification systeem

### User Acceptance Tests
- Complete user workflows
- Cross-browser testing
- Mobile responsiveness

## Deployment Checklist

- [ ] Database migratie scripts
- [ ] Production configuratie files
- [ ] SSL certificaat installatie
- [ ] E-mail server configuratie (Collax)
- [ ] Backup procedures
- [ ] Monitoring setup
- [ ] User training materiaal

## Onderhoud & Support

### Dagelijks
- Ticket queue monitoring
- System performance check

### Wekelijks
- Database backup verificatie
- Usage statistics review

### Maandelijks
- Security updates
- Performance optimalisatie
- User feedback verwerking

## Success Criteria

1. **Functionaliteit**
   - Alle tickets worden correct gelogd en getrackt
   - E-mail integratie werkt betrouwbaar
   - Knowledge base wordt actief gebruikt

2. **Performance**
   - Pagina laadtijden < 2 seconden
   - 99.9% uptime
   - Geen dataloss

3. **User Adoption**
   - >90% van ICT verzoeken via het systeem
   - Positieve user feedback
   - Verminderde telefonische onderbrekingen

## Uitbreidingsmogelijkheden Toekomst

1. **Mobile App**
   - Native iOS/Android apps
   - Push notificaties

2. **Advanced Analytics**
   - Machine learning voor ticket categorisatie
   - Predictive maintenance alerts

3. **Integraties**
   - Active Directory koppeling
   - Asset management systeem
   - Chat/Teams integratie

4. **Workflow Automation**
   - Auto-assignment regels
   - Escalatie workflows
   - SLA automation

---

**Start Datum:** [In te vullen]  
**Geschatte Oplevering:** 8 weken na start  
**Budget:** [In te vullen]  
**Resources:** Kiro (lead developer) + eventuele ondersteuning