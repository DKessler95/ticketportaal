# Email-to-Ticket Setup Handleiding

## Overzicht
Deze handleiding legt uit hoe je de email-to-ticket functionaliteit koppelt aan je werk mailbox.

## Stap 1: Email Configuratie

### 1.1 Bewerk `config/email.php`

```php
// IMAP Configuratie voor het ontvangen van emails
define('IMAP_HOST', '{jouw-mail-server.nl:993/imap/ssl}INBOX');
define('IMAP_USER', 'ict@kruit-en-kramer.nl');
define('IMAP_PASS', 'jouw_wachtwoord');
define('IMAP_PORT', 993);
define('IMAP_SECURE', 'ssl');
```

### 1.2 Voor Collax Email Server

Als je Collax gebruikt op werk:

```php
define('IMAP_HOST', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX');
define('IMAP_USER', 'ict@kruit-en-kramer.nl');
define('IMAP_PASS', 'jouw_wachtwoord');
```

### 1.3 Voor een Submap

Als je emails in een specifieke submap wilt verwerken:

```php
// Voor een submap genaamd "Tickets"
define('IMAP_HOST', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX.Tickets');

// Of voor een submap genaamd "Support"
define('IMAP_HOST', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX.Support');
```

**Tip:** De exacte mapnaam kun je vinden door in te loggen op je webmail en de mapstructuur te bekijken.

## Stap 2: Email Processing Inschakelen

Voeg toe aan `config/email.php`:

```php
// Email processing inschakelen
define('EMAIL_PROCESSING_ENABLED', true);
```

## Stap 3: Cron Job Instellen

### 3.1 Voor Linux/Production Server

```bash
# Open crontab
crontab -e

# Voeg deze regel toe (draait elke 5 minuten)
*/5 * * * * /usr/bin/php /pad/naar/ticketportaal/email_to_ticket.php >> /pad/naar/logs/email_cron.log 2>&1
```

### 3.2 Voor Windows/XAMPP (Development)

**Optie A: Task Scheduler**

1. Open "Task Scheduler" (Taakplanner)
2. Klik "Create Basic Task"
3. Naam: "Email to Ticket Processor"
4. Trigger: "Daily" → Herhaal elke 5 minuten
5. Action: "Start a program"
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `D:\xampp\htdocs\ticketportaal\email_to_ticket.php`

**Optie B: Handmatig Testen**

```cmd
cd D:\xampp\htdocs\ticketportaal
php email_to_ticket.php
```

## Stap 4: Test de Configuratie

### 4.1 Test Script Uitvoeren

Maak een test bestand `test_email_connection.php`:

```php
<?php
require_once 'config/email.php';

echo "Testing IMAP connection...\n";
echo "Host: " . IMAP_HOST . "\n";
echo "User: " . IMAP_USER . "\n";

$mailbox = @imap_open(IMAP_HOST, IMAP_USER, IMAP_PASS);

if ($mailbox) {
    echo "✓ Connection successful!\n";
    
    $emails = imap_search($mailbox, 'ALL');
    echo "Total emails: " . (is_array($emails) ? count($emails) : 0) . "\n";
    
    imap_close($mailbox);
} else {
    echo "✗ Connection failed: " . imap_last_error() . "\n";
}
?>
```

Voer uit:
```bash
php test_email_connection.php
```

### 4.2 Test Email Versturen

1. Stuur een email naar `ict@kruit-en-kramer.nl`
2. Onderwerp: "Test Ticket"
3. Inhoud: "Dit is een test ticket vanuit email"
4. Voer het email_to_ticket script uit:
   ```bash
   php email_to_ticket.php
   ```
5. Check in de admin panel: `http://localhost:8080/ticketportaal/admin/email_tickets.php`

## Stap 5: Email Tickets Beheren

### 5.1 Admin Panel

Ga naar: **Admin Dashboard → Email Tickets**

Hier kun je:
- ✅ Alle email-tickets bekijken
- ✅ Tickets toewijzen aan agents
- ✅ Prioriteit aanpassen
- ✅ Status bijwerken
- ✅ Tickets bekijken en beantwoorden

### 5.2 Workflow

1. **Email Ontvangen** → Automatisch ticket aangemaakt
2. **Triage** → Admin bekijkt en wijst toe
3. **Behandeling** → Agent werkt ticket af
4. **Oplossing** → Klant ontvangt update via email

## Troubleshooting

### Probleem: "Failed to connect to mailbox"

**Oplossingen:**
1. Check of IMAP is ingeschakeld op de mailbox
2. Verifieer username en wachtwoord
3. Check firewall instellingen (poort 993 moet open zijn)
4. Test met telnet: `telnet mail.kruit-en-kramer.nl 993`

### Probleem: "PHP IMAP extension not found"

**Windows/XAMPP:**
1. Open `php.ini`
2. Zoek `;extension=imap`
3. Verwijder de `;` om te activeren: `extension=imap`
4. Herstart Apache

**Linux:**
```bash
sudo apt-get install php-imap
sudo systemctl restart apache2
```

### Probleem: "No emails found"

**Check:**
1. Zijn er ongelezen emails in de mailbox?
2. Is de juiste map geconfigureerd?
3. Check de logs: `logs/app.log`

### Probleem: "Permission denied"

**Linux:**
```bash
chmod +x email_to_ticket.php
chown www-data:www-data email_to_ticket.php
```

## Email Formaat Vereisten

### Onderwerp (Subject)
- Wordt de **ticket titel**
- Maximaal 255 karakters
- Speciale tekens worden automatisch verwijderd

### Body
- Wordt de **ticket beschrijving**
- HTML wordt geconverteerd naar platte tekst
- Handtekeningen worden automatisch verwijderd

### Bijlagen
- Maximaal 10MB per bijlage
- Toegestane types: PDF, DOC, DOCX, JPG, PNG, TXT, ZIP
- Worden automatisch opgeslagen in `uploads/tickets/`

### Van Adres (From)
- Als email adres bestaat → Ticket gekoppeld aan gebruiker
- Als email adres NIET bestaat → Nieuwe gebruiker aangemaakt
- Naam wordt automatisch geëxtraheerd

## Geavanceerde Configuratie

### Meerdere Mailboxen

Je kunt meerdere mailboxen monitoren door meerdere configuraties te maken:

```php
// config/email_support.php
define('IMAP_HOST_SUPPORT', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX.Support');

// config/email_sales.php
define('IMAP_HOST_SALES', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX.Sales');
```

### Auto-Categorisatie

Voeg toe aan `email_to_ticket.php`:

```php
// Automatisch categorie toewijzen op basis van onderwerp
if (stripos($subject, 'hardware') !== false) {
    $categoryId = 1; // Hardware
} elseif (stripos($subject, 'software') !== false) {
    $categoryId = 2; // Software
} else {
    $categoryId = 7; // Other
}
```

### Email Filters

Negeer bepaalde emails:

```php
// Negeer auto-replies
if (stripos($subject, 'Out of Office') !== false) {
    continue;
}

// Negeer spam
if (stripos($from, 'noreply@') !== false) {
    continue;
}
```

## Best Practices

1. **Dedicated Email Adres** - Gebruik een apart email adres voor tickets (bijv. `support@kruit-en-kramer.nl`)
2. **Regelmatige Monitoring** - Check de email tickets pagina dagelijks
3. **Snelle Triage** - Wijs nieuwe tickets binnen 1 uur toe
4. **Auto-Reply** - Klanten ontvangen automatisch een bevestiging
5. **Backup** - Bewaar originele emails in een aparte map

## Support

Voor vragen over de email integratie:
- Check de logs: `logs/app.log` en `logs/email_cron.log`
- Test handmatig: `php email_to_ticket.php`
- Bekijk de documentatie: `EMAIL_INTEGRATION_README.md`

---

**Laatste Update:** Januari 2025
**Versie:** 1.0
