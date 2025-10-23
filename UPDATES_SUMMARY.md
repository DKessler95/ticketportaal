# Updates Samenvatting

## 1. Logo in Sjablonen Gefixed ✓

**Probleem:** Logo verwijzingen in database templates gebruikten `http://localhost/ticketportaal` in plaats van `http://localhost:8080/ticketportaal`

**Oplossing:**
- Script `fix_template_logos.php` gemaakt en uitgevoerd
- 15 templates bijgewerkt met correcte URL
- Logo's werken nu correct in alle templates

**Bijgewerkte Templates:**
- Ticket Succesvol Afgesloten
- Probleem Opgelost - Configuratie Aangepast
- Probleem Opgelost - Software Update
- Ticket Afgesloten - Geen Actie Nodig
- Meer Informatie Nodig
- In Behandeling - Analyse Gestart
- Wijziging Verwerkt
- Account Gegevens Verstrekt
- Wachtend op Externe Partij
- Tijdelijke Oplossing - Workaround
- Geplande Onderhoudsmelding
- Change Afgekeurd
- Change Goedgekeurd
- Change In Uitvoering
- Change Succesvol Afgerond

## 2. Wachtwoord Reset Functionaliteit Toegevoegd ✓

**Nieuwe Functionaliteit:**
- Admins kunnen nu gebruikerswachtwoorden resetten via de gebruikersbeheer pagina
- Automatisch gegenereerd wachtwoord: 8 karakters, alleen letters en cijfers (geen speciale tekens)
- Nieuw wachtwoord wordt getoond na reset zodat admin het kan delen met gebruiker

**Toegevoegde Bestanden/Functies:**
1. `includes/functions.php` - Nieuwe functie `generateRandomPassword()`
2. `classes/User.php` - Nieuwe methode `resetPassword()`
3. `admin/users.php` - Nieuwe actie `reset_password` en UI knop

**Hoe te gebruiken:**
1. Ga naar Admin Dashboard → Gebruikers
2. Klik op "Bewerken" bij een gebruiker
3. Klik op "Wachtwoord Resetten" knop (geel, links onderaan)
4. Bevestig de actie
5. Het nieuwe wachtwoord wordt getoond - deel dit veilig met de gebruiker

**Voorbeeld gegenereerd wachtwoord:** `aB3kL9mP` (8 karakters, mix van hoofdletters, kleine letters en cijfers)

## Toegang

**Login:** http://localhost:8080/ticketportaal/login.php
- Email: admin@kruit-en-kramer.nl
- Wachtwoord: Admin123!

**Admin Dashboard:** http://localhost:8080/ticketportaal/admin/index.php

**Gebruikersbeheer:** http://localhost:8080/ticketportaal/admin/users.php

## Handige Scripts

- `fix_template_logos.php` - Fix logo URLs in templates
- `unlock_account.php` - Ontgrendel account bij lockout
- `debug_login.php` - Debug login problemen
- `test_connection.php` - Test database connectie
- `run_all_migrations.php` - Voer alle migraties uit
