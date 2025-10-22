# Production Deployment ICT Ticketportaal - Windows Server Opdracht voor Kiro

## Context & Beslissing

Hoi Kiro,

De applicatie is nu volledig getest op XAMPP en we gaan deze live zetten op een **Windows Server** in de Kruit & Kramer omgeving. Dit is een cruciale stap - het moet vanaf dag 1 stabiel, veilig en betrouwbaar draaien zonder dat er daarna veel omkijken naar is.

### **Belangrijke Keuze: IIS vs XAMPP voor Productie**

**AANBEVELING: Native IIS + PHP (GEEN XAMPP)**

**Waarom IIS in plaats van XAMPP?**[122][123][126]

✅ **IIS Voordelen:**
- **Gebouwd voor 24/7 productie** - ontworpen voor enterprise Windows omgevingen[123][126]
- **Native Windows integratie** - naadloze integratie met Windows Server, Active Directory, Windows Services[122][123]
- **Betere security** - reguliere Microsoft security updates, enterprise-grade beveiliging[123][134]
- **Professional support** - volledige Microsoft backing en documentatie[123][131]
- **Betere performance** op Windows - geoptimaliseerd voor het onderliggende OS[123][141]
- **Stabiele 24/7 uptime** - geen port conflicts of crashes die XAMPP vaak heeft[126]
- **Eenvoudiger beheer** - Windows Server Manager, IIS Manager GUI's[122][134]

❌ **XAMPP Nadelen voor Productie:**
- **Ontwikkeltool, geen productie-server** - niet ontworpen voor 24/7 gebruik[123][126][135]
- **Frequente crashes** met databases en services[126]
- **Moeilijk te updaten** - bundle updates zijn complex[135]
- **Security risks** - gebundelde software niet altijd up-to-date[135]
- **Geen enterprise support** - community-only ondersteuning[123]
- **Port conflicts** zijn veel voorkomend[126]

**Conclusie:** IIS is de professionele, enterprise-ready keuze voor Windows Server productie[122][123][134].

---

## Deployment Strategie

### **Fase 1: Server Voorbereiding (Dag 1)**

#### 1.1 Windows Server Specificaties Controleren
**Minimum requirements:**[82]
- Windows Server 2022 of 2025 (aanbevolen)
- 4 CPU cores
- 8GB RAM (12GB aanbevolen)
- 100GB SSD (50GB vrij voor applicatie)
- UPS/redundante voeding
- Actuele Windows Updates

#### 1.2 IIS Installeren
**Via Server Manager:**[122][134][140]
```powershell
# Open PowerShell als Administrator
Install-WindowsFeature -name Web-Server -IncludeManagementTools
Install-WindowsFeature -name Web-CGI
Install-WindowsFeature -name Web-Asp-Net45
```

**Verifieer installatie:**
```powershell
Get-WindowsFeature -Name *Web*
```

#### 1.3 PHP Installeren (Native, GEEN XAMPP)
**Download & Installeer:**[122][125][128][131]
1. **Download PHP 8.2+ Non-Thread Safe** van https://windows.php.net/download
2. **Extract naar:** `C:\PHP`
3. **Kopieer php.ini-production naar php.ini**
4. **Enable extensions in php.ini:**
```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=fileinfo
extension=curl
extension=imap
extension=gd
extension=intl
```

5. **Pas php.ini aan voor productie:**
```ini
; Performance & Security
max_execution_time = 60
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 64M
max_input_time = 60

; Error Handling (productie)
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = "C:\PHP\logs\php_errors.log"

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Session Security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

; Timezone
date.timezone = Europe/Amsterdam
```

6. **PHP toevoegen aan System PATH:**[125]
   - **System Properties** → **Environment Variables**
   - Under **System Variables** → Edit **Path**
   - Add: `C:\PHP`

7. **Verifieer PHP:**
```cmd
php -v
php -m
```

#### 1.4 FastCGI Configureren voor IIS
**IIS Handler Mapping toevoegen:**[122][125][128]
1. **Open IIS Manager**
2. **Selecteer server** → **Handler Mappings**
3. **Add Module Mapping:**
   - Request path: `*.php`
   - Module: `FastCgiModule`
   - Executable: `C:\PHP\php-cgi.exe`
   - Name: `PHP_via_FastCGI`
4. **Klik OK** → **Yes** om te bevestigen

#### 1.5 MySQL/MariaDB Installeren
**Download & Installeer:**[82]
1. **MySQL Community Server 8.0+** van https://dev.mysql.com/downloads/mysql/
2. **Installeer als Windows Service**
3. **Configureer:**
   - Root password instellen (sterk wachtwoord!)
   - Enable network access
   - Character set: utf8mb4
   - Default authentication plugin: mysql_native_password

**Verifieer:**
```cmd
mysql -u root -p
```

---

### **Fase 2: Applicatie Deployment (Dag 2)**

#### 2.1 Applicatie Bestanden Plaatsen
**Locatie:** `C:\inetpub\wwwroot\ticketportaal`[122][134]

```powershell
# Maak directory
New-Item -Path "C:\inetpub\wwwroot\ticketportaal" -ItemType Directory

# Kopieer alle applicatie bestanden naar deze locatie
# Via SFTP, Git, of file share
```

#### 2.2 IIS Website Configureren
**Nieuwe Website aanmaken:**[122][134]
1. **IIS Manager** → **Sites** → **Add Website**
2. **Configuratie:**
   - Site name: `ICT Ticketportaal`
   - Physical path: `C:\inetpub\wwwroot\ticketportaal`
   - Binding:
     - Type: http
     - IP: All Unassigned
     - Port: 80
     - Host name: `tickets.kruit-en-kramer.nl`
3. **Application Pool:**
   - Name: `TicketportaalAppPool`
   - .NET CLR version: `No Managed Code`
   - Managed pipeline mode: `Integrated`
   - Identity: `ApplicationPoolIdentity`

#### 2.3 Permissions Instellen
**File Permissions:**[82]
```powershell
# IIS user access
$path = "C:\inetpub\wwwroot\ticketportaal"
$identity = "IIS AppPool\TicketportaalAppPool"

# Read access voor hele applicatie
icacls $path /grant "${identity}:(OI)(CI)R" /T

# Write access voor specifieke folders
icacls "$path\uploads" /grant "${identity}:(OI)(CI)M" /T
icacls "$path\logs" /grant "${identity}:(OI)(CI)M" /T

# Protect config folder
icacls "$path\config" /inheritance:r
icacls "$path\config" /grant:r "${identity}:R"
icacls "$path\config" /grant:r "BUILTIN\Administrators:(OI)(CI)F"
```

#### 2.4 web.config Maken (IIS Equivalent van .htaccess)
**Maak: `C:\inetpub\wwwroot\ticketportaal\web.config`**[122][125][134]

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <!-- URL Rewrite -->
        <rewrite>
            <rules>
                <rule name="Redirect to HTTPS" stopProcessing="true">
                    <match url="(.*)" />
                    <conditions>
                        <add input="{HTTPS}" pattern="off" ignoreCase="true" />
                    </conditions>
                    <action type="Redirect" url="https://{HTTP_HOST}/{R:1}" redirectType="Permanent" />
                </rule>
            </rules>
        </rewrite>

        <!-- Security Headers -->
        <httpProtocol>
            <customHeaders>
                <add name="X-Frame-Options" value="SAMEORIGIN" />
                <add name="X-Content-Type-Options" value="nosniff" />
                <add name="X-XSS-Protection" value="1; mode=block" />
                <add name="Referrer-Policy" value="strict-origin-when-cross-origin" />
                <remove name="X-Powered-By" />
            </customHeaders>
        </httpProtocol>

        <!-- Directory Browsing -->
        <directoryBrowse enabled="false" />

        <!-- Default Document -->
        <defaultDocument>
            <files>
                <clear />
                <add value="index.php" />
            </files>
        </defaultDocument>

        <!-- Protect Sensitive Directories -->
        <security>
            <requestFiltering>
                <hiddenSegments>
                    <add segment="config" />
                    <add segment="classes" />
                    <add segment="database" />
                    <add segment="logs" />
                    <add segment=".git" />
                </hiddenSegments>
            </requestFiltering>
        </security>
    </system.webServer>
</configuration>
```

---

### **Fase 3: Composer & Dependencies (Dag 2)**

#### 3.1 Composer Installeren
**Windows Composer Installer:**[124][127][130]
1. **Download Composer-Setup.exe** van https://getcomposer.org/download/
2. **Run installer** (as Administrator)
3. **Selecteer PHP path:** `C:\PHP\php.exe`
4. **Install voor alle gebruikers**
5. **Verifieer:**
```cmd
composer --version
```

#### 3.2 Dependencies Installeren
**Als applicatie composer.json heeft:**[133][139]
```cmd
cd C:\inetpub\wwwroot\ticketportaal
composer install --no-dev --optimize-autoloader
```

**Opties uitleg:**
- `--no-dev`: Skip development dependencies (PHPUnit, debug tools)
- `--optimize-autoloader`: Performance optimalisatie voor productie

**Als geen composer.json:** Skip deze stap, applicatie gebruikt native PHP[80][82]

---

### **Fase 4: Database Setup (Dag 2)**

#### 4.1 Database Aanmaken
```sql
-- Connect to MySQL
mysql -u root -p

-- Create database
CREATE DATABASE ticketportaal 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

-- Create dedicated user (NIET root gebruiken!)
CREATE USER 'ticketportal_app'@'localhost' 
  IDENTIFIED BY 'STERK_WACHTWOORD_HIER_123!@#';

-- Grant minimal required privileges
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER 
  ON ticketportaal.* 
  TO 'ticketportal_app'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

#### 4.2 Schema & Data Importeren
```cmd
cd C:\inetpub\wwwroot\ticketportaal\database

mysql -u ticketportal_app -p ticketportaal < schema.sql
mysql -u ticketportal_app -p ticketportaal < seed.sql
```

#### 4.3 Configuratie Bestanden
**config/database.php:**[82]
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticketportaal');
define('DB_USER', 'ticketportal_app');
define('DB_PASS', 'STERK_WACHTWOORD_HIER_123!@#');
define('DB_CHARSET', 'utf8mb4');
?>
```

**config/config.php:**
```php
<?php
define('SITE_URL', 'https://tickets.kruit-en-kramer.nl');
define('DEBUG_MODE', false); // PRODUCTIE = FALSE!
define('APP_NAME', 'ICT Ticketportaal - Kruit & Kramer');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 52428800); // 50MB
define('LOG_PATH', __DIR__ . '/../logs/');
?>
```

**config/email.php:**[82]
```php
<?php
define('EMAIL_ENABLED', true);
define('SMTP_HOST', 'mail.kruit-en-kramer.nl');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'ict@kruit-en-kramer.nl');
define('SMTP_PASSWORD', 'email_wachtwoord');
define('SMTP_SECURE', 'tls');
define('EMAIL_FROM', 'ict@kruit-en-kramer.nl');
define('EMAIL_FROM_NAME', 'ICT Support - Kruit & Kramer');

// IMAP voor email-to-ticket
define('IMAP_ENABLED', true);
define('IMAP_HOST', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX');
define('IMAP_USERNAME', 'ict@kruit-en-kramer.nl');
define('IMAP_PASSWORD', 'email_wachtwoord');
?>
```

---

### **Fase 5: SSL Certificate (Dag 3)**

#### 5.1 SSL Certificaat Verkrijgen
**Optie A: Let's Encrypt (Gratis)**[82]
- Via externe tool zoals **Certify The Web** of **win-acme**
- Download: https://certifytheweb.com/

**Optie B: Bedrijfs SSL Certificaat**[82]
- Aanvragen bij certificaat provider (Sectigo, DigiCert, etc.)
- CSR genereren via IIS Manager

#### 5.2 SSL Binding in IIS
1. **IIS Manager** → **Sites** → **ICT Ticketportaal**
2. **Bindings** → **Add**
3. **Configuratie:**
   - Type: `https`
   - IP: All Unassigned
   - Port: `443`
   - SSL certificate: Selecteer geïnstalleerd certificaat
4. **OK** → **Close**

#### 5.3 Force HTTPS
**In web.config** (al toegevoegd in stap 2.4):
- URL Rewrite regel redirect http → https

---

### **Fase 6: Scheduled Tasks (Email Processing) (Dag 3)**

#### 6.1 Windows Task Scheduler Setup
**Email-to-Ticket Cron Job:**[82]

**Task Scheduler openen:**
```powershell
taskschd.msc
```

**Nieuwe Task maken:**
1. **Create Basic Task**
2. **Name:** `Ticketportaal Email Processing`
3. **Trigger:** Daily, Repeat every: 5 minutes, Duration: Indefinitely
4. **Action:** Start a program
   - Program: `C:\PHP\php.exe`
   - Arguments: `C:\inetpub\wwwroot\ticketportaal\email_to_ticket.php`
   - Start in: `C:\inetpub\wwwroot\ticketportaal`
5. **Settings:**
   - Run whether user is logged on or not
   - Run with highest privileges
   - Hidden

**Verifieer:**
```cmd
# Test manual run
C:\PHP\php.exe C:\inetpub\wwwroot\ticketportaal\email_to_ticket.php

# Check log
type C:\inetpub\wwwroot\ticketportaal\logs\email_processing.log
```

---

### **Fase 7: Backup Automatisering (Dag 3)**

#### 7.1 Database Backup Script
**Maak: `C:\Scripts\backup_ticketportaal.ps1`**[82]

```powershell
# Ticketportaal Backup Script
$backupDir = "D:\Backups\Ticketportaal"
$date = Get-Date -Format "yyyyMMdd_HHmmss"
$mysqlPath = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"

# Create backup directory if not exists
New-Item -ItemType Directory -Force -Path $backupDir

# Database backup
& $mysqlPath -u ticketportal_app -p'WACHTWOORD' ticketportaal | 
  Out-File "$backupDir\database_$date.sql" -Encoding UTF8

# Uploads backup
Compress-Archive -Path "C:\inetpub\wwwroot\ticketportaal\uploads" `
  -DestinationPath "$backupDir\uploads_$date.zip"

# Delete backups older than 30 days
Get-ChildItem -Path $backupDir -Recurse | 
  Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-30)} | 
  Remove-Item -Force

# Log backup
Add-Content -Path "$backupDir\backup_log.txt" `
  -Value "Backup completed: $date"
```

#### 7.2 Scheduled Backup
**Task Scheduler:**
- **Name:** `Ticketportaal Daily Backup`
- **Trigger:** Daily at 02:00
- **Action:** `powershell.exe -ExecutionPolicy Bypass -File C:\Scripts\backup_ticketportaal.ps1`

---

### **Fase 8: Monitoring & Logging (Dag 4)**

#### 8.1 Windows Event Log Monitoring
**Enable IIS logging:**
```powershell
# Configure IIS logging
Set-WebConfigurationProperty `
  -Filter "/system.applicationHost/sites/site[@name='ICT Ticketportaal']/logFile" `
  -Name "enabled" -Value "True"
```

#### 8.2 Application Health Check Script
**Maak: `C:\Scripts\health_check.ps1`**

```powershell
# Health Check Script
$url = "https://tickets.kruit-en-kramer.nl/api/health_check.php"
$logFile = "D:\Logs\health_check.log"
$emailTo = "ict@kruit-en-kramer.nl"

try {
    $response = Invoke-WebRequest -Uri $url -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Add-Content $logFile "$(Get-Date): OK"
    } else {
        $msg = "WARNING: Status code $($response.StatusCode)"
        Add-Content $logFile "$(Get-Date): $msg"
        Send-MailMessage -To $emailTo -From "server@kruit-en-kramer.nl" `
          -Subject "Ticketportaal Health Check Warning" -Body $msg `
          -SmtpServer "mail.kruit-en-kramer.nl"
    }
} catch {
    $msg = "ERROR: $($_.Exception.Message)"
    Add-Content $logFile "$(Get-Date): $msg"
    Send-MailMessage -To $emailTo -From "server@kruit-en-kramer.nl" `
      -Subject "Ticketportaal Health Check FAILED" -Body $msg `
      -SmtpServer "mail.kruit-en-kramer.nl"
}
```

**Schedule elke 30 minuten**

#### 8.3 Disk Space Monitoring
```powershell
# Check disk space
$drive = Get-PSDrive C
$freeGB = [math]::Round($drive.Free/1GB, 2)
if ($freeGB -lt 20) {
    # Send alert email
}
```

---

### **Fase 9: Security Hardening (Dag 4)**

#### 9.1 Windows Firewall
```powershell
# Allow HTTP/HTTPS
New-NetFirewallRule -DisplayName "HTTP" -Direction Inbound `
  -Protocol TCP -LocalPort 80 -Action Allow

New-NetFirewallRule -DisplayName "HTTPS" -Direction Inbound `
  -Protocol TCP -LocalPort 443 -Action Allow

# Block direct MySQL access from outside
New-NetFirewallRule -DisplayName "Block MySQL External" `
  -Direction Inbound -Protocol TCP -LocalPort 3306 -Action Block `
  -RemoteAddress Internet
```

#### 9.2 IIS Security Settings
```powershell
# Disable unnecessary HTTP methods
Set-WebConfigurationProperty `
  -PSPath 'MACHINE/WEBROOT/APPHOST' `
  -Filter "system.webServer/security/requestFiltering/verbs" `
  -Name "." -Value @{verb='TRACE';allowed='false'}

# Request filtering limits
Set-WebConfigurationProperty `
  -PSPath 'MACHINE/WEBROOT/APPHOST' `
  -Filter "system.webServer/security/requestFiltering/requestLimits" `
  -Name "maxAllowedContentLength" -Value 52428800
```

#### 9.3 Reguliere Security Updates
```powershell
# Enable Windows Update
Install-Module PSWindowsUpdate -Force
Get-WindowsUpdate
Install-WindowsUpdate -AcceptAll -AutoReboot
```

---

### **Fase 10: Testing & Go-Live (Dag 5)**

#### 10.1 Pre-Production Checklist
**Functioneel:**
- [ ] Website bereikbaar via https://tickets.kruit-en-kramer.nl
- [ ] SSL certificate geldig en geen browser warnings
- [ ] Login werkt met test accounts
- [ ] Ticket aanmaken werkt (web + email)
- [ ] File uploads werken
- [ ] Email notificaties werken
- [ ] Knowledge base toegankelijk
- [ ] Admin/Agent/User dashboards functioneel
- [ ] CI/Change management modules werkend (als geïmplementeerd)

**Performance:**
- [ ] Pagina's laden < 2 seconden
- [ ] Database queries geoptimaliseerd
- [ ] 20+ concurrent users testen (stress test)

**Security:**
- [ ] Debug mode UIT (config.php)
- [ ] Error messages tonen geen gevoelige info
- [ ] SQL injection test (gebruik tool)
- [ ] XSS test
- [ ] File upload validation werkt
- [ ] Directory browsing disabled
- [ ] Config files niet toegankelijk via browser

**Backup & Monitoring:**
- [ ] Database backup draait en werkt
- [ ] File backup draait
- [ ] Health check script actief
- [ ] Email alerts geconfigureerd
- [ ] Disk space monitoring actief

#### 10.2 User Acceptance Testing
**Test met 3-5 gebruikers:**
1. Admin: Alle beheer functionaliteit
2. Agent: Ticket management workflows
3. Users: Ticket aanmaken en tracking

**Fix alle critical bugs voor go-live**

#### 10.3 Go-Live Procedure
**Communicatie:**
- [ ] Email naar alle gebruikers met:
  - Go-live datum en tijd
  - Nieuwe URL
  - Login instructies
  - Support contact (jouw email/telefoon)

**DNS Update:**
- [ ] tickets.kruit-en-kramer.nl wijst naar server IP
- [ ] TTL verlaagd voor snelle propagatie
- [ ] Test DNS resolve: `nslookup tickets.kruit-en-kramer.nl`

**Final Checks:**
- [ ] Backup VOOR go-live
- [ ] All services running
- [ ] Health check OK
- [ ] SSL certificate geldig

**Post Go-Live (Week 1):**
- Dagelijks logs checken
- User feedback verzamelen
- Performance monitoren
- Kleine bugs direct fixen

---

### **Fase 11: Documentatie & Overdracht (Dag 6-7)**

#### 11.1 Documentatie Maken
**Administrator Handleiding:**
- Server configuratie
- Backup/restore procedures
- Troubleshooting common issues
- Update procedures
- Security best practices

**User Handleiding:**
- Inloggen en registreren
- Ticket aanmaken
- Email naar ticket
- Knowledge base gebruiken
- FAQ

#### 11.2 Change Management
**Document de deployment als Change in het systeem zelf:**[121]
- Change type: Infrastructuur
- Impact: Hoog (nieuwe productie systeem)
- Implementation plan: Deze deployment guide
- Rollback plan: Terugzetten naar oude situatie
- Post-implementation review: Na 1 week

---

## Onderhoud & Updates

### **Dagelijks:**
- Check health check logs
- Monitor disk space

### **Wekelijks:**
- Review error logs (PHP, IIS, MySQL)
- Check backup success
- Review security logs

### **Maandelijks:**
- Windows Updates installeren
- PHP updates (minor versions)
- MySQL updates (patch releases)
- Security audit
- Performance review

### **Kwartaal:**
- Major updates (PHP, MySQL major versions)
- SSL certificate renewal check
- Full security audit
- Disaster recovery test (restore from backup)

---

## Troubleshooting

### **Website niet bereikbaar:**
```powershell
# Check IIS
iisreset /status

# Check PHP
C:\PHP\php.exe -v

# Check DNS
nslookup tickets.kruit-en-kramer.nl

# Check firewall
Get-NetFirewallRule -DisplayName "*HTTP*"
```

### **Database connection errors:**
```cmd
# Test MySQL
mysql -u ticketportal_app -p ticketportaal

# Check service
Get-Service MySQL80

# Restart if needed
Restart-Service MySQL80
```

### **Email niet werkend:**
```php
// Test SMTP in PHP
// Maak test_email.php met mail() functie
```

### **Performance issues:**
```powershell
# Check resource usage
Get-Counter '\Processor(_Total)\% Processor Time'
Get-Counter '\Memory\Available MBytes'

# IIS App Pool recycling
Restart-WebAppPool -Name "TicketportaalAppPool"
```

---

## Emergency Contact & Rollback

### **Emergency Rollback Plan:**
1. Stop IIS site
2. Restore database from backup
3. Restore files from backup
4. Restart services
5. Verify functionality

### **Support Escalatie:**
- **Level 1:** Jij (ICT Manager)
- **Level 2:** Kiro (Developer)
- **Level 3:** Externe consultant (als nodig)

---

## Success Criteria

**De deployment is succesvol als:**
✅ Applicatie 24/7 bereikbaar via HTTPS  
✅ Alle functionaliteit werkt zoals getest  
✅ Backups draaien automatisch  
✅ Monitoring is actief en alerts werken  
✅ Security hardened volgens best practices  
✅ Performance is acceptabel (< 2s page load)  
✅ Gebruikers kunnen zonder problemen werken  
✅ Geen kritieke bugs eerste week  

---

## Deliverables voor Kiro

**Wat klaar moet zijn:**
1. ✅ IIS + PHP productie-ready geïnstalleerd
2. ✅ Applicatie deployed en werkend
3. ✅ SSL certificaat actief
4. ✅ Database backups automatisch
5. ✅ Monitoring scripts actief
6. ✅ Complete documentatie
7. ✅ Geteste disaster recovery procedure
8. ✅ Overdracht aan jou met training

---

**Geschatte Tijdlijn: 5-7 dagen voor volledige productie deployment**

**Succes Kiro! Dit is de laatste kritieke stap - daarna draait alles professioneel en stabiel!**