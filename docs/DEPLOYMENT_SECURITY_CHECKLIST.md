# Deployment Security Checklist

Use this checklist when deploying the ICT Ticketportaal to production.

## Pre-Deployment

### Configuration Files

- [ ] **config/config.php**
  - [ ] Set `DEBUG_MODE = false`
  - [ ] Update `SITE_URL` to production domain (with https://)
  - [ ] Verify `SESSION_TIMEOUT` is appropriate (default: 1800 seconds)
  - [ ] Verify `MAX_FILE_SIZE` is set correctly (default: 10MB)
  - [ ] Verify `ALLOWED_EXTENSIONS` list is appropriate

- [ ] **config/database.php**
  - [ ] Update database credentials
  - [ ] Use strong database password
  - [ ] Verify database host is correct
  - [ ] Test database connection

- [ ] **config/email.php**
  - [ ] Configure SMTP settings for Collax
  - [ ] Test email sending
  - [ ] Verify FROM_EMAIL and FROM_NAME

- [ ] **config/session.php**
  - [ ] Verify `session.cookie_secure = 1` (requires HTTPS)
  - [ ] Verify `session.cookie_httponly = 1`
  - [ ] Verify `session.cookie_samesite = Strict`

### SSL/TLS Certificate

- [ ] Install valid SSL certificate
- [ ] Verify certificate is trusted (not self-signed)
- [ ] Test HTTPS access
- [ ] Verify certificate expiration date
- [ ] Set up auto-renewal if using Let's Encrypt
- [ ] Test HTTPS redirect from HTTP

### File Permissions

Run these commands in the project root:

```bash
# Set all files to 644
find . -type f -exec chmod 644 {} \;

# Set all directories to 755
find . -type d -exec chmod 755 {} \;

# Make uploads directory writable
chmod 755 uploads/
chmod 755 uploads/tickets/

# Make logs directory writable
chmod 755 logs/

# Protect sensitive files
chmod 600 config/database.php
chmod 600 config/email.php
```

- [ ] Verify file permissions are set correctly
- [ ] Test that uploads directory is writable
- [ ] Test that logs directory is writable
- [ ] Verify sensitive files are not world-readable

### Apache Configuration

- [ ] Verify `.htaccess` is in project root
- [ ] Verify `.htaccess` files are in sensitive directories:
  - [ ] `/config/.htaccess`
  - [ ] `/classes/.htaccess`
  - [ ] `/includes/.htaccess`
  - [ ] `/logs/.htaccess`
  - [ ] `/database/.htaccess`
  - [ ] `/uploads/.htaccess`
- [ ] Verify `AllowOverride All` is set in Apache config
- [ ] Test that sensitive directories return 403 Forbidden
- [ ] Test that uploads directory doesn't execute PHP files

### Database Setup

- [ ] Create database and user
- [ ] Run `database/schema.sql` to create tables
- [ ] Run `database/seed.sql` to insert default data
- [ ] Verify all tables are created
- [ ] Verify indexes are created
- [ ] Create initial admin user
- [ ] Test database connection from application

## Security Testing

### Authentication & Authorization

- [ ] Test user registration
- [ ] Test user login
- [ ] Test password reset flow
- [ ] Test session timeout (wait 30 minutes)
- [ ] Test failed login lockout (5 attempts)
- [ ] Test role-based access control:
  - [ ] User cannot access agent pages
  - [ ] User cannot access admin pages
  - [ ] Agent cannot access admin pages
  - [ ] Admin can access all pages

### CSRF Protection

- [ ] Test form submission without CSRF token (should fail)
- [ ] Test form submission with invalid CSRF token (should fail)
- [ ] Test form submission with valid CSRF token (should succeed)
- [ ] Verify all forms have CSRF protection:
  - [ ] Login form
  - [ ] Registration form
  - [ ] Password reset forms
  - [ ] Ticket creation form
  - [ ] Ticket update forms
  - [ ] Comment forms
  - [ ] Admin forms

### Input Validation

- [ ] Test SQL injection attempts (should be blocked)
- [ ] Test XSS payload injection (should be escaped)
- [ ] Test file upload with invalid type (should be rejected)
- [ ] Test file upload with oversized file (should be rejected)
- [ ] Test form submission with missing required fields (should show errors)
- [ ] Test form submission with invalid email (should show error)
- [ ] Test form submission with weak password (should show error)

### File Upload Security

- [ ] Test uploading allowed file types (should succeed)
- [ ] Test uploading disallowed file types (should fail)
- [ ] Test uploading file larger than 10MB (should fail)
- [ ] Test uploading PHP file to uploads directory (should not execute)
- [ ] Verify uploaded files have random filenames
- [ ] Verify uploaded files are stored in correct directory

### Session Security

- [ ] Verify session cookie has `HttpOnly` flag
- [ ] Verify session cookie has `Secure` flag (HTTPS only)
- [ ] Verify session cookie has `SameSite=Strict`
- [ ] Test session timeout after 30 minutes of inactivity
- [ ] Test session regeneration on login
- [ ] Test logout destroys session

### Security Headers

Use browser developer tools or online tools to verify headers:

- [ ] `X-Frame-Options: SAMEORIGIN`
- [ ] `X-XSS-Protection: 1; mode=block`
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] `Content-Security-Policy` is set
- [ ] `Permissions-Policy` is set
- [ ] Server header is removed or generic
- [ ] X-Powered-By header is removed

### Directory Protection

Test these URLs (should all return 403 Forbidden):

- [ ] `https://yourdomain.com/config/`
- [ ] `https://yourdomain.com/classes/`
- [ ] `https://yourdomain.com/includes/`
- [ ] `https://yourdomain.com/logs/`
- [ ] `https://yourdomain.com/database/`
- [ ] `https://yourdomain.com/config/database.php`
- [ ] `https://yourdomain.com/config/email.php`

### HTTPS Configuration

- [ ] Test HTTP to HTTPS redirect
- [ ] Verify all resources load over HTTPS
- [ ] Test mixed content warnings (should be none)
- [ ] Verify SSL Labs rating (aim for A or A+): https://www.ssllabs.com/ssltest/
- [ ] Enable HSTS header (uncomment in `.htaccess` after testing)

## Post-Deployment

### Monitoring Setup

- [ ] Set up log monitoring
- [ ] Configure error alerting
- [ ] Set up uptime monitoring
- [ ] Configure backup monitoring
- [ ] Set up security event alerts

### Backup Configuration

- [ ] Configure daily database backups
- [ ] Configure weekly full system backups
- [ ] Test backup restoration
- [ ] Verify backup storage location is secure
- [ ] Set up off-site backup storage

### Cron Jobs

- [ ] Set up email processing cron job:
  ```bash
  */5 * * * * /usr/bin/php /path/to/ticketportaal/email_to_ticket.php >> /path/to/logs/cron.log 2>&1
  ```
- [ ] Test cron job execution
- [ ] Verify cron job logs

### Documentation

- [ ] Document server configuration
- [ ] Document deployment process
- [ ] Document backup procedures
- [ ] Document incident response plan
- [ ] Document admin credentials (store securely)

### User Setup

- [ ] Create initial admin account
- [ ] Create test user accounts
- [ ] Create default categories
- [ ] Create initial knowledge base articles
- [ ] Test complete user workflow

## Security Maintenance

### Regular Tasks

**Daily:**
- [ ] Review application logs
- [ ] Check for failed login attempts
- [ ] Monitor error logs

**Weekly:**
- [ ] Review security logs
- [ ] Check backup status
- [ ] Review user accounts
- [ ] Check for suspicious activity

**Monthly:**
- [ ] Update PHP and dependencies
- [ ] Review and rotate logs
- [ ] Test backup restoration
- [ ] Review user permissions
- [ ] Security audit

**Quarterly:**
- [ ] Change admin passwords
- [ ] Review security policies
- [ ] Update documentation
- [ ] Conduct security training

**Annually:**
- [ ] Full security audit
- [ ] Penetration testing
- [ ] Review and update security procedures
- [ ] Review and update disaster recovery plan

## Security Incident Response

If a security incident is detected:

1. **Immediate Actions:**
   - [ ] Isolate affected systems
   - [ ] Preserve logs and evidence
   - [ ] Change all passwords
   - [ ] Notify affected users
   - [ ] Document incident timeline

2. **Investigation:**
   - [ ] Review logs for attack vector
   - [ ] Identify compromised data
   - [ ] Assess damage scope
   - [ ] Identify root cause
   - [ ] Document findings

3. **Remediation:**
   - [ ] Patch vulnerabilities
   - [ ] Restore from clean backups
   - [ ] Update security measures
   - [ ] Monitor for recurrence
   - [ ] Verify system integrity

4. **Post-Incident:**
   - [ ] Conduct post-mortem
   - [ ] Update procedures
   - [ ] Train staff
   - [ ] Improve monitoring
   - [ ] Document lessons learned

## Contact Information

**Security Team:**
- Email: security@kruit-en-kramer.nl
- Phone: [Security Team Phone]
- Emergency: [Emergency Contact]

**System Administrator:**
- Name: [Admin Name]
- Email: [Admin Email]
- Phone: [Admin Phone]

**Hosting Provider:**
- Company: [Hosting Company]
- Support: [Support Contact]
- Emergency: [Emergency Contact]

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Checklist](https://www.php.net/manual/en/security.php)
- [Apache Security Tips](https://httpd.apache.org/docs/2.4/misc/security_tips.html)
- [SSL Labs](https://www.ssllabs.com/ssltest/)
- [Security Headers](https://securityheaders.com/)

## Sign-Off

**Deployment Date:** _______________

**Deployed By:** _______________

**Verified By:** _______________

**Notes:**
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

