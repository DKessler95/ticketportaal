# TODO: Kruit & Kramer Ticketportaal Fixes

## Status: In Progress
Datum: 22 oktober 2025

---

## ✅ VOLTOOID

### 1. Logo Mappen Structuur
- [x] Mappen aangemaakt: `assets/images/logo/Pronto/`, `assets/images/logo/Profijt/`, `assets/images/logo/Henders/`
- [x] Klaar voor logo uploads

### 2. Database Migratie - Locatie Veld
- [x] Migratie file: `database/migrations/add_location_to_users.sql`
- [x] Locatie kolom toegevoegd aan users tabel
- [x] SQL uitgevoerd in database

### 3. Filter Styling Fixes
- [x] `agent/dashboard.php` - Filter knoppen nu correct gestijld
- [x] `agent/my_tickets.php` - Filter knoppen nu correct gestijld
- [x] Knoppen vallen niet meer buiten border

---

## ✅ VOLTOOID (vervolg)

### 4. Locatie Functionaliteit bij Users
**Bestand:** `admin/users.php`
- [x] Locatie dropdown toegevoegd aan "Create New User" formulier
- [x] Locatie opties: "Kruit en Kramer", "Pronto", "Profijt Groningen", "Profijt Hoogeveen", "Profijt Assen", "Henders & Hazel Assen"
- [x] Locatie veld toegevoegd aan "Edit User" modal
- [x] Locatie kolom toegevoegd aan users tabel weergave
- [x] Update query aangepast om locatie op te slaan bij create
- [x] Update query aangepast om locatie op te slaan bij edit
- [x] User.php getAllUsers() method updated om locatie op te halen

### 5. Logo Switching op Basis van User Locatie
**Bestand:** `includes/sidebar.php`
- [x] User locatie ophalen uit sessie
- [x] Logo path bepalen op basis van locatie
- [x] Logo mapping geïmplementeerd:
  - "Pronto" → `assets/images/logo/Pronto/logo.svg`
  - "Profijt Groningen/Hoogeveen/Assen" → `assets/images/logo/Profijt/logo.svg`
  - "Henders & Hazel Assen" → `assets/images/logo/Henders/logo.svg`
  - Default → `assets/images/logo/logo-kruit-en-kramer.svg`
- [x] User.php login method aangepast om locatie in sessie op te slaan

---

### 6. Change Management - Afgewezen Changes Kunnen Aanpassen
**Bestand:** `admin/change_detail.php` & `admin/change_edit.php`
- [x] Edit functionaliteit toegevoegd voor afgewezen changes in change_detail.php
- [x] "Bewerken & Opnieuw Indienen" knop toegevoegd bij status 'rejected'
- [x] Nieuw bestand `admin/change_edit.php` gemaakt met volledig formulier
- [x] Status wordt teruggezet naar 'submitted' na aanpassing
- [x] Afwijzingsreden wordt getoond in sidebar
- [x] Alleen creator kan eigen afgewezen changes bewerken
- [x] Change log wordt bijgewerkt bij resubmit

---

## 🔴 PRIORITEIT 1 - KRITIEKE FIXES

**Alle prioriteit 1 items zijn voltooid! ✅**

---

## 🟡 PRIORITEIT 2 - BELANGRIJKE FEATURES

### 7. Templates UI Herstellen
**Bestand:** `includes/sidebar.php`
- [x] Templates link toegevoegd aan admin sidebar
- [x] Toegevoegd onder "Management" sectie, na "Change Management"
- [x] Templates pagina is nu toegankelijk via sidebar

---

### 8. Template Save Button Fix
**Bestand:** `admin/templates.php`
- [x] TinyMCE save trigger toegevoegd
- [x] Form submit handler verbeterd
- [x] Template content wordt nu correct opgeslagen

---

### 9. Templates Integratie in Ticket Editors
**Bestanden:** `agent/ticket_detail.php`, `user/ticket_detail.php`
- [x] Template dropdown toegevoegd boven comment textarea in beide bestanden
- [x] Template dropdown toegevoegd bij resolution textarea (alleen agent)
- [x] JavaScript loadTemplate() functie geïmplementeerd
- [x] Templates worden opgehaald uit database
- [x] HTML tags worden gestript bij laden in textarea
- [x] Werkt met zowel TinyMCE als plain textarea

---

### 10. Knowledge Base Toegang voor Admin
**Bestand:** `includes/sidebar.php`
- [x] Knowledge Base link toegevoegd aan admin sidebar
- [x] KB Beheren link toegevoegd aan admin sidebar
- [x] Admin heeft nu toegang tot beide Knowledge Base pagina's

---

## 🟢 PRIORITEIT 3 - VERBETERINGEN

### 11. Category Fields Drag & Drop Fix
**Bestand:** `admin/category_fields.php`, `assets/js/category-fields-manager.js`
- [x] Globale CSRF token toegevoegd aan category_fields.php
- [x] JavaScript aangepast om globale CSRF token te gebruiken
- [x] CSRF token lookup verbeterd in saveFieldOrder functie
- [x] API endpoint `api/update_field_order.php` werkt correct
- [x] Database query in CategoryField::updateFieldOrder() werkt correct
- [x] Drag & drop functionaliteit nu volledig werkend

---

### 12. Preview Knop voor Category Fields
**Bestand:** `admin/category_fields.php`, `admin/category_fields_preview.php`
- [x] Preview knop toegevoegd aan category cards (alleen als er velden zijn)
- [x] Nieuw bestand `admin/category_fields_preview.php` gemaakt
- [x] Preview toont alle dynamische velden zoals ze in ticket formulier verschijnen
- [x] Ondersteunt alle veld types: text, textarea, select, radio, checkbox, date, number, email, tel
- [x] Preview opent in nieuw venster
- [x] JavaScript functie previewFields() toegevoegd aan category-fields-manager.js

---

## 📝 NOTITIES

### Locaties Lijst
Voor referentie, de volledige lijst van locaties:
1. Kruit en Kramer (hoofdkantoor)
2. Kruit en Kramer (winkel)
3. Pronto
4. Profijt Groningen
5. Profijt Hoogeveen
6. Profijt Assen
7. Henders & Hazel Assen

### Logo Bestanden Nodig
Upload de volgende logo's:
- `assets/images/logo/Pronto/logo.svg`
- `assets/images/logo/Profijt/logo.svg`
- `assets/images/logo/Henders/logo.svg`

### Database Status
- ✅ `location` kolom toegevoegd aan `users` tabel
- ✅ `assigned_agent_id` kolom toegevoegd aan `change_requests` tabel
- ✅ Change management tabellen aangemaakt

---

## 🎯 VOORTGANG OVERZICHT

**✅ ALLE ITEMS VOLTOOID!**

**Voltooid in deze sessie:**
- ✅ Item 4: Locatie Functionaliteit bij Users
- ✅ Item 5: Logo Switching op Basis van User Locatie  
- ✅ Item 6: Change Management - Afgewezen Changes Kunnen Aanpassen
- ✅ Item 7: Templates UI Herstellen
- ✅ Item 8: Template Save Button Fix
- ✅ Item 9: Templates Integratie in Ticket Editors
- ✅ Item 10: Knowledge Base Toegang voor Admin
- ✅ Item 11: Category Fields Drag & Drop Fix
- ✅ Item 12: Preview Knop voor Category Fields
- ✅ **Bonus**: KB Beheren Bootstrap Icons Fix

**Statistieken:**
- 🔴 Prioriteit 1: 3/3 voltooid (100%)
- 🟡 Prioriteit 2: 4/4 voltooid (100%)
- 🟢 Prioriteit 3: 2/2 voltooid (100%)
- **Totaal: 12/12 items voltooid (100%)** 🎉

## 🎯 KLAAR VOOR PRODUCTIE

Alle items uit de TODO lijst zijn succesvol afgerond! Het systeem is nu klaar voor verdere testing en deployment.

---

## ✅ CHECKLIST VOOR NIEUWE SESSIE

Start met:
```
Ik wil verder met de TODO lijst uit .kiro/TODO-FIXES.md
Begin bij item 4: Locatie Functionaliteit bij Users
```

Einde TODO lijst.
