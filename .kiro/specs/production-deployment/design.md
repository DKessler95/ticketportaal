# Production Deployment Design - ICT Ticketportaal

## Overview

This design document outlines the production deployment architecture for the ICT Ticketportaal on Windows Server using IIS, PHP, and MySQL. The design focuses on security, reliability, performance, and maintainability for 24/7 operations.

## Architecture

### Production Environment Stack

```
┌─────────────────────────────────────────────────────────────┐
│                    Internet / Users                          │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTPS (443)
                         │ HTTP (80) → Redirect to HTTPS
┌────────────────────────▼────────────────────────────────────┐
│                  Windows Firewall                            │
│  Allow: 80, 443  │  Block: 3306 (external)                  │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                    IIS 10.0+                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Site: ICT Ticketportaal                             │  │
│  │  Binding: tickets.kruit-en-kramer.nl:443 (HTTPS)     │  │
│  │  App Pool: TicketportaalAppPool (No Managed Code)    │  │
│  │  Identity: ApplicationPoolIdentity                    │  │
│  └──────────────────────┬───────────────────────────────┘  │
└─────────────────────────┼──────────────────────────────────┘
                          │
                ┌─────────┴─────────┐
                │                   │
┌───────────────▼─────┐   ┌────────▼──────────────────────────┐
│   FastCGI Handler   │   │  Static Files (CSS/JS/Images)     │
│   php-cgi.exe       │   │  Served directly by IIS           │
└───────────┬─────────┘   └───────────────────────────────────┘
            │
┌───────────▼──────────────────────────────────────────────────┐
│                    PHP 8.2+ (Non-Thread Safe)                │
│  Location: C:\PHP                                            │
│  Extensions: PDO, MySQL, OpenSSL, IMAP, mbstring, etc.      │
└───────────┬──────────────────────────────────────────────────┘
            │
┌───────────▼──────────────────────────────────────────────────┐
│              Application Code                                │
│  Location: C:\inetpub\wwwroot\ticketportaal                 │
│  ┌────────────┬────────────┬────────────┬─────────────┐    │
│  │  /classes  │  /includes │  /admin    │  /user      │    │
│  │  /agent    │  /api      │  /assets   │  /config    │    │
│  └────────────┴────────────┴────────────┴─────────────┘    │
└───────────┬──────────────────────────────────────────────────┘
            │
┌───────────▼──────────────────────────────────────────────────┐
│                    MySQL 8.0+                                │
│  Database: ticketportaal                                     │
│  User: ticketportal_app (limited privileges)                │
│  Character Set: utf8mb4                                      │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│              Windows Task Scheduler                          │
│  ┌────────────────────┬──────────────────────────────────┐  │
│  │  Email Processing  │  Daily Backup  │  Health Check   │  │
│  │  Every 5 min       │  02:00 daily   │  Every 30 min   │  │
│  └────────────────────┴──────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                    Backup Storage                            │
│  Location: D:\Backups\Ticketportaal                         │
│  Retention: 30 days                                          │
│  Contents: Database dumps + Uploads archive                  │
└──────────────────────────────────────────────────────────────┘
```

## Directory Structure

### Production File Layout

```
C:\inetpub\wwwroot\ticketportaal\
├── admin/                      # Admin portal pages
├── agent/                      # Agent portal pages
├── user/                       # User portal pages
├── api/                        # API endpoints
├── assets/
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript files
│   └── images/                 # Images and logos
├── classes/                    # PHP classes (protected)
│   ├── Database.php
│   ├── User.php
│   ├── Ticket.php
│   ├── EmailHandler.php
│   ├── KnowledgeBase.php
│   ├── Category.php
│   └── Report.php
├── config/                     # Configuration files (protected)
│   ├── database.php            # Database credentials
│   ├── email.php               # Email settings
│   ├── config.php              # Application settings
│   └── session.php             # Session configuration
├── database/                   # Database scripts (protected)
│   ├── schema.sql
│   ├── seed.sql
│   └── ci_change_migration.sql
├── includes/                   # Shared includes
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   ├── functions.php
│   └── PHPMailer/              # Email library
├── logs/                       # Application logs (writable, protected)
│   ├── app.log
│   ├── email_processing.log
│   └── error.log
├── uploads/                    # User uploads (writable)
│   └── tickets/                # Ticket attachments
├── web.config                  # IIS configuration
├── index.php                   # Landing page
├── login.php                   # Login page
├── register.php                # Registration page
├── logout.php                  # Logout handler
├── knowledge_base.php          # Public KB
└── email_to_ticket.php         # Email processing script

C:\PHP\
├── php.exe                     # PHP CLI
├── php-cgi.exe                 # FastCGI executable
├── php.ini                     # PHP configuration
├── ext/                        # PHP extensions
└── logs/                       # PHP error logs
    └── php_errors.log

C:\Scripts\
├── backup_ticketportaal.ps1    # Backup script
├── health_check.ps1            # Health monitoring
└── disk_space_check.ps1        # Disk monitoring

D:\Backups\Ticketportaal\
├── database_YYYYMMDD_HHMMSS.sql
├── uploads_YYYYMMDD_HHMMSS.zip
└── backup_log.txt
```

## Components and Interfaces

### 1. IIS Configuration

#### web.config Structure

The web.config file is the IIS equivalent of Apache's .htaccess and controls:

**URL Rewriting:**
- HTTP to HTTPS redirect
- Clean URLs (if needed)

**Security Headers:**
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin
- Remove X-Powered-By header

**Request Filtering:**
- Hide sensitive directories (/config, /classes, /database, /logs, /.git)
- Set max content length (50MB for file uploads)
- Block dangerous HTTP methods (TRACE)

**Default Document:**
- Set index.php as default

#### Application Pool Configuration

**Settings:**
- .NET CLR Version: No Managed Code (pure PHP)
- Managed Pipeline Mode: Integrated
- Identity: ApplicationPoolIdentity (least privilege)
- Start Mode: AlwaysRunning (for better performance)
- Idle Timeout: 20 minutes
- Regular Time Interval: 1740 minutes (29 hours - prevents daily recycling)

### 2. PHP Configuration

#### php.ini Production Settings

**Performance:**
```ini
max_execution_time = 60
memory_limit = 256M
max_input_time = 60
```

**File Uploads:**
```ini
upload_max_filesize = 50M
post_max_size = 64M
file_uploads = On
```

**Error Handling (Production):**
```ini
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = "C:\PHP\logs\php_errors.log"
```

**Security:**
```ini
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

**Session Security:**
```ini
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"
session.gc_maxlifetime = 1800
```

**Timezone:**
```ini
date.timezone = Europe/Amsterdam
```

### 3. Database Configuration

#### MySQL Production Settings

**Character Set:**
- Default: utf8mb4
- Collation: utf8mb4_unicode_ci

**User Privileges:**
```sql
-- Dedicated application user (NOT root)
CREATE USER 'ticketportal_app'@'localhost' 
  IDENTIFIED BY 'STRONG_PASSWORD';

-- Minimal required privileges
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER 
  ON ticketportaal.* 
  TO 'ticketportal_app'@'localhost';
```

**Connection Settings:**
- Host: localhost (no remote access)
- Port: 3306 (blocked from external access)
- Max connections: 100
- Connection timeout: 10 seconds

### 4. File Permissions

#### Windows ACL Configuration

**Application Root (C:\inetpub\wwwroot\ticketportaal):**
- IIS AppPool\TicketportaalAppPool: Read (R)
- BUILTIN\Administrators: Full Control (F)

**Writable Directories:**
- /uploads: IIS AppPool\TicketportaalAppPool: Modify (M)
- /logs: IIS AppPool\TicketportaalAppPool: Modify (M)

**Protected Directories:**
- /config: IIS AppPool\TicketportaalAppPool: Read (R) only
- /classes: IIS AppPool\TicketportaalAppPool: Read (R) only
- /database: IIS AppPool\TicketportaalAppPool: Read (R) only

**Inheritance:**
- Disable inheritance on /config to prevent accidental permission changes

### 5. SSL/TLS Configuration

#### Certificate Options

**Option A: Let's Encrypt (Free)**
- Tool: Certify The Web or win-acme
- Auto-renewal: Every 60 days
- Validation: HTTP-01 or DNS-01

**Option B: Commercial Certificate**
- Provider: Sectigo, DigiCert, GlobalSign
- Validity: 1-2 years
- Wildcard: Optional (*.kruit-en-kramer.nl)

#### IIS SSL Settings

**Binding:**
- Type: https
- Port: 443
- SSL Certificate: Selected certificate
- Require SNI: Yes (if multiple sites)

**SSL/TLS Protocols:**
- Enable: TLS 1.2, TLS 1.3
- Disable: SSL 2.0, SSL 3.0, TLS 1.0, TLS 1.1

**Cipher Suites:**
- Use strong ciphers only
- Disable weak ciphers (RC4, DES, 3DES)

### 6. Backup System

#### Backup Script Design

**PowerShell Script: backup_ticketportaal.ps1**

**Components:**
1. **Database Backup:**
   - Use mysqldump to export full database
   - Filename: database_YYYYMMDD_HHMMSS.sql
   - Compression: Optional (gzip)

2. **File Backup:**
   - Compress /uploads directory
   - Filename: uploads_YYYYMMDD_HHMMSS.zip
   - Exclude temporary files

3. **Retention:**
   - Keep backups for 30 days
   - Automatically delete older backups

4. **Logging:**
   - Log success/failure to backup_log.txt
   - Email alert on failure

5. **Verification:**
   - Check backup file size > 0
   - Verify SQL file is valid

**Schedule:**
- Daily at 02:00 (low traffic time)
- Run as SYSTEM account
- Run whether user is logged on or not

### 7. Email Processing

#### Task Scheduler Configuration

**Task: Ticketportaal Email Processing**

**Trigger:**
- Daily
- Repeat every: 5 minutes
- Duration: Indefinitely
- Enabled: Yes

**Action:**
- Program: C:\PHP\php.exe
- Arguments: C:\inetpub\wwwroot\ticketportaal\email_to_ticket.php
- Start in: C:\inetpub\wwwroot\ticketportaal

**Settings:**
- Run with highest privileges: Yes
- Run whether user is logged on or not: Yes
- Hidden: Yes
- Stop task if runs longer than: 10 minutes

**Error Handling:**
- Log errors to logs/email_processing.log
- Email alert if consecutive failures > 3

### 8. Monitoring and Health Checks

#### Health Check Script Design

**PowerShell Script: health_check.ps1**

**Checks:**
1. **Application Availability:**
   - HTTP GET to https://tickets.kruit-en-kramer.nl/api/health_check.php
   - Expected: 200 OK response
   - Timeout: 10 seconds

2. **Database Connectivity:**
   - Test MySQL connection
   - Expected: Successful connection

3. **Disk Space:**
   - Check C: drive free space
   - Alert if < 20GB free

4. **Service Status:**
   - Check IIS service running
   - Check MySQL service running

**Alerting:**
- Email to ict@kruit-en-kramer.nl on failure
- Log all checks to D:\Logs\health_check.log

**Schedule:**
- Every 30 minutes
- Run as SYSTEM account

#### Application Health Check Endpoint

**File: api/health_check.php**

```php
<?php
// Simple health check endpoint
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check database
try {
    require_once __DIR__ . '/../classes/Database.php';
    $db = Database::getInstance();
    $db->query("SELECT 1");
    $health['checks']['database'] = 'ok';
} catch (Exception $e) {
    $health['status'] = 'error';
    $health['checks']['database'] = 'failed';
}

// Check writable directories
$health['checks']['uploads_writable'] = is_writable(__DIR__ . '/../uploads');
$health['checks']['logs_writable'] = is_writable(__DIR__ . '/../logs');

if (!$health['checks']['uploads_writable'] || !$health['checks']['logs_writable']) {
    $health['status'] = 'warning';
}

http_response_code($health['status'] === 'ok' ? 200 : 500);
echo json_encode($health, JSON_PRETTY_PRINT);
```

## Security Implementation

### 1. Windows Firewall Rules

**Inbound Rules:**
```powershell
# Allow HTTP (will redirect to HTTPS)
New-NetFirewallRule -DisplayName "HTTP" -Direction Inbound `
  -Protocol TCP -LocalPort 80 -Action Allow

# Allow HTTPS
New-NetFirewallRule -DisplayName "HTTPS" -Direction Inbound `
  -Protocol TCP -LocalPort 443 -Action Allow

# Block MySQL from external
New-NetFirewallRule -DisplayName "Block MySQL External" `
  -Direction Inbound -Protocol TCP -LocalPort 3306 -Action Block `
  -RemoteAddress Internet
```

### 2. IIS Security Hardening

**Request Filtering:**
- Max URL length: 4096
- Max query string: 2048
- Max content length: 52428800 (50MB)
- Block high-bit characters: Yes
- Block unlisted file extensions: No (allow all for flexibility)

**HTTP Methods:**
- Allow: GET, POST, HEAD
- Block: TRACE, TRACK, OPTIONS (unless needed)

**IP Security:**
- Optional: Whitelist specific IP ranges if needed
- Rate limiting: Consider using IIS Dynamic IP Restrictions

### 3. Application-Level Security

**Configuration Files:**
- Store outside web root if possible
- Use environment variables for sensitive data (alternative)
- Encrypt database passwords (optional, using Windows DPAPI)

**Session Management:**
- Regenerate session ID on login
- Implement CSRF tokens on all forms
- 30-minute inactivity timeout
- Secure and HttpOnly cookies

**Input Validation:**
- Sanitize all user inputs
- Use prepared statements for database queries
- Validate file uploads (type, size, content)

**Output Encoding:**
- Use htmlspecialchars() for all output
- Implement Content Security Policy headers

## Performance Optimization

### 1. IIS Optimization

**Static Content Caching:**
```xml
<staticContent>
  <clientCache cacheControlMode="UseMaxAge" cacheControlMaxAge="7.00:00:00" />
</staticContent>
```

**Compression:**
```xml
<httpCompression>
  <scheme name="gzip" dll="%Windir%\system32\inetsrv\gzip.dll" />
  <dynamicTypes>
    <add mimeType="text/*" enabled="true" />
    <add mimeType="application/javascript" enabled="true" />
    <add mimeType="application/json" enabled="true" />
  </dynamicTypes>
  <staticTypes>
    <add mimeType="text/*" enabled="true" />
    <add mimeType="application/javascript" enabled="true" />
    <add mimeType="image/svg+xml" enabled="true" />
  </staticTypes>
</httpCompression>
```

### 2. PHP Optimization

**OPcache:**
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

**Composer Optimization:**
```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

### 3. Database Optimization

**Indexes:**
- Ensure all foreign keys have indexes
- Add indexes on frequently queried columns (status, created_at, email)
- Use composite indexes where appropriate

**Query Optimization:**
- Use EXPLAIN to analyze slow queries
- Implement query caching where appropriate
- Use JOINs instead of multiple queries

**Connection Pooling:**
- Use persistent connections sparingly (can cause issues)
- Implement connection pooling at application level if needed

## Deployment Process

### Pre-Deployment Checklist

**Environment:**
- [ ] Windows Server 2022/2025 installed and updated
- [ ] IIS installed with required features
- [ ] PHP 8.2+ installed and configured
- [ ] MySQL 8.0+ installed and configured
- [ ] SSL certificate obtained

**Application:**
- [ ] All code tested on staging/XAMPP
- [ ] Database migrations prepared
- [ ] Configuration files ready (with production values)
- [ ] Backup scripts tested

**Security:**
- [ ] Firewall rules configured
- [ ] File permissions set correctly
- [ ] web.config security headers configured
- [ ] Debug mode disabled

### Deployment Steps

1. **Prepare Server** (Day 1)
   - Install IIS, PHP, MySQL
   - Configure FastCGI
   - Set up firewall rules

2. **Deploy Application** (Day 2)
   - Copy files to C:\inetpub\wwwroot\ticketportaal
   - Create IIS site and application pool
   - Set file permissions
   - Create web.config

3. **Configure Database** (Day 2)
   - Create database and user
   - Import schema and seed data
   - Configure connection settings

4. **Install SSL** (Day 3)
   - Obtain certificate
   - Configure HTTPS binding
   - Test HTTPS redirect

5. **Set Up Automation** (Day 3)
   - Create backup script
   - Create health check script
   - Configure Task Scheduler tasks

6. **Testing** (Day 4-5)
   - Functional testing
   - Security testing
   - Performance testing
   - User acceptance testing

7. **Go-Live** (Day 6)
   - Final backup
   - Update DNS
   - Monitor closely

8. **Post-Deployment** (Day 7+)
   - Daily log reviews
   - User feedback collection
   - Bug fixes as needed

## Rollback Plan

### Emergency Rollback Procedure

**If critical issues occur:**

1. **Stop IIS Site:**
   ```powershell
   Stop-Website -Name "ICT Ticketportaal"
   ```

2. **Restore Database:**
   ```cmd
   mysql -u ticketportal_app -p ticketportaal < D:\Backups\Ticketportaal\database_LATEST.sql
   ```

3. **Restore Files:**
   ```powershell
   Expand-Archive -Path "D:\Backups\Ticketportaal\uploads_LATEST.zip" `
     -DestinationPath "C:\inetpub\wwwroot\ticketportaal\uploads" -Force
   ```

4. **Restart Services:**
   ```powershell
   Start-Website -Name "ICT Ticketportaal"
   iisreset
   ```

5. **Verify:**
   - Test application access
   - Check database connectivity
   - Verify file uploads work

**Rollback Time Estimate:** 15-30 minutes

## Maintenance Procedures

### Daily Tasks
- Review error logs (PHP, IIS, application)
- Check backup success
- Monitor disk space

### Weekly Tasks
- Review security logs
- Check for Windows updates
- Review performance metrics
- Test backup restoration (monthly)

### Monthly Tasks
- Apply Windows updates
- Update PHP (patch versions)
- Update MySQL (patch versions)
- Review and optimize database
- Security audit

### Quarterly Tasks
- Major version updates (PHP, MySQL)
- Full security audit
- Disaster recovery drill
- Performance review and optimization
- SSL certificate renewal check

## Troubleshooting Guide

### Common Issues

**Issue: Website not accessible**
- Check IIS service running
- Check Application Pool running
- Check firewall rules
- Check DNS resolution
- Review IIS error logs

**Issue: Database connection errors**
- Check MySQL service running
- Verify credentials in config/database.php
- Test connection manually
- Check MySQL error log

**Issue: File upload errors**
- Check /uploads directory permissions
- Verify php.ini upload settings
- Check disk space
- Review PHP error log

**Issue: Email not sending**
- Verify SMTP settings in config/email.php
- Test SMTP connection manually
- Check PHP error log
- Verify firewall allows outbound SMTP

**Issue: Slow performance**
- Check server resource usage (CPU, RAM, disk)
- Review slow query log
- Check for long-running processes
- Consider increasing PHP memory_limit
- Review IIS Application Pool settings

## Success Criteria

**The deployment is successful when:**
- ✅ Application accessible 24/7 via HTTPS
- ✅ All functionality works as tested
- ✅ Automated backups running successfully
- ✅ Monitoring and alerts operational
- ✅ Security hardened per best practices
- ✅ Performance meets requirements (< 2s page load)
- ✅ Users can work without issues
- ✅ No critical bugs in first week
- ✅ Documentation complete and accurate
- ✅ Knowledge transfer completed
