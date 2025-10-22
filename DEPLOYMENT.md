# Kruit & Kramer ICT Ticketportaal - Deployment Guide

## Versie Informatie
- **Versie:** 1.0.0
- **Datum:** 22 oktober 2025
- **Status:** Production Ready

## Overzicht
Dit document beschrijft hoe je het ICT Ticketportaal kunt deployen op verschillende omgevingen.

## Systeem Vereisten

### Server Requirements
- **PHP:** 7.4 of hoger (8.0+ aanbevolen)
- **MySQL:** 5.7 of hoger (8.0+ aanbevolen)
- **Webserver:** Apache 2.4+ of Nginx
- **Disk Space:** Minimaal 500MB
- **RAM:** Minimaal 512MB (1GB+ aanbevolen)

### PHP Extensions
- mysqli
- pdo_mysql
- mbstring
- json
- session
- fileinfo
- gd (voor image handling)

## Installatie Stappen

### 1. Database Setup

#### Stap 1: Database Aanmaken
```sql
CREATE DATABASE ticketportaal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ticketportaal_user'@'localhost' IDENTIFIED BY 'jouw_wachtwoord';
GRANT ALL PRIVILEGES ON ticketportaal.* TO 'ticketportaal_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Stap 2: Database Schema Importeren
Voer de volgende SQL bestanden uit in deze volgorde:
1. `database/schema.sql` - Basis tabellen
2. `database/migrations/add_location_to_users.sql` - Locatie veld
3. `database/migrations/create_change_requests_table.sql` - Change management
4. `database/migrations/add_sales_facilitair_departments.sql` - Extra afdelingen
5. `database/migrations/add_default_templates.sql` - Sjablonen

### 2. Configuratie

#### Stap 1: Database Configuratie
Kopieer en pas aan:
```bash
cp config/database.example.php config/database.php
```

Bewerk `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticketportaal');
define('DB_USER', 'ticketportaal_user');
define('DB_PASS', 'jouw_wachtwoord');
define('DB_CHARSET', 'utf8mb4');
```

#### Stap 2: Applicatie Configuratie
Bewerk `config/config.php`:
```php
// Site URL (zonder trailing slash)
define('SITE_URL', 'http://jouw-domein.nl/ticketportaal');

// Site naam
define('SITE_NAME', 'Kruit & Kramer Ticketportaal');

// Debug mode (zet op false in productie!)
define('DEBUG_MODE', false);

// Upload directory
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 10485760); // 10MB
```

#### Stap 3: Email Configuratie (optioneel)
Bewerk `config/email.php`:
```php
define('SMTP_HOST', 'smtp.jouw-provider.nl');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@kruitkramer.nl');
define('SMTP_PASSWORD', 'jouw_smtp_wachtwoord');
define('SMTP_FROM_EMAIL', 'noreply@kruitkramer.nl');
define('SMTP_FROM_NAME', 'Kruit & Kramer ICT');
```

### 3. Bestandspermissies

```bash
# Maak upload directory aan
mkdir -p uploads
chmod 755 uploads

# Maak logs directory aan
mkdir -p logs
chmod 755 logs

# Zorg dat webserver kan schrijven
chown -R www-data:www-data uploads logs
```

### 4. Apache Configuratie

#### .htaccess (al aanwezig in project)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Security headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
```

#### Virtual Host Voorbeeld
```apache
<VirtualHost *:80>
    ServerName ticketportaal.kruitkramer.nl
    DocumentRoot /var/www/ticketportaal
    
    <Directory /var/www/ticketportaal>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ticketportaal-error.log
    CustomLog ${APACHE_LOG_DIR}/ticketportaal-access.log combined
</VirtualHost>
```

### 5. Eerste Admin Account

Na installatie, log in met:
- **Email:** admin@kruitkramer.nl
- **Wachtwoord:** admin123

**BELANGRIJK:** Wijzig dit wachtwoord direct na eerste login!

## Backup Procedures

### Database Backup
```bash
# Handmatige backup
mysqldump -u ticketportaal_user -p ticketportaal > backup_$(date +%Y%m%d_%H%M%S).sql

# Geautomatiseerde dagelijkse backup (cron)
0 2 * * * mysqldump -u ticketportaal_user -p'wachtwoord' ticketportaal > /backups/ticketportaal_$(date +\%Y\%m\%d).sql
```

### Bestanden Backup
```bash
# Backup uploads en configuratie
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz uploads/ config/ logs/
```

### Volledige Backup Script
Maak `backup.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/backups/ticketportaal"
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
mysqldump -u ticketportaal_user -p'wachtwoord' ticketportaal > $BACKUP_DIR/db_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz uploads/ config/ logs/

# Verwijder backups ouder dan 30 dagen
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

## Restore Procedures

### Database Restore
```bash
mysql -u ticketportaal_user -p ticketportaal < backup_20251022_120000.sql
```

### Bestanden Restore
```bash
tar -xzf backup_files_20251022_120000.tar.gz
```

## Security Checklist

- [ ] Debug mode uitgeschakeld (`DEBUG_MODE = false`)
- [ ] Sterke database wachtwoorden
- [ ] Admin wachtwoord gewijzigd
- [ ] HTTPS ingeschakeld (SSL certificaat)
- [ ] Bestandspermissies correct ingesteld
- [ ] Upload directory beveiligd
- [ ] Security headers ingeschakeld
- [ ] PHP error display uitgeschakeld in productie
- [ ] Database gebruiker heeft alleen benodigde rechten
- [ ] Backup procedures getest

## Troubleshooting

### Probleem: Witte pagina / 500 Error
**Oplossing:**
1. Check Apache error log: `tail -f /var/log/apache2/error.log`
2. Controleer PHP error log: `tail -f /var/log/php/error.log`
3. Zet tijdelijk `DEBUG_MODE = true` in config.php
4. Controleer bestandspermissies

### Probleem: Database connectie mislukt
**Oplossing:**
1. Controleer database credentials in `config/database.php`
2. Test database connectie: `mysql -u ticketportaal_user -p`
3. Controleer of MySQL service draait: `systemctl status mysql`

### Probleem: Uploads werken niet
**Oplossing:**
1. Controleer of `uploads/` directory bestaat
2. Controleer permissies: `chmod 755 uploads`
3. Controleer ownership: `chown www-data:www-data uploads`
4. Controleer `MAX_FILE_SIZE` in config.php
5. Controleer PHP `upload_max_filesize` en `post_max_size`

### Probleem: Emails worden niet verzonden
**Oplossing:**
1. Controleer SMTP configuratie in `config/email.php`
2. Test SMTP connectie met telnet: `telnet smtp.provider.nl 587`
3. Controleer firewall regels voor uitgaande SMTP
4. Check logs in `logs/` directory

## Monitoring

### Log Bestanden
- **Application logs:** `logs/app.log`
- **Error logs:** `logs/error.log`
- **Apache access:** `/var/log/apache2/access.log`
- **Apache error:** `/var/log/apache2/error.log`

### Health Check Endpoints
- **Homepage:** `http://jouw-domein.nl/ticketportaal/`
- **Login:** `http://jouw-domein.nl/ticketportaal/login.php`
- **Database:** Check via admin dashboard

## Updates en Maintenance

### Update Procedure
1. Maak volledige backup (database + bestanden)
2. Zet applicatie in maintenance mode
3. Pull nieuwe code van GitHub
4. Voer nieuwe database migraties uit
5. Clear cache indien aanwezig
6. Test functionaliteit
7. Haal maintenance mode weg

### Maintenance Mode
Maak `maintenance.php` in root:
```php
<!DOCTYPE html>
<html>
<head>
    <title>Onderhoud - Kruit & Kramer</title>
</head>
<body>
    <h1>Systeem Onderhoud</h1>
    <p>Het ticketportaal is tijdelijk niet beschikbaar wegens onderhoud.</p>
    <p>Voor dringende zaken: bel 777 (ICT afdeling intern)</p>
</body>
</html>
```

Redirect in `.htaccess`:
```apache
# Uncomment voor maintenance mode
# RewriteCond %{REQUEST_URI} !^/maintenance\.php$
# RewriteRule ^(.*)$ /maintenance.php [R=503,L]
```

## Contact en Support

**ICT Afdeling Kruit & Kramer**
- **Telefoon:** 777 (intern)
- **Email:** support@kruitkramer.nl
- **Ontwikkelaar:** Damian Kessler

## Changelog

### Versie 1.0.0 (22 oktober 2025)
- Initiële productie release
- Ticket management systeem
- Knowledge Base
- CI Management
- Change Management
- Dynamic category fields
- Template systeem met placeholders
- Multi-locatie support met logo switching
- User departments en profiles
- Email notificaties
