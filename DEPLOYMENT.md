# ICT Ticketportaal - Deployment Guide

## Server Requirements

### Minimum Requirements

- **PHP**: 7.4 or higher
  - Required extensions: PDO, PDO_MySQL, mbstring, openssl, imap, fileinfo
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL Certificate**: Required for production deployment
- **Disk Space**: Minimum 500MB (more for file uploads)
- **Memory**: Minimum 512MB RAM
- **Cron**: Support for scheduled tasks

### Recommended PHP Extensions

```bash
php-pdo
php-mysql
php-mbstring
php-openssl
php-imap
php-fileinfo
php-curl
php-json
php-xml
```

## Installation Steps

### 1. Prepare the Server

#### For Ubuntu/Debian:

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Apache
sudo apt install apache2 -y

# Install PHP and required extensions
sudo apt install php php-pdo php-mysql php-mbstring php-openssl php-imap php-fileinfo php-curl php-json php-xml -y

# Install MySQL
sudo apt install mysql-server -y

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

#### For CentOS/RHEL:

```bash
# Update system packages
sudo yum update -y

# Install Apache
sudo yum install httpd -y

# Install PHP and required extensions
sudo yum install php php-pdo php-mysqlnd php-mbstring php-openssl php-imap php-fileinfo php-curl php-json php-xml -y

# Install MySQL
sudo yum install mysql-server -y

# Start services
sudo systemctl start httpd
sudo systemctl start mysqld
sudo systemctl enable httpd
sudo systemctl enable mysqld
```

### 2. Clone or Upload Application Files

```bash
# Navigate to web root
cd /var/www/html

# Clone repository (if using Git)
sudo git clone <repository-url> ticketportaal

# Or upload files via FTP/SFTP to /var/www/html/ticketportaal

# Set proper ownership
sudo chown -R www-data:www-data ticketportaal
# For CentOS/RHEL use: sudo chown -R apache:apache ticketportaal
```

### 3. Configure File Permissions

```bash
cd /var/www/html/ticketportaal

# Set directory permissions
sudo find . -type d -exec chmod 755 {} \;

# Set file permissions
sudo find . -type f -exec chmod 644 {} \;

# Make uploads directory writable
sudo chmod 775 uploads/
sudo chmod 775 uploads/tickets/

# Make logs directory writable
sudo chmod 775 logs/

# Protect sensitive directories
sudo chmod 700 config/
sudo chmod 700 classes/
```

### 4. Create MySQL Database

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE ticketportaal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ticketuser'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON ticketportaal.* TO 'ticketuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Import Database Schema

```bash
# Import schema
mysql -u ticketuser -p ticketportaal < database/schema.sql

# Import seed data (optional - includes default categories and admin user)
mysql -u ticketuser -p ticketportaal < database/seed.sql
```

### 6. Configure Application

```bash
# Copy example configuration files
cp config/database.example.php config/database.php
cp config/email.example.php config/email.php

# Edit configuration files with your settings
nano config/database.php
nano config/email.php
nano config/config.php
```

**Important**: Update the following in each config file:
- Database credentials in `config/database.php`
- Email server settings in `config/email.php`
- Site URL and paths in `config/config.php`

### 7. Configure Web Server

#### Apache Configuration

Create a virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/ticketportaal.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName tickets.kruit-en-kramer.nl
    ServerAdmin admin@kruit-en-kramer.nl
    DocumentRoot /var/www/html/ticketportaal

    <Directory /var/www/html/ticketportaal>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Protect sensitive directories
    <Directory /var/www/html/ticketportaal/config>
        Require all denied
    </Directory>

    <Directory /var/www/html/ticketportaal/classes>
        Require all denied
    </Directory>

    <Directory /var/www/html/ticketportaal/logs>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ticketportaal_error.log
    CustomLog ${APACHE_LOG_DIR}/ticketportaal_access.log combined

    # Redirect to HTTPS (after SSL is configured)
    # RewriteEngine On
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite ticketportaal.conf
sudo systemctl reload apache2
```

#### Nginx Configuration

Create a server block:

```bash
sudo nano /etc/nginx/sites-available/ticketportaal
```

Add the following configuration:

```nginx
server {
    listen 80;
    server_name tickets.kruit-en-kramer.nl;
    root /var/www/html/ticketportaal;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Disable directory listing
    autoindex off;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Protect sensitive directories
    location ~ ^/(config|classes|logs|database)/ {
        deny all;
        return 404;
    }

    # Deny access to .htaccess files
    location ~ /\.ht {
        deny all;
    }

    # Redirect to HTTPS (after SSL is configured)
    # return 301 https://$server_name$request_uri;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/ticketportaal /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## SSL Certificate Configuration

### Option 1: Let's Encrypt (Free SSL Certificate)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y
# For Nginx: sudo apt install certbot python3-certbot-nginx -y

# Obtain and install certificate
sudo certbot --apache -d tickets.kruit-en-kramer.nl
# For Nginx: sudo certbot --nginx -d tickets.kruit-en-kramer.nl

# Test automatic renewal
sudo certbot renew --dry-run
```

### Option 2: Commercial SSL Certificate

1. Generate a Certificate Signing Request (CSR):

```bash
sudo openssl req -new -newkey rsa:2048 -nodes \
  -keyout /etc/ssl/private/ticketportaal.key \
  -out /etc/ssl/certs/ticketportaal.csr
```

2. Submit the CSR to your certificate authority
3. Download the certificate files
4. Install the certificate:

#### Apache SSL Configuration:

```bash
sudo nano /etc/apache2/sites-available/ticketportaal-ssl.conf
```

```apache
<VirtualHost *:443>
    ServerName tickets.kruit-en-kramer.nl
    ServerAdmin admin@kruit-en-kramer.nl
    DocumentRoot /var/www/html/ticketportaal

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/ticketportaal.crt
    SSLCertificateKeyFile /etc/ssl/private/ticketportaal.key
    SSLCertificateChainFile /etc/ssl/certs/ticketportaal-chain.crt

    <Directory /var/www/html/ticketportaal>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Protect sensitive directories
    <Directory /var/www/html/ticketportaal/config>
        Require all denied
    </Directory>

    <Directory /var/www/html/ticketportaal/classes>
        Require all denied
    </Directory>

    <Directory /var/www/html/ticketportaal/logs>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ticketportaal_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/ticketportaal_ssl_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite ticketportaal-ssl.conf
sudo systemctl reload apache2
```

#### Nginx SSL Configuration:

```nginx
server {
    listen 443 ssl http2;
    server_name tickets.kruit-en-kramer.nl;
    root /var/www/html/ticketportaal;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/ticketportaal.crt;
    ssl_certificate_key /etc/ssl/private/ticketportaal.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Rest of configuration same as HTTP block...
}
```

## Cron Job Setup for Email Processing

The system requires a cron job to process incoming emails and convert them to tickets.

### Configure Cron Job

```bash
# Edit crontab for web server user
sudo crontab -u www-data -e
# For CentOS/RHEL: sudo crontab -u apache -e
```

Add the following line to run the email processor every 5 minutes:

```cron
*/5 * * * * /usr/bin/php /var/www/html/ticketportaal/email_to_ticket.php >> /var/www/html/ticketportaal/logs/cron.log 2>&1
```

### Verify Cron Job

```bash
# List cron jobs
sudo crontab -u www-data -l

# Monitor cron log
tail -f /var/www/html/ticketportaal/logs/cron.log
```

### Email Configuration Requirements

Ensure the following in `config/email.php`:
- IMAP access enabled for ict@kruit-en-kramer.nl
- Correct IMAP server settings (host, port, username, password)
- SMTP settings for sending emails

### Test Email Processing

```bash
# Run manually to test
sudo -u www-data php /var/www/html/ticketportaal/email_to_ticket.php

# Check for errors
cat /var/www/html/ticketportaal/logs/cron.log
```

## Post-Installation Steps

### 1. Test the Installation

1. Access the application: `https://tickets.kruit-en-kramer.nl`
2. Test user registration
3. Login with default admin account (if seed data was imported):
   - Email: admin@kruit-en-kramer.nl
   - Password: (check seed.sql file)
4. Create a test ticket
5. Test email notifications
6. Verify file uploads work

### 2. Security Hardening

```bash
# Disable PHP information disclosure
sudo nano /etc/php/7.4/apache2/php.ini
# Set: expose_php = Off

# Set secure session settings (should already be in config/session.php)
# session.cookie_httponly = 1
# session.cookie_secure = 1
# session.use_strict_mode = 1

# Restart web server
sudo systemctl restart apache2
# For Nginx: sudo systemctl restart php7.4-fpm
```

### 3. Configure Backups

```bash
# Create backup script
sudo nano /usr/local/bin/backup-ticketportaal.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/ticketportaal"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u ticketuser -p'your_password' ticketportaal > $BACKUP_DIR/db_$DATE.sql

# Backup uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/html/ticketportaal/uploads/

# Remove backups older than 30 days
find $BACKUP_DIR -type f -mtime +30 -delete
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-ticketportaal.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-ticketportaal.sh
```

### 4. Configure Monitoring

Set up monitoring for:
- Web server uptime
- Database connectivity
- Disk space usage
- Email processing errors
- Application error logs

### 5. Change Default Credentials

**IMPORTANT**: If you imported seed data, change the default admin password immediately:

1. Login as admin
2. Navigate to profile settings
3. Change password to a strong, unique password

## Troubleshooting

### Common Issues

#### 1. Database Connection Errors

```bash
# Check MySQL is running
sudo systemctl status mysql

# Verify credentials
mysql -u ticketuser -p ticketportaal

# Check config/database.php settings
```

#### 2. File Upload Errors

```bash
# Check directory permissions
ls -la /var/www/html/ticketportaal/uploads/

# Verify PHP upload settings
php -i | grep upload

# Check php.ini settings:
# upload_max_filesize = 10M
# post_max_size = 12M
```

#### 3. Email Not Sending

```bash
# Check email configuration
cat config/email.php

# Test SMTP connection
telnet mail.kruit-en-kramer.nl 587

# Check PHP mail logs
tail -f /var/log/mail.log
```

#### 4. Cron Job Not Running

```bash
# Check cron service
sudo systemctl status cron

# Verify crontab entry
sudo crontab -u www-data -l

# Check cron log
tail -f /var/www/html/ticketportaal/logs/cron.log

# Test manual execution
sudo -u www-data php /var/www/html/ticketportaal/email_to_ticket.php
```

#### 5. Permission Denied Errors

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/html/ticketportaal

# Fix permissions
sudo chmod 775 /var/www/html/ticketportaal/uploads/
sudo chmod 775 /var/www/html/ticketportaal/logs/
```

## Maintenance

### Regular Maintenance Tasks

1. **Weekly**:
   - Review error logs
   - Check disk space usage
   - Verify backups are running

2. **Monthly**:
   - Update system packages
   - Review security patches
   - Clean up old log files
   - Optimize database tables

3. **Quarterly**:
   - Review user accounts
   - Audit security settings
   - Test backup restoration
   - Review performance metrics

### Log Files

- Application errors: `/var/www/html/ticketportaal/logs/app.log`
- Cron job output: `/var/www/html/ticketportaal/logs/cron.log`
- Apache errors: `/var/log/apache2/ticketportaal_error.log`
- Nginx errors: `/var/log/nginx/error.log`

### Database Maintenance

```bash
# Optimize tables
mysql -u ticketuser -p ticketportaal -e "OPTIMIZE TABLE tickets, users, ticket_comments, ticket_attachments, knowledge_base;"

# Check table integrity
mysql -u ticketuser -p ticketportaal -e "CHECK TABLE tickets, users;"
```

## Upgrading

### Application Updates

```bash
# Backup current installation
sudo cp -r /var/www/html/ticketportaal /var/www/html/ticketportaal.backup

# Pull latest changes (if using Git)
cd /var/www/html/ticketportaal
sudo git pull origin main

# Run any database migrations
mysql -u ticketuser -p ticketportaal < database/migrations/update_xxx.sql

# Clear any caches
sudo rm -rf /var/www/html/ticketportaal/cache/*

# Restart web server
sudo systemctl restart apache2
```

## Support

For technical support or questions:
- Email: admin@kruit-en-kramer.nl
- Documentation: See README.md and inline code comments

## Security Considerations

- Keep PHP and MySQL updated
- Regularly review access logs
- Monitor for suspicious activity
- Use strong passwords
- Enable two-factor authentication (future enhancement)
- Regular security audits
- Keep SSL certificates up to date

---

**Last Updated**: January 2025
**Version**: 1.0
