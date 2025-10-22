# Database Migraties

Deze map bevat alle database migratie scripts voor het ICT Ticketportaal.

## Hoe migraties uitvoeren

### Optie 1: Automatisch via PHP Script (AANBEVOLEN)
1. Open je browser
2. Ga naar: `http://localhost/ticketportaal/database/run_migrations.php`
3. Het script voert automatisch alle migraties uit
4. Bekijk de resultaten op het scherm
5. **BELANGRIJK:** Verwijder of hernoem `run_migrations.php` na gebruik voor beveiliging

### Optie 2: Handmatig via phpMyAdmin
1. Open phpMyAdmin in je browser: `http://localhost/phpmyadmin`
2. Selecteer de database `ticketportaal` in het linker menu
3. Klik op de tab "SQL" bovenaan
4. Open een migratie bestand (bijv. `add_templates_table.sql`) in een teksteditor
5. Kopieer de volledige inhoud
6. Plak de SQL code in het SQL venster van phpMyAdmin
7. Klik op "Go" of "Uitvoeren" onderaan
8. Herhaal voor elk migratie bestand

### Optie 3: Via MySQL Command Line
```bash
# Open Command Prompt (CMD)
cd C:\xampp\mysql\bin

# Voer migratie uit
mysql -u root -p ticketportaal < "C:\xampp\htdocs\ticketportaal\database\migrations\add_templates_table.sql"

# Herhaal voor andere migraties
mysql -u root -p ticketportaal < "C:\xampp\htdocs\ticketportaal\database\migrations\add_review_comment.sql"
mysql -u root -p ticketportaal < "C:\xampp\htdocs\ticketportaal\database\migrations\add_category_fields.sql"
```

## Beschikbare Migraties

### 1. add_templates_table.sql
**Beschrijving:** Voegt template systeem toe voor ticket resolutions
**Features:**
- Template beheer voor admins
- Standaard templates voor veelvoorkomende oplossingen
- Ondersteuning voor resolution, comment en email templates

**Tabellen:**
- `ticket_templates` - Opslag van templates

### 2. add_review_comment.sql
**Beschrijving:** Voegt comment veld toe aan user reviews
**Features:**
- Users kunnen feedback geven bij hun rating
- Admins kunnen alle reviews met comments bekijken

**Wijzigingen:**
- Voegt `satisfaction_comment` kolom toe aan `tickets` tabel
- Voegt index toe voor betere performance

### 3. add_category_fields.sql
**Beschrijving:** Dynamische velden per categorie (ITIL standaard)
**Features:**
- Categorie-specifieke velden (bijv. hardware type, software licentie)
- Flexibel veld systeem met verschillende types
- Voorbeeldvelden voor Hardware, Software, Account en Network

**Tabellen:**
- `category_fields` - Definitie van velden per categorie
- `ticket_field_values` - Opgeslagen waarden per ticket

## Volgorde van uitvoering

De migraties moeten in deze volgorde worden uitgevoerd:
1. add_templates_table.sql
2. add_review_comment.sql
3. add_category_fields.sql

Het automatische script (`run_migrations.php`) voert ze automatisch in de juiste volgorde uit.

## Troubleshooting

### "Table already exists" error
Dit is normaal als je een migratie opnieuw uitvoert. De meeste migraties gebruiken `IF NOT EXISTS` om dit te voorkomen.

### "Foreign key constraint fails"
Zorg ervoor dat:
- De parent tabellen bestaan (bijv. `users`, `categories`, `tickets`)
- De volgorde van migraties correct is

### "Access denied"
Controleer of:
- MySQL/MariaDB draait in XAMPP Control Panel
- Je database credentials correct zijn in `config/database.php`
- De database `ticketportaal` bestaat

## Na het uitvoeren van migraties

1. **Verwijder run_migrations.php** voor beveiliging
2. Test de nieuwe features:
   - Admin → Templates (template beheer)
   - Admin → User Reviews (review overzicht)
   - User → Create Ticket (dynamische velden per categorie)
3. Maak een backup van je database

## Database Backup maken

Via phpMyAdmin:
1. Selecteer database `ticketportaal`
2. Klik op "Export" tab
3. Kies "Quick" export method
4. Klik op "Go"
5. Sla het .sql bestand op

Via Command Line:
```bash
cd C:\xampp\mysql\bin
mysqldump -u root -p ticketportaal > backup_ticketportaal.sql
```

## Hulp nodig?

Als je problemen ondervindt:
1. Check de error logs in `logs/` directory
2. Controleer MySQL error log in XAMPP
3. Zorg dat alle vereiste tabellen bestaan
4. Maak een backup voordat je opnieuw probeert
