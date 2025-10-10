# Configuration Files

This directory contains configuration files for the ICT Ticketportaal application.

## Setup Instructions

### 1. Create Configuration Files

Copy the example configuration files and customize them for your environment:

```bash
# Copy database configuration
cp database.example.php database.php

# Copy email configuration
cp email.example.php email.php
```

### 2. Configure Database Settings

Edit `database.php` and update the following:

- **DB_HOST**: Your MySQL server hostname (usually `localhost`)
- **DB_NAME**: Database name (default: `ticketportaal`)
- **DB_USER**: Database username
- **DB_PASS**: Database password (use a strong password!)

Example:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticketportaal');
define('DB_USER', 'ticketuser');
define('DB_PASS', 'your_secure_password_here');
```

### 3. Configure Email Settings

Edit `email.php` and update the following:

#### SMTP Settings (for sending emails):
- **SMTP_HOST**: Your email server hostname
- **SMTP_PORT**: SMTP port (587 for TLS, 465 for SSL)
- **SMTP_USER**: Your email address
- **SMTP_PASS**: Your email password

#### IMAP Settings (for receiving emails):
- **IMAP_HOST**: IMAP server with format `{hostname:port/protocol}INBOX`
- **IMAP_USER**: Your email address (usually same as SMTP)
- **IMAP_PASS**: Your email password (usually same as SMTP)

Example for Collax:
```php
define('SMTP_HOST', 'mail.kruit-en-kramer.nl');
define('SMTP_PORT', 587);
define('SMTP_USER', 'ict@kruit-en-kramer.nl');
define('SMTP_PASS', 'your_password_here');

define('IMAP_HOST', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX');
define('IMAP_USER', 'ict@kruit-en-kramer.nl');
define('IMAP_PASS', 'your_password_here');
```

### 4. Configure Application Settings

Edit `config.php` if needed to customize:

- Site URL
- Session timeout
- File upload limits
- Upload paths

## Security Notes

⚠️ **IMPORTANT SECURITY CONSIDERATIONS:**

1. **Never commit actual configuration files to version control**
   - Only commit `.example.php` files
   - Add `database.php` and `email.php` to `.gitignore`

2. **Protect configuration directory**
   - Set restrictive permissions: `chmod 700 config/`
   - Ensure web server cannot serve these files directly
   - Use `.htaccess` or server configuration to deny access

3. **Use strong passwords**
   - Database passwords should be at least 16 characters
   - Email passwords should follow your email provider's requirements
   - Never use default or common passwords

4. **Secure file permissions**
   ```bash
   # Set ownership to web server user
   chown -R www-data:www-data config/
   
   # Set restrictive permissions
   chmod 700 config/
   chmod 600 config/*.php
   ```

## Configuration Files

### Required Files

- **database.php** - Database connection settings (copy from database.example.php)
- **email.php** - Email server settings (copy from email.example.php)
- **config.php** - Application settings (already exists)
- **session.php** - Session configuration (already exists)

### Example Files (Templates)

- **database.example.php** - Template for database configuration
- **email.example.php** - Template for email configuration

## Testing Configuration

### Test Database Connection

```bash
# Try connecting to MySQL with your credentials
mysql -h localhost -u ticketuser -p ticketportaal

# If successful, you should see the MySQL prompt
# Type 'exit' to quit
```

### Test Email Configuration

1. Create a test ticket through the web interface
2. Check if confirmation email is received
3. Check `logs/app.log` for any email errors

### Test Email Processing

```bash
# Send a test email to ict@kruit-en-kramer.nl
# Then run the email processor manually:
php /path/to/ticketportaal/email_to_ticket.php

# Check if ticket was created in the system
# Check logs/cron.log for any errors
```

## Troubleshooting

### Database Connection Issues

1. Verify MySQL is running: `sudo systemctl status mysql`
2. Check credentials are correct
3. Ensure database exists: `SHOW DATABASES;`
4. Verify user has privileges: `SHOW GRANTS FOR 'ticketuser'@'localhost';`

### Email Issues

1. **SMTP not working:**
   - Test connection: `telnet mail.kruit-en-kramer.nl 587`
   - Check firewall allows outbound SMTP
   - Verify credentials are correct
   - Check PHP has openssl extension: `php -m | grep openssl`

2. **IMAP not working:**
   - Check PHP has imap extension: `php -m | grep imap`
   - Verify IMAP_HOST format is correct
   - Test credentials by logging into webmail
   - Check mailbox has unread emails

3. **Emails not sending:**
   - Check `logs/app.log` for errors
   - Verify `ENABLE_EMAIL_NOTIFICATIONS` is true
   - Check spam folder on recipient side
   - Verify FROM_EMAIL is valid

## Environment-Specific Configurations

### Development Environment

```php
// database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticketportaal_dev');
define('DB_USER', 'dev_user');
define('DB_PASS', 'dev_password');

// email.php - Use Mailtrap or similar
define('SMTP_HOST', 'smtp.mailtrap.io');
define('ENABLE_EMAIL_NOTIFICATIONS', true);
```

### Production Environment

```php
// database.php
define('DB_HOST', 'db.kruit-en-kramer.nl');
define('DB_NAME', 'ticketportaal_prod');
define('DB_USER', 'prod_user');
define('DB_PASS', 'very_secure_password');

// email.php - Use actual mail server
define('SMTP_HOST', 'mail.kruit-en-kramer.nl');
define('ENABLE_EMAIL_NOTIFICATIONS', true);
```

### Testing Environment

```php
// database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticketportaal_test');
define('DB_USER', 'test_user');
define('DB_PASS', 'test_password');

// email.php - Disable emails or use test server
define('ENABLE_EMAIL_NOTIFICATIONS', false);
```

## Additional Resources

- See `DEPLOYMENT.md` in the root directory for full deployment instructions
- See `README.md` in the root directory for application overview
- See `SECURITY.md` for security best practices

## Support

For configuration assistance:
- Check the example files for detailed comments
- Review the deployment documentation
- Contact: admin@kruit-en-kramer.nl
