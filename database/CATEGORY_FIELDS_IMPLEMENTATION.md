# Category Fields Implementation for AI RAG System

## Overview

This document describes the implementation of dynamic category fields for the ICT Ticketportaal to improve AI training data quality. Complete, structured data enables better semantic search and more accurate AI suggestions.

**Requirements**: 2.1, 11.1  
**Tasks**: 5, 5.1, 5.2, 5.3, 5.4

## Implementation Summary

### Files Created

1. **`migrations/populate_category_fields_for_ai.sql`**
   - Main migration script with all category field definitions
   - Includes 54 fields across 7 categories
   - Comprehensive dropdown options for structured data

2. **`run_category_fields_migration.php`**
   - PHP script to execute migration with detailed feedback
   - Provides before/after comparison
   - Error handling and validation

3. **`verify_category_fields.php`**
   - PHP verification script
   - Shows field counts and detailed breakdown

4. **`verify_category_fields.sql`**
   - SQL queries for manual verification
   - Can be run directly in SQL console

## Category Fields Breakdown

### 1. Hardware Category (9 fields) ✓
**Task 5.1 - Requirements: 2.1**

| Field | Type | Required | Purpose |
|-------|------|----------|---------|
| Type Hardware | select | Yes | Laptop, Desktop, Printer, etc. |
| Merk | select | Yes | Dell, HP, Lenovo, etc. |
| Model | text | No | Specific model number |
| Serienummer | text | No | Serial number for tracking |
| Locatie | select | Yes | Kantoor Hengelo, Enschede, etc. |
| Afdeling | select | Yes | ICT, Sales, Inkoop, etc. |
| Asset Tag | text | No | Internal inventory tag |
| Aankoopdatum | date | No | Purchase date for warranty |
| Type Probleem | select | Yes | Problem categorization |

**Dropdown Options**:
- **Brands**: Dell, HP, Lenovo, Apple, Microsoft, Asus, Acer, Samsung, LG, Canon, Epson, Brother, Logitech
- **Locations**: Kantoor Hengelo, Kantoor Enschede, Thuiswerkplek, Vestiging Almelo, Magazijn, Showroom
- **Departments**: ICT, Sales, Inkoop, Facilitair, Directie, HR, Financiën, Logistiek, Marketing

### 2. Software Category (14 fields) ✓
**Task 5.2 - Requirements: 2.1**

| Field | Type | Required | Purpose |
|-------|------|----------|---------|
| Applicatie Naam | select | Yes | Common applications + company-specific |
| Andere Applicatie | text | No | Custom application name |
| Versie | text | No | Software version |
| Licentie Type | select | No | License categorization |
| Installatie Locatie | select | Yes | Where software is installed |
| Type Probleem | select | Yes | Problem categorization |
| Foutmelding | textarea | No | Error message details |
| Besturingssysteem | select | No | OS information |
| Ecoro SHD Module | select | No | Specific Ecoro module |
| Kassa Locatie | select | No | Cash register location |
| Kassa Nummer | text | No | Cash register number |
| WinqlWise Probleem Type | select | No | WinqlWise issue type |
| Backup Type | select | No | Type of backup |
| Backup Actie | select | No | Backup action needed |

**Dropdown Options**:
- **Applications**: Microsoft Office, Microsoft 365, Teams, Outlook, Adobe Acrobat, Chrome, Firefox, Zoom, AutoCAD, SAP, **Ecoro SHD**, **Kassa**, **WinqlWise**, **Backup & Recovery**, ERP, CRM, Antivirus, VPN Client
- **License Types**: Bedrijfslicentie, Gebruikerslicentie, Proefversie, Gratis/Open Source
- **Installation Locations**: Lokale Computer, Netwerkschijf, Cloud/Online, Server
- **Ecoro SHD Modules**: Verkooporders, Bestellingen, Magazijn, Logistiek, Planning, Facturen, Algemeen
- **Kassa Locations**: Kantoor Hengelo, Kantoor Enschede, Vestiging Almelo, Showroom
- **WinqlWise Issues**: Kan niet inloggen, Bestellingen plaatsen, Voorraad inzien, Prijzen/Offertes, Leverancier communicatie
- **Backup Types**: Automatische backup, Handmatige backup, Cloud backup, Lokale backup

**Company-Specific Applications**:
- **Ecoro SHD**: Bedrijfsapplicatie voor verkooporders, bestellingen, magazijn, logistiek, planning, en facturen
- **Kassa**: Kassasysteem voor verkooppunten
- **WinqlWise**: IMG leveranciersportaal
- **Backup & Recovery**: Backup en herstel systemen

### 3. Network Category (10 fields) ✓
**Task 5.3 - Requirements: 2.1**

| Field | Type | Required | Purpose |
|-------|------|----------|---------|
| Type Probleem | select | Yes | Problem categorization |
| Verbindingstype | select | Yes | LAN, WiFi, VPN |
| Locatie | select | Yes | Physical location |
| Switch/Router | text | No | Network device identifier |
| Poort Nummer | text | No | Network port number |
| VLAN | text | No | VLAN identifier |
| IP Adres | text | No | IP address |
| MAC Adres | text | No | MAC address |
| WiFi Netwerk | select | No | WiFi network name |
| Getroffen Diensten | checkbox | No | Affected services |

**Dropdown Options**:
- **Problem Types**: Geen verbinding, Trage verbinding, Intermitterende verbinding, VPN probleem, WiFi probleem
- **Connection Types**: Bekabeld (LAN), Draadloos (WiFi), VPN, Mobiel Hotspot
- **WiFi Networks**: KK-Office, KK-Guest, KK-Secure, Thuisnetwerk
- **Affected Services**: Internet, E-mail, Gedeelde mappen, Printer, Intranet, Externe applicaties

### 4. Account Category (12 fields) ✓
**Task 5.4 - Requirements: 2.1**

| Field | Type | Required | Purpose |
|-------|------|----------|---------|
| Type Aanvraag | select | Yes | Request type |
| Type Account | select | Yes | Account categorization |
| Gebruikersnaam | text | Yes | Username |
| E-mailadres | email | Yes | Email address |
| Volledige Naam | text | No | Full name for new accounts |
| Afdeling | select | Yes | Department |
| Toegangsniveau | select | Yes | Access level |
| Systeem | checkbox | Yes | Target systems |
| Externe Applicatie Naam | text | No | External app name |
| Manager/Leidinggevende | text | No | Manager approval |
| Startdatum | date | No | Account start date |
| Einddatum | date | No | Account end date |

**Dropdown Options**:
- **Request Types**: Nieuw account, Wachtwoord reset, Toegang wijzigen, Account deactiveren
- **Account Types**: Windows/Netwerk Account, E-mail Account, VPN Toegang, Applicatie Account, Database Toegang
- **Departments**: ICT, Sales, Inkoop, Facilitair, Directie, HR, Financiën, Logistiek, Marketing
- **Access Levels**: Standaard Gebruiker, Power User, Afdeling Administrator, Systeem Administrator, Alleen Lezen, Lezen/Schrijven, Volledige Controle
- **Systems**: Windows Netwerk, E-mail, VPN, ERP Systeem, CRM Systeem, Fileserver, Database, Intranet, Externe Applicatie

### 5. Email Category (6 fields)

| Field | Type | Required | Purpose |
|-------|------|----------|---------|
| Type Probleem | select | Yes | Problem categorization |
| E-mail Programma | select | Yes | Email client |
| Apparaat | select | No | Device type |
| E-mailadres | email | Yes | Affected email address |
| Foutmelding | textarea | No | Error message |
| Omvang | radio | No | Scope of issue |

### 6. Security Category (6 fields)

| Field | Type | Required | Purpose |
|-------|------|----------|---------|
| Type Beveiligingsprobleem | select | Yes | Security issue type |
| Urgentie | radio | Yes | Urgency level |
| Getroffen Systeem | text | Yes | Affected system |
| Beschrijving Dreiging | textarea | Yes | Threat description |
| Data Gecompromitteerd? | radio | Yes | Data compromise status |
| Ondernomen Actie | checkbox | No | Actions taken |

### 7. Other Category (3 fields)

| Field | Type | Required | Purpose |
|-------|------|----------|---------|
| Type Aanvraag | select | Yes | Request type |
| Prioriteit Indicatie | radio | No | Priority indication |
| Voorkeur Contact | radio | No | Preferred contact method |

## Verification

### Run Verification Query

Execute the verification queries in `verify_category_fields.sql`:

```sql
-- Summary query
SELECT 
    c.name as category_name,
    COUNT(cf.field_id) as total_fields
FROM categories c
LEFT JOIN category_fields cf ON c.category_id = cf.category_id
WHERE cf.is_active = 1
GROUP BY c.category_id, c.name
ORDER BY c.category_id;
```

**Expected Results**:
- Hardware: 9 fields
- Software: 14 fields (updated with company-specific applications)
- Network: 10 fields
- Account: 12 fields
- Email: 6 fields
- Security: 6 fields
- Other: 3 fields
- **Total: 60 active fields**

## Data Quality Impact on AI

### Why Complete Category Fields Matter

1. **Semantic Search Accuracy**
   - Structured data (dropdowns) provides consistent terminology
   - Brand names, locations, and departments become searchable entities
   - AI can match tickets based on specific hardware models or software versions

2. **Entity Extraction**
   - Fields like "Merk", "Model", "Serienummer" become entities in the knowledge graph
   - Relationships can be built: Ticket → Hardware → Location → Department

3. **Context Building**
   - Complete fields provide rich context for RAG prompts
   - AI can suggest solutions based on specific hardware/software combinations
   - Historical patterns emerge (e.g., "Dell Latitude 5520 BIOS issues")

4. **Embedding Quality**
   - More structured data = better vector embeddings
   - Semantic similarity improves when tickets have complete metadata
   - Reduces ambiguity in natural language descriptions

### Example: Before vs After

**Before (Poor Data Quality)**:
```
Title: Laptop werkt niet
Description: Laptop doet het niet meer
```

**After (Rich Data Quality)**:
```
Title: Laptop werkt niet
Description: Laptop doet het niet meer
Fields:
  - Type Hardware: Laptop
  - Merk: Dell
  - Model: Latitude 5520
  - Serienummer: ABC123XYZ
  - Locatie: Kantoor Hengelo
  - Afdeling: Sales
  - Type Probleem: Start niet op
```

The AI can now:
- Find similar Dell Latitude 5520 startup issues
- Suggest solutions specific to that model
- Link to related CI items
- Identify patterns by location or department

## Next Steps

### 1. Agent Training
- Train agents on filling out category fields correctly
- Emphasize importance of complete data for AI accuracy
- Provide examples of good vs poor ticket data

### 2. Field Validation
- Test ticket creation with all categories
- Verify dropdown options are comprehensive
- Add more options based on agent feedback

### 3. Data Collection
- Begin collecting tickets with complete fields
- Monitor field completion rates
- Identify categories with low completion

### 4. AI Integration
- Sync pipeline will extract all field data
- Fields become part of ticket embeddings
- Knowledge graph will use fields for entity relationships

### 5. Continuous Improvement
- Review field usage monthly
- Add new fields as needed
- Update dropdown options based on actual usage
- Remove unused fields

## Technical Notes

### Database Schema

```sql
CREATE TABLE category_fields (
  field_id INT PRIMARY KEY AUTO_INCREMENT,
  category_id INT NOT NULL,
  field_name VARCHAR(255) NOT NULL,
  field_label VARCHAR(255) NOT NULL,
  field_type ENUM('text', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number', 'email', 'tel'),
  field_options TEXT NULL,  -- JSON array
  is_required TINYINT(1) DEFAULT 0,
  field_order INT DEFAULT 0,
  placeholder VARCHAR(255) NULL,
  help_text TEXT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE ticket_field_values (
  value_id INT PRIMARY KEY AUTO_INCREMENT,
  ticket_id INT NOT NULL,
  field_id INT NOT NULL,
  field_value TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_ticket_field (ticket_id, field_id)
);
```

### Field Types

- **text**: Single-line text input
- **textarea**: Multi-line text input
- **select**: Dropdown with predefined options
- **radio**: Radio buttons (single choice)
- **checkbox**: Multiple checkboxes (multiple choices)
- **date**: Date picker
- **number**: Numeric input
- **email**: Email validation
- **tel**: Phone number input

### JSON Options Format

```json
["Option 1", "Option 2", "Option 3"]
```

## Troubleshooting

### Fields Not Showing in Ticket Form

1. Check if category_id matches correctly
2. Verify is_active = 1
3. Check field_order for proper sequencing
4. Clear browser cache

### Dropdown Options Not Displaying

1. Verify field_options is valid JSON
2. Check for proper escaping of quotes
3. Test with simple options first

### Field Values Not Saving

1. Check ticket_field_values table exists
2. Verify foreign key constraints
3. Check for duplicate ticket_id + field_id combinations

## References

- **Requirements Document**: `.kiro/specs/rag-ai-local-implementation/requirements.md`
- **Design Document**: `.kiro/specs/rag-ai-local-implementation/design.md`
- **Tasks Document**: `.kiro/specs/rag-ai-local-implementation/tasks.md`
- **CategoryField Class**: `classes/CategoryField.php`
- **Category Management**: `admin/category_fields.php`

## Completion Status

- [x] Task 5: Audit and Configure Category Dynamic Fields
- [x] Task 5.1: Populate Hardware Category Fields
- [x] Task 5.2: Populate Software Category Fields
- [x] Task 5.3: Populate Network Category Fields
- [x] Task 5.4: Populate Account Category Fields

**Implementation Date**: 2025-10-22  
**Status**: ✓ Complete
