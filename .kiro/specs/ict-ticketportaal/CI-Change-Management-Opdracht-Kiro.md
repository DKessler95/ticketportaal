# ICT Ticketportaal Uitbreiding - CI & Change Management Module

## Opdracht voor Kiro

Hoi Kiro,

De ticketportaal loopt goed! Nu willen we het systeem uitbreiden met twee belangrijke ITIL-gebaseerde modules: **Configuration Item (CI) Management** en **Change Management**. Hiermee kunnen we hardware-uitgaven en IT-wijzigingen professioneel bijhouden.

**Belangrijkste doel:** Simpel en praktisch houden - geen overbodige complexiteit.

---

## 📦 MODULE 1: Configuration Item (CI) Management

### Doel
Een overzichtelijke database om alle ICT-middelen (hardware, software, licenties) te beheren en financiële instroom/uitstroom bij te houden.

### Wat moet erin?

#### 1.1 CI Overzichtspagina (`/admin/ci_overview.php`)

**Tabel met kolommen:**
- CI-nummer (automatisch, bijv. CI-2025-001)
- Type (Hardware / Software / Licentie / Overig)
- Naam/Omschrijving
- Merk & Model
- Serienummer
- Status (In gebruik / In voorraad / Defect / Afgeschreven)
- Eigenaar (gekoppeld aan gebruiker)
- Afdeling
- Locatie (bijv. Kantoor, Magazijn, Thuis)
- Aankoopdatum
- Aankoopprijs (€)
- Garantie vervaldatum
- Notities

**Functionaliteit:**
- ✅ Zoeken op serienummer, eigenaar, type
- ✅ Filteren op status, categorie, afdeling
- ✅ Sorteerbaar op datum, prijs
- ✅ Excel/CSV export voor financiële rapportage
- ✅ "Nieuw CI toevoegen" knop

#### 1.2 CI Detailpagina (`/admin/ci_detail.php?id=X`)

**Weergave:**
- Alle CI-gegevens in duidelijk overzicht
- **Geschiedenis/Log:** Wie heeft wanneer wat aangepast
- **Gekoppelde tickets:** Toon alle tickets gerelateerd aan dit CI
- **Gekoppelde changes:** Toon alle wijzigingen met dit CI
- **Attachments:** Upload facturen, handleidingen, garantiebewijzen

**Acties:**
- Bewerken (Update info)
- Status wijzigen (bijv. "In gebruik" → "Defect")
- Archiveren/Verwijderen
- Link naar ticket of change

#### 1.3 CI Formulier (Nieuw/Bewerken)

**Velden:**
```
- Type: Dropdown (Hardware, Software, Licentie, Overig)
- Categorie: Dropdown (Laptop, Desktop, Monitor, Printer, Server, Router, Telefoon, Licentie, etc.)
- Merk: Tekstveld
- Model: Tekstveld
- Naam/Beschrijving: Tekstveld
- Serienummer: Tekstveld (unique)
- Eigenaar: Dropdown (gebruikers uit users tabel)
- Afdeling: Dropdown of tekstveld
- Locatie: Tekstveld
- Status: Dropdown (In gebruik, In voorraad, Defect, Afgeschreven)
- Aankoopdatum: Datepicker
- Aankoopprijs: Getal (€)
- Leverancier: Tekstveld
- Garantie tot: Datepicker
- Notities: Textarea
```

**Validatie:**
- Verplicht: Type, Naam, Status
- Serienummer moet uniek zijn
- Prijs moet positief getal zijn

---

## 🔄 MODULE 2: Change Management

### Doel
Gestructureerd wijzigingen (updates, nieuwe features, infrastructuurwijzigingen) plannen, goedkeuren en documenteren volgens ITIL-principes.

### Wat moet erin?

#### 2.1 Change Overzichtspagina (`/admin/changes.php`)

**Tabel met kolommen:**
- Change-nummer (automatisch, bijv. CHG-2025-001)
- Titel
- Aanvrager (wie heeft het aangevraagd)
- Type (Feature / Patch / Hardware / Software / Netwerk / Infrastructuur)
- Prioriteit (Laag / Normaal / Hoog / Urgent)
- Impact (Laag / Middel / Hoog)
- Status (Nieuw / In beoordeling / Goedgekeurd / Ingepland / Geïmplementeerd / Afgewezen)
- Geplande datum
- Verantwoordelijke (agent/admin)
- Aanmaakdatum

**Functionaliteit:**
- ✅ Filteren op status, type, prioriteit
- ✅ Zoeken op titel, nummer
- ✅ Status badges met kleuren (groen=goedgekeurd, rood=afgewezen, etc.)
- ✅ "Nieuwe Change aanmaken" knop

#### 2.2 Change Detailpagina (`/admin/change_detail.php?id=X`)

**Secties:**

**1. Change Informatie**
- Nummer, Titel, Type, Prioriteit, Impact
- Aanvrager, Verantwoordelijke
- Status met statusgeschiedenis (wie, wanneer, van/naar)

**2. Change Beschrijving**
- Wat wordt er aangepast?
- Waarom is deze change nodig?
- Wat is het verwachte resultaat?

**3. Impactanalyse**
- Welke systemen/diensten worden beïnvloed?
- Hoeveel gebruikers worden geraakt?
- Zijn er downtimes?
- Risico's (Laag / Middel / Hoog)

**4. Implementatieplan**
- Stap-voor-stap plan voor uitvoering
- Wie doet wat?
- Tijdlijn (start/eind)
- Benodigde middelen

**5. Rollback Plan**
- Hoe maken we de change ongedaan als het fout gaat?
- Stappen voor terugdraaien

**6. Gekoppelde Items**
- Gekoppelde CI's (welke hardware/software betreft dit?)
- Gekoppelde tickets (is er een incident/request die dit veroorzaakte?)
- Attachments (documenten, screenshots, configuraties)

**7. Goedkeuring & Review**
- Change Advisory Board (CAB) leden kunnen reviewen
- Goedkeuren/Afwijzen knop (alleen voor Admin/Change Manager)
- Post-Implementation Review (na implementatie):
  - Succesvol? (Ja/Nee)
  - Lessons learned
  - Vervolgacties

**Acties:**
- Bewerken (Update change info)
- Status wijzigen workflow
- Change Report genereren (PDF)

#### 2.3 Change Formulier (Nieuw/Bewerken)

**Tab 1: Basis Informatie**
```
- Titel: Tekstveld (verplicht)
- Type: Dropdown (Feature, Patch, Hardware, Software, Netwerk, Infrastructuur, Overig)
- Prioriteit: Dropdown (Laag, Normaal, Hoog, Urgent)
- Impact: Dropdown (Laag, Middel, Hoog)
- Aanvrager: Auto-ingevuld (huidige gebruiker)
- Verantwoordelijke: Dropdown (agents/admins)
- Geplande datum: Datepicker
```

**Tab 2: Beschrijving & Motivatie**
```
- Beschrijving: Textarea (Wat wordt aangepast?)
- Reden: Textarea (Waarom is dit nodig?)
- Verwacht resultaat: Textarea
```

**Tab 3: Impact & Risico**
```
- Beïnvloede systemen: Textarea
- Aantal gebruikers: Getal
- Downtime verwacht: Checkbox + duur in minuten
- Risico-inschatting: Textarea
```

**Tab 4: Implementatie**
```
- Implementatieplan: Rich text editor (stappen)
- Rollback plan: Rich text editor
- Benodigde middelen: Textarea
```

**Tab 5: Koppelingen**
```
- Gekoppelde CI's: Multi-select (uit CI database)
- Gekoppelde tickets: Multi-select (uit tickets)
- Attachments: File upload
```

---

## 🗄️ Database Schema

### Tabel: `configuration_items`

```sql
CREATE TABLE configuration_items (
    ci_id INT PRIMARY KEY AUTO_INCREMENT,
    ci_number VARCHAR(50) UNIQUE NOT NULL, -- bijv. CI-2025-001
    type ENUM('Hardware','Software','Licentie','Overig') NOT NULL,
    category VARCHAR(100),
    brand VARCHAR(100),
    model VARCHAR(100),
    name VARCHAR(255) NOT NULL,
    serial_number VARCHAR(255) UNIQUE,
    status ENUM('In gebruik','In voorraad','Defect','Afgeschreven') DEFAULT 'In gebruik',
    owner_id INT NULL,
    department VARCHAR(100),
    location VARCHAR(255),
    purchase_date DATE,
    purchase_price DECIMAL(10,2),
    supplier VARCHAR(255),
    warranty_expiry DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (owner_id) REFERENCES users(user_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

### Tabel: `ci_history`

```sql
CREATE TABLE ci_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    ci_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('created','updated','status_changed','deleted') NOT NULL,
    field_changed VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

### Tabel: `changes`

```sql
CREATE TABLE changes (
    change_id INT PRIMARY KEY AUTO_INCREMENT,
    change_number VARCHAR(50) UNIQUE NOT NULL, -- bijv. CHG-2025-001
    title VARCHAR(255) NOT NULL,
    requested_by INT NOT NULL,
    assigned_to INT NULL,
    type ENUM('Feature','Patch','Hardware','Software','Netwerk','Infrastructuur','Overig') DEFAULT 'Overig',
    priority ENUM('Laag','Normaal','Hoog','Urgent') DEFAULT 'Normaal',
    impact ENUM('Laag','Middel','Hoog') DEFAULT 'Middel',
    status ENUM('Nieuw','In beoordeling','Goedgekeurd','Ingepland','Geïmplementeerd','Afgewezen') DEFAULT 'Nieuw',
    
    -- Beschrijving
    description TEXT,
    reason TEXT,
    expected_result TEXT,
    
    -- Impact
    affected_systems TEXT,
    affected_users INT,
    downtime_expected BOOLEAN DEFAULT FALSE,
    downtime_duration INT, -- in minuten
    risk_assessment TEXT,
    
    -- Implementatie
    implementation_plan TEXT,
    rollback_plan TEXT,
    resources_needed TEXT,
    
    -- Datums
    planned_date DATE,
    implemented_date DATE NULL,
    
    -- Review
    post_implementation_success BOOLEAN NULL,
    post_implementation_notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requested_by) REFERENCES users(user_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id)
);
```

### Tabel: `change_ci_relations`

```sql
CREATE TABLE change_ci_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    change_id INT NOT NULL,
    ci_id INT NOT NULL,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE
);
```

### Tabel: `change_history`

```sql
CREATE TABLE change_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    change_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('created','status_changed','updated','approved','rejected','implemented') NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    comment TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_id) REFERENCES changes(change_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

---

## 🔗 Integratie met Bestaand Ticketsysteem

### Ticket → CI Koppeling
- Bij ticket aanmaken/bewerken: Dropdown om CI te selecteren
- Ticket detail pagina: Toon gekoppelde CI info
- CI detail pagina: Toon alle tickets gerelateerd aan dit CI

### Ticket → Change Koppeling
- Tickets kunnen leiden tot changes (bijv. feature request)
- Link "Maak Change aan" op ticket detail pagina
- Change kan ontstaan uit meerdere tickets

**Nieuwe tabel:**
```sql
CREATE TABLE ticket_ci_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    ci_id INT NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (ci_id) REFERENCES configuration_items(ci_id) ON DELETE CASCADE
);

CREATE TABLE ticket_change_relations (
    relation_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    change_id INT NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (change_id) REFERENCES changes(change_id) ON DELETE CASCADE
);
```

---

## 📊 Change Report Genereren

### Functionaliteit: `/admin/change_report.php?id=X`

**Output: PDF of HTML rapport met:**

**Header:**
- Logo Kruit & Kramer
- "Change Management Report"
- Change nummer + datum

**Secties:**
1. **Executive Summary**
   - Titel, Type, Prioriteit, Impact
   - Status en geplande datum
   - Aanvrager en verantwoordelijke

2. **Change Beschrijving**
   - Wat, Waarom, Verwacht resultaat

3. **Impactanalyse**
   - Beïnvloede systemen en gebruikers
   - Risico's en downtime

4. **Implementatieplan**
   - Stap voor stap
   - Tijdlijn
   - Benodigde resources

5. **Rollback Plan**
   - Hoe ongedaan maken

6. **Betrokken CI's**
   - Tabel met gekoppelde configuratie items

7. **Goedkeuringsstatus**
   - Wie heeft goedgekeurd/afgewezen
   - Datum en opmerkingen

8. **Post-Implementation Review** (na implementatie)
   - Succesvol? Ja/Nee
   - Lessons learned
   - Aanbevelingen

**Technisch:**
- Gebruik library zoals TCPDF of DomPDF voor PDF generatie
- Of eerst HTML view, met "Print/Export PDF" optie

---

## 🎨 UI/UX Richtlijnen

### Navigatie
**Admin Menu uitbreiden:**
```
📊 Dashboard
🎫 Tickets
👥 Gebruikers
📁 Categorieën
📚 Knowledge Base
🔧 Configuratie Items (NIEUW)
🔄 Change Management (NIEUW)
📈 Rapportages
```

### Kleurenschema voor Status
**CI Status:**
- In gebruik: Groen
- In voorraad: Blauw
- Defect: Rood
- Afgeschreven: Grijs

**Change Status:**
- Nieuw: Lichtblauw
- In beoordeling: Oranje
- Goedgekeurd: Groen
- Ingepland: Paars
- Geïmplementeerd: Donkergroen
- Afgewezen: Rood

### Dashboard Widgets (optioneel)
- Totaal aantal actieve CI's
- Totale waarde hardware (€)
- CI's met verlopende garantie (30 dagen)
- Open changes (status: Nieuw, In beoordeling)
- Recent geïmplementeerde changes

---

## 📋 Implementatie Checklist voor Kiro

### Fase 1: Database Setup (Week 1)
- [ ] Nieuwe tabellen aanmaken (CI's, Changes, History, Relations)
- [ ] Test data invoeren (10 dummy CI's, 5 dummy changes)
- [ ] Database migrations script maken

### Fase 2: CI Management (Week 2-3)
- [ ] CI overzicht pagina met tabel en filters
- [ ] CI detail pagina met geschiedenis
- [ ] CI formulier (create/edit)
- [ ] CI history logging implementeren
- [ ] CI search en export functionaliteit

### Fase 3: Change Management (Week 4-5)
- [ ] Change overzicht pagina
- [ ] Change detail pagina met alle secties
- [ ] Change formulier (tabs)
- [ ] Change status workflow
- [ ] Change history logging

### Fase 4: Integratie (Week 6)
- [ ] CI koppelen aan tickets
- [ ] Change koppelen aan tickets
- [ ] Change Report generator (PDF)
- [ ] Dashboard widgets toevoegen

### Fase 5: Testing & Refinement (Week 7)
- [ ] End-to-end tests (admin workflow)
- [ ] User acceptance testing
- [ ] Performance optimalisatie
- [ ] Security audit

---

## 🔒 Security & Permissions

### Toegangsniveaus:
- **User:** Kan alleen eigen gekoppelde CI's zien (read-only)
- **Agent:** Kan CI's en Changes bekijken, Changes aanmaken
- **Admin:** Full CRUD op CI's en Changes, kan goedkeuren/afwijzen

### Audit Trail:
- Alle wijzigingen worden gelogd in history tabellen
- Wie, wat, wanneer voor compliance

---

## 📚 Referenties & Best Practices

**ITIL Principes toegepast:**
- CI Management volgt ITIL Configuration Management best practices
- Change Management volgt ITIL Change Enablement proces
- Eenvoudig gehouden voor MKB zonder CAB-meetings (goedkeuring door admin)

**Bronnen gebruikt:**
- ITIL 4 Configuration Management guidelines
- ITIL Change Management process flow
- Best practices voor CMDB in kleine bedrijven

---

## 🎯 Deliverables

**Wat moet klaar zijn:**
1. Werkende CI Management module (CRUD + overzichten)
2. Werkende Change Management module (workflow + rapportage)
3. Integratie met bestaand ticketsysteem
4. Change Report PDF generator
5. Documentatie voor gebruikers (handleiding)
6. Database migrations + seed data

---

## 💡 Tips voor Implementatie

**Houd het simpel:**
- Gebruik Bootstrap componenten voor snelle UI
- Hergebruik bestaande classes (Database, User, etc.)
- Copy/paste code structuur van ticketsysteem waar mogelijk

**Focus op gebruiksvriendelijkheid:**
- Duidelijke labels en help-teksten
- Visuele feedback (success/error messages)
- Intuïtieve workflows

**Denk aan schaalbaarheid:**
- Gebruik prepared statements
- Index belangrijke kolommen (ci_number, serial_number, change_number)
- Paginering voor grote lijsten (>100 items)

---

**Succes Kiro! Als je vragen hebt of ergens vastloopt, laat het weten!**

**Prioriteit:** Eerst CI Management werkend krijgen, dan Change Management, dan integraties.

**Deadline inschatting:** 6-7 weken voor volledige module (afhankelijk van complexiteit)