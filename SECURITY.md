# Security Implementation Guide

This document describes the security measures implemented in the ICT Ticketportaal and provides guidance for maintaining security.

## Overview

The ICT Ticketportaal implements comprehensive security measures following industry best practices to protect user data, prevent common web vulnerabilities, and ensure system integrity.

## Implemented Security Measures

### 1. Input Validation and Sanitization

**Location:** `includes/functions.php`

All user inputs are validated and sanitized before processing:

- **Email Validation:** `validateEmail()` - Validates email format and checks DNS records
- **Required Field Validation:** `validateRequired()` - Ensures required fields are not empty
- **Length Validation:** `validateLength()` - Validates minimum and maximum field lengths
- **Password Strength:** `validatePassword()` - Enforces 8+ characters with letters and numbers
- **Integer Validation:** `validateInteger()` - Validates numeric inputs
- **Enum Validation:** `validateEnum()` - Validates against allowed values
- **Text Sanitization:** `sanitizeInput()`, `sanitizeText()` - Removes malicious content
- **HTML Sanitization:** `sanitizeHTML()` - Allows only safe HTML tags for rich content
- **File Upload Validation:** `validateFileUpload()` - Validates file type, size, and integrity

**Usage Example:**
```php
// Validate email
if (!validateEmail($email)) {
    $errors[] = 'Invalid email address';
}

// Validate password strength
$passwordCheck = validatePassword($password);
if (!$passwordCheck['valid']) {
    $errors[] = $passwordCheck['error'];
}

// Sanitize text input
$title = sanitizeInput($_POST['title']);
```

### 2. CSRF Protection

**Location:** `includes/functions.php`

Cross-Site Request Forgery (CSRF) protection is implemented on all forms:

- **Token Generation:** `generateCSRFToken()` - Creates secure random tokens
- **Token Validation:** `validateCSRFToken()` - Verifies token authenticity using timing-safe comparison
- **Helper Functions:** `outputCSRFField()`, `verifyCSRFToken()` - Simplify implementation

**Usage Example:**
```php
// In form HTML
<form method="POST">
    <?php outputCSRFField(); ?>
    <!-- form fields -->
</form>

// In form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken(); // Dies if token is invalid
    // Process form...
}
```

**Protected Forms:**
- Login form (`login.php`)
- Registration form (`register.php`)
- Password reset forms (`request_reset.php`, `reset_password.php`)
- Ticket creation form (`user/create_ticket.php`)
- All admin forms (user management, category management, etc.)
- All agent forms (ticket updates, comments, etc.)

### 3. Secure Session Management

**Location:** `config/session.php`, `includes/functions.php`

Comprehensive session security configuration:

**Session Settings:**
- `session.cookie_httponly = 1` - Prevents JavaScript access to cookies (XSS protection)
- `session.use_only_cookies = 1` - Prevents session ID in URLs
- `session.cookie_secure = 1` - Requires HTTPS for cookie transmission
- `session.use_strict_mode = 1` - Rejects uninitialized session IDs
- `session.cookie_samesite = Strict` - Prevents CSRF attacks
- Custom session name - Hides PHP usage from attackers
- SHA-256 hashing for session IDs
- 30-minute inactivity timeout
- Automatic session regeneration every 30 minutes

**Session Timeout:**
- Configured in `config/config.php`: `SESSION_TIMEOUT = 1800` (30 minutes)
- Automatically logs out inactive users
- Checked on every page load via `checkLogin()`

**Usage:**
```php
// Initialize secure session
initSession();

// Check if user is logged in
if (!checkLogin()) {
    redirectTo('/login.php');
}
```

### 4. Apache Security Configuration

**Location:** `.htaccess` and directory-specific `.htaccess` files

Comprehensive Apache security hardening:

**Main .htaccess Features:**
- Disables directory browsing
- Forces HTTPS redirect
- Sets security headers (X-Frame-Options, X-XSS-Protection, CSP, etc.)
- Protects sensitive files and directories
- Prevents PHP execution in upload directories
- Enables GZIP compression
- Sets cache control for performance
- Limits request methods and body size

**Security Headers:**
- `X-Frame-Options: SAMEORIGIN` - Prevents clickjacking
- `X-XSS-Protection: 1; mode=block` - Enables browser XSS protection
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `Content-Security-Policy` - Restricts resource loading
- `Referrer-Policy` - Controls referrer information
- `Permissions-Policy` - Restricts browser features

**Protected Directories:**
Each sensitive directory has its own `.htaccess` with `Require all denied`:
- `/config/` - Configuration files
- `/classes/` - PHP classes
- `/includes/` - Include files
- `/logs/` - Log files
- `/database/` - Database scripts

**Upload Directory Protection:**
- `/uploads/` - Allows file downloads but prevents PHP execution
- Disables script execution
- Sets safe MIME types

### 5. Password Security

**Implementation:** `classes/User.php`

- **Hashing:** Uses `password_hash()` with `PASSWORD_BCRYPT`
- **Cost Factor:** 12 (configurable for performance/security balance)
- **Verification:** Uses `password_verify()` for timing-safe comparison
- **Strength Requirements:**
  - Minimum 8 characters
  - Must contain letters
  - Must contain numbers
  - Configurable via `PASSWORD_MIN_LENGTH` constant

### 6. SQL Injection Prevention

**Implementation:** `classes/Database.php`

All database queries use prepared statements with parameter binding:

```php
// Example from Database class
$stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

**Never use:**
- String concatenation in queries
- Direct variable insertion
- Unparameterized queries

### 7. XSS Prevention

**Implementation:** Throughout application

- All output is escaped using `escapeOutput()` or `htmlspecialchars()`
- Rich text content is sanitized with `sanitizeHTML()`
- Content Security Policy headers restrict inline scripts
- Input validation removes malicious content

**Usage:**
```php
// Always escape output
echo escapeOutput($userInput);

// For rich content (KB articles)
echo sanitizeHTML($richContent);
```

### 8. File Upload Security

**Implementation:** `includes/functions.php`, upload handling code

- **Type Whitelist:** Only allowed extensions (pdf, doc, docx, jpg, png, txt, zip)
- **Size Limit:** Maximum 10MB per file
- **Random Filenames:** Prevents directory traversal and overwrites
- **Separate Storage:** Files stored outside web root when possible
- **PHP Execution Disabled:** `.htaccess` prevents script execution in uploads
- **MIME Type Validation:** Validates actual file content, not just extension

**Configuration:**
```php
// In config/config.php
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'zip']);
```

### 9. Failed Login Protection

**Implementation:** `classes/User.php`

- Tracks failed login attempts by IP address
- Locks account after 5 failed attempts within 15 minutes
- Lock duration: 30 minutes
- Generic error messages (doesn't reveal if email exists)
- Logs all failed attempts with IP and timestamp

**Configuration:**
```php
// In config/config.php
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900);      // 15 minutes
define('ACCOUNT_LOCK_DURATION', 1800);    // 30 minutes
```

### 10. Error Handling and Logging

**Implementation:** `includes/functions.php`

- Centralized error logging via `logError()`
- Logs include timestamp, context, user ID, IP address
- User-friendly error messages (never expose system details)
- Separate log files by date
- Debug mode can be disabled in production

**Usage:**
```php
logError('Database', 'Connection failed', [
    'host' => DB_HOST,
    'error' => $e->getMessage()
]);
```

## Deployment Checklist

### Before Going Live:

1. **SSL/TLS Certificate**
   - [ ] Install valid SSL certificate
   - [ ] Verify HTTPS redirect works
   - [ ] Enable HSTS header (uncomment in `.htaccess`)

2. **Configuration**
   - [ ] Set `DEBUG_MODE = false` in `config/config.php`
   - [ ] Update `SITE_URL` to production domain
   - [ ] Configure database credentials
   - [ ] Configure email settings
   - [ ] Set secure `session.cookie_secure = 1`

3. **File Permissions**
   - [ ] Set files to 644: `find . -type f -exec chmod 644 {} \;`
   - [ ] Set directories to 755: `find . -type d -exec chmod 755 {} \;`
   - [ ] Make uploads directory writable: `chmod 755 uploads/`
   - [ ] Make logs directory writable: `chmod 755 logs/`

4. **Directory Protection**
   - [ ] Verify `.htaccess` files are in place
   - [ ] Test that sensitive directories return 403 Forbidden
   - [ ] Verify uploads directory doesn't execute PHP

5. **Security Testing**
   - [ ] Test CSRF protection on all forms
   - [ ] Test SQL injection attempts
   - [ ] Test XSS payload injection
   - [ ] Test file upload restrictions
   - [ ] Test session timeout
   - [ ] Test failed login lockout

6. **Monitoring**
   - [ ] Set up log monitoring
   - [ ] Configure error alerting
   - [ ] Monitor failed login attempts
   - [ ] Regular security audits

## Security Best Practices

### For Developers:

1. **Always validate and sanitize user input**
   - Use provided validation functions
   - Never trust user input
   - Validate on server-side (client-side is optional)

2. **Always escape output**
   - Use `escapeOutput()` for all user-generated content
   - Use `sanitizeHTML()` for rich text
   - Never echo raw user input

3. **Always use prepared statements**
   - Never concatenate SQL queries
   - Use parameter binding for all queries
   - Use the Database class methods

4. **Always protect forms with CSRF tokens**
   - Use `outputCSRFField()` in forms
   - Use `verifyCSRFToken()` in processing
   - Regenerate tokens after use

5. **Always check authentication and authorization**
   - Use `requireLogin()` for protected pages
   - Use `requireRole()` for role-specific pages
   - Check permissions before sensitive operations

6. **Always log security events**
   - Log failed logins
   - Log permission denials
   - Log suspicious activity
   - Include context and user information

### For System Administrators:

1. **Keep software updated**
   - Update PHP regularly
   - Update Apache/Nginx
   - Update MySQL
   - Apply security patches promptly

2. **Monitor logs regularly**
   - Check application logs daily
   - Monitor failed login attempts
   - Watch for suspicious patterns
   - Set up automated alerts

3. **Backup regularly**
   - Daily database backups
   - Weekly full system backups
   - Test restore procedures
   - Store backups securely off-site

4. **Use strong passwords**
   - Enforce password policy
   - Use different passwords for different services
   - Consider password manager
   - Rotate passwords regularly

5. **Limit access**
   - Use principle of least privilege
   - Disable unused accounts
   - Review user permissions regularly
   - Use SSH keys instead of passwords

## Security Incident Response

If a security incident is detected:

1. **Immediate Actions:**
   - Isolate affected systems
   - Preserve logs and evidence
   - Change all passwords
   - Notify affected users

2. **Investigation:**
   - Review logs for attack vector
   - Identify compromised data
   - Assess damage scope
   - Document findings

3. **Remediation:**
   - Patch vulnerabilities
   - Restore from clean backups
   - Update security measures
   - Monitor for recurrence

4. **Post-Incident:**
   - Conduct security audit
   - Update procedures
   - Train staff
   - Improve monitoring

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Apache Security Tips](https://httpd.apache.org/docs/2.4/misc/security_tips.html)
- [MySQL Security Best Practices](https://dev.mysql.com/doc/refman/8.0/en/security-guidelines.html)

## Contact

For security concerns or to report vulnerabilities, contact:
- Email: security@kruit-en-kramer.nl
- Phone: [Security Team Phone Number]

**Note:** Please report security issues privately before public disclosure.

