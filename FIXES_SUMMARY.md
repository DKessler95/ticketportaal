# Fixes Samenvatting

## Problemen Opgelost ✓

### 1. Duplicate resetPassword() Function
**Probleem:** Fatal error - Cannot redeclare User::resetPassword()

**Oorzaak:** Er waren twee `resetPassword()` functies in User.php:
- Een voor admin password reset (nieuwe functie)
- Een voor token-based password reset (bestaande functie voor "wachtwoord vergeten")

**Oplossing:**
- Eerste functie hernoemd naar `resetPasswordByAdmin($userId, $newPassword)`
- admin/users.php bijgewerkt om nieuwe functienaam te gebruiken
- Token-based reset functie blijft `resetPassword($token, $newPassword)`

### 2. Category Fields Preview Error
**Probleem:** Fatal error - json_decode(): Argument #1 ($json) must be of type string, array given

**Oorzaak:** 
- CategoryField class decodeert al JSON naar arrays in `getFieldsByCategory()`
- Preview pagina probeerde opnieuw json_decode uit te voeren op een array

**Oplossing:**
- Verwijderd `json_decode()` calls in category_fields_preview.php (3 locaties)
- Direct `$field['field_options']` gebruiken (is al een array)

**Gefixt in:**
- Line 124: `$options = $field['field_options'] ?? [];` (was: json_decode)
- Line 141: `$options = $field['field_options'] ?? [];` (was: json_decode)  
- Line 160: `$options = $field['field_options'] ?? [];` (was: json_decode)

### 3. Database Migraties Status
**Gecontroleerd en uitgevoerd:**
- ✓ Category fields: 33 velden aanwezig
- ✓ Categories: 7 categorieën
- ✓ Users: 4 gebruikers
- ✓ Tickets: 1 ticket
- ✓ Configuration Items: 5 items
- ✓ Changes: 3 changes
- ✓ Templates: 20 templates

**Category Fields per Categorie:**
- Hardware (ID 1): 4 velden (hardware_type, brand, model, serial_number)
- Software (ID 2): 21 velden (software_name, version, license, company-specific fields)
- Andere categorieën: Diverse velden voor Network, Account, Email, Security

## Testen

**Test Scripts Gemaakt:**
1. `test_category_preview.php` - Test category fields data ophalen
2. `run_remaining_migrations.php` - Check en run ontbrekende migraties

**Test Resultaten:**
- ✓ Category fields worden correct opgehaald als arrays
- ✓ Hardware category: 4 velden met correcte opties
- ✓ Software category: 21 velden met company-specific opties
- ✓ Preview pagina zou nu moeten werken zonder errors

## Volgende Stappen

**Om te testen:**
1. Login als admin: http://localhost:8080/ticketportaal/login.php
2. Ga naar: Admin → Category Fields Management
3. Klik op "Preview" bij een categorie
4. Controleer of dynamische velden correct worden getoond

**Wachtwoord Reset Testen:**
1. Ga naar: Admin → Gebruikers
2. Klik "Bewerken" bij een gebruiker
3. Klik "Wachtwoord Resetten" (gele knop)
4. Nieuw wachtwoord wordt getoond (8 karakters, letters + cijfers)

## Bestanden Gewijzigd

1. `classes/User.php` - resetPassword hernoemd naar resetPasswordByAdmin
2. `admin/users.php` - Gebruik nieuwe functienaam
3. `admin/category_fields_preview.php` - Verwijderd json_decode calls (3x)

## Bestanden Toegevoegd

1. `test_category_preview.php` - Test script voor category fields
2. `run_remaining_migrations.php` - Migratie checker
3. `FIXES_SUMMARY.md` - Deze samenvatting
