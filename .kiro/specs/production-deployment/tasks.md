# Production Deployment Implementation Plan

## Phase 1: Production-Ready Code Preparation

- [ ] 1. Create production configuration files and scripts
  - [ ] 1.1 Create web.config for IIS
    - Add URL rewrite rules for HTTPS redirect
    - Configure security headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
    - Set up request filtering to hide sensitive directories (/config, /classes, /database, /logs, /.git)
    - Configure default document (index.php)
    - Set max content length for file uploads (50MB)
    - _Requirements: 1.7, 2.1, 2.2_

  - [ ] 1.2 Create production php.ini template
    - Configure error handling (display_errors=Off, log_errors=On)
    - Set performance parameters (memory_limit=256M, max_execution_time=60)
    - Configure file upload limits (upload_max_filesize=50M, post_max_size=64M)
    - Enable security settings (expose_php=Off, allow_url_fopen=Off)
    - Configure session security (cookie_httponly, cookie_secure, use_strict_mode)
    - Set timezone to Europe/Amsterdam
    - _Requirements: 1.3, 2.5, 8.3, 8.4, 8.5, 8.6_

  - [ ] 1.3 Create health check API endpoint
    - Create api/health_check.php file
    - Implement database connectivity check
    - Check writable directories (/uploads, /logs)
    - Return JSON response with status and checks
    - Set appropriate HTTP status codes (200 for OK, 500 for error)
    - _Requirements: 7.1, 7.2_

  - [ ] 1.4 Create PowerShell backup script
    - Create backup_ticketportaal.ps1 in scripts directory
    - Implement database backup using mysqldump
    - Implement file backup (compress /uploads directory)
    - Add timestamp to backup filenames (YYYYMMDD_HHMMSS)
    - Implement 30-day retention (delete old backups)
    - Add logging to backup_log.txt
    - Add error handling and email alerts on failure
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.7_

  - [ ] 1.5 Create PowerShell health check script
    - Create health_check.ps1 in scripts directory
    - Implement HTTP request to health check endpoint
    - Check IIS and MySQL service status
    - Check disk space (alert if < 20GB)
    - Add logging to health_check.log
    - Send email alerts on failures
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.6_

  - [ ] 1.6 Create PowerShell disk space monitoring script
    - Create disk_space_check.ps1 in scripts directory
    - Check C: and D: drive free space
    - Send email alert if space < 20GB
    - Log results to disk_space.log
    - _Requirements: 7.4_

- [ ] 2. Update application code for production readiness
  - [ ] 2.1 Review and update config files
    - Ensure config/database.example.php has correct structure
    - Ensure config/email.example.php has correct structure
    - Update config/config.php to support DEBUG_MODE flag
    - Add production-specific settings (error logging paths, etc.)
    - _Requirements: 4.6, 8.1_

  - [ ] 2.2 Add environment detection to config
    - Implement environment detection (development vs production)
    - Set DEBUG_MODE based on environment
    - Configure error display based on environment
    - Set appropriate logging levels
    - _Requirements: 8.1, 8.2_

  - [ ] 2.3 Review and optimize database queries
    - Review all SQL queries for proper indexing
    - Ensure all queries use prepared statements
    - Add missing indexes if needed
    - Optimize slow queries identified during testing
    - _Requirements: 9.2_

  - [ ] 2.4 Implement proper error logging
    - Ensure all try-catch blocks log errors properly
    - Use consistent error logging format
    - Log to application-specific log files
    - Avoid exposing sensitive information in logs
    - _Requirements: 7.7, 8.2_

  - [ ] 2.5 Add security headers in PHP code
    - Add Content-Security-Policy header
    - Implement HSTS header (Strict-Transport-Security)
    - Add additional security headers as fallback to web.config
    - _Requirements: 2.1, 3.3_

- [ ] 3. Create deployment documentation
  - [ ] 3.1 Create Windows Server deployment guide
    - Document IIS installation steps
    - Document PHP installation and configuration
    - Document MySQL installation and configuration
    - Document FastCGI configuration
    - Include PowerShell commands for automation
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 11.1_

  - [ ] 3.2 Create database setup guide
    - Document database creation steps
    - Document user creation with minimal privileges
    - Document schema import procedure
    - Document seed data import procedure
    - Include CI/Change Management migration steps
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 11.1_

  - [ ] 3.3 Create SSL certificate installation guide
    - Document Let's Encrypt setup with Certify The Web
    - Document commercial certificate installation
    - Document IIS HTTPS binding configuration
    - Document certificate renewal procedures
    - _Requirements: 3.1, 3.2, 3.4, 11.1_

  - [ ] 3.4 Create Task Scheduler configuration guide
    - Document email processing task setup
    - Document backup task setup
    - Document health check task setup
    - Include PowerShell commands for task creation
    - _Requirements: 5.6, 6.1, 6.6, 7.5, 11.1_

  - [ ] 3.5 Create security hardening checklist
    - Document Windows Firewall configuration
    - Document IIS security settings
    - Document file permission configuration
    - Document PHP security settings
    - Include verification steps for each item
    - _Requirements: 2.4, 2.5, 2.6, 2.7, 11.1_

  - [ ] 3.6 Create troubleshooting guide
    - Document common issues and solutions
    - Include diagnostic commands
    - Add log file locations
    - Include rollback procedures
    - _Requirements: 11.1, 11.3_

  - [ ] 3.7 Create user documentation
    - Write user guide for login and registration
    - Document ticket creation process
    - Document email-to-ticket functionality
    - Create FAQ section
    - _Requirements: 11.2_

  - [ ] 3.8 Create administrator runbook
    - Document daily maintenance tasks
    - Document weekly maintenance tasks
    - Document monthly maintenance tasks
    - Include emergency procedures
    - Document backup restoration procedure
    - _Requirements: 11.1, 11.3_

## Phase 2: Pre-Deployment Testing

- [ ] 4. Perform comprehensive testing on XAMPP/local environment
  - [ ] 4.1 Functional testing
    - Test user registration and login
    - Test ticket creation (web and email)
    - Test file uploads and downloads
    - Test email notifications
    - Test knowledge base functionality
    - Test admin, agent, and user dashboards
    - Test CI Management module (if implemented)
    - Test Change Management module (if implemented)
    - _Requirements: 10.1_

  - [ ] 4.2 Security testing
    - Test SQL injection protection
    - Test XSS protection
    - Test CSRF token validation
    - Test file upload validation
    - Test directory access restrictions
    - Test session security
    - Verify sensitive data is not exposed in errors
    - _Requirements: 10.2_

  - [ ] 4.3 Performance testing
    - Measure page load times
    - Test with 20+ concurrent users
    - Identify and optimize slow queries
    - Test file upload performance
    - Test email processing performance
    - _Requirements: 10.3, 9.5, 9.6_

  - [ ] 4.4 Browser compatibility testing
    - Test on Chrome, Firefox, Edge
    - Test on mobile browsers (iOS Safari, Chrome Mobile)
    - Verify responsive design works correctly
    - Test all major workflows on each browser
    - _Requirements: 10.1_

  - [ ] 4.5 Email functionality testing
    - Test SMTP email sending
    - Test IMAP email receiving
    - Test email-to-ticket creation
    - Test all notification types
    - Verify email templates render correctly
    - _Requirements: 10.1_

- [ ] 5. Fix identified bugs and issues
  - [ ] 5.1 Fix critical bugs
    - Address any security vulnerabilities found
    - Fix any data loss or corruption issues
    - Fix any authentication/authorization issues
    - _Requirements: 10.1, 10.2_

  - [ ] 5.2 Fix high-priority bugs
    - Fix any functional issues that block major workflows
    - Fix any performance issues causing slow page loads
    - Fix any email delivery issues
    - _Requirements: 10.1, 10.3_

  - [ ] 5.3 Optimize performance issues
    - Optimize slow database queries
    - Add missing indexes
    - Optimize file upload handling
    - Implement caching where appropriate
    - _Requirements: 9.2, 9.3, 9.5_

## Phase 3: Production Server Setup (To be executed on Windows Server)

- [ ] 6. Install and configure server software
  - [ ] 6.1 Install IIS with required features
    - Install Web-Server feature
    - Install Web-CGI feature
    - Install Web-Asp-Net45 feature
    - Verify installation with Get-WindowsFeature
    - _Requirements: 1.1_

  - [ ] 6.2 Install and configure PHP
    - Download PHP 8.2+ Non-Thread Safe
    - Extract to C:\PHP
    - Copy php.ini-production to php.ini
    - Enable required extensions in php.ini
    - Apply production php.ini settings
    - Add C:\PHP to System PATH
    - Verify with php -v and php -m
    - _Requirements: 1.2, 1.3_

  - [ ] 6.3 Configure FastCGI in IIS
    - Open IIS Manager
    - Add FastCGI Handler Mapping for *.php
    - Point to C:\PHP\php-cgi.exe
    - Verify configuration
    - _Requirements: 1.4_

  - [ ] 6.4 Install and configure MySQL
    - Download MySQL Community Server 8.0+
    - Install as Windows Service
    - Set root password
    - Configure for utf8mb4 character set
    - Enable network access (localhost only)
    - Verify with mysql -u root -p
    - _Requirements: 1.5_

  - [ ] 6.5 Install Composer (if needed)
    - Download Composer-Setup.exe
    - Run installer as Administrator
    - Select C:\PHP\php.exe
    - Verify with composer --version
    - _Requirements: 9.1_

- [ ] 7. Deploy application files
  - [ ] 7.1 Create application directory
    - Create C:\inetpub\wwwroot\ticketportaal
    - Copy all application files to this directory
    - Verify all files copied correctly
    - _Requirements: 1.6_

  - [ ] 7.2 Create IIS website
    - Open IIS Manager
    - Create new website "ICT Ticketportaal"
    - Set physical path to C:\inetpub\wwwroot\ticketportaal
    - Configure binding (http, port 80, host: tickets.kruit-en-kramer.nl)
    - Create Application Pool "TicketportaalAppPool"
    - Set .NET CLR version to "No Managed Code"
    - Set Managed pipeline mode to "Integrated"
    - Set Identity to "ApplicationPoolIdentity"
    - _Requirements: 1.6, 1.7_

  - [ ] 7.3 Set file permissions
    - Grant IIS AppPool\TicketportaalAppPool Read access to application root
    - Grant IIS AppPool\TicketportaalAppPool Modify access to /uploads
    - Grant IIS AppPool\TicketportaalAppPool Modify access to /logs
    - Protect /config directory (Read only)
    - Protect /classes directory (Read only)
    - Protect /database directory (Read only)
    - Disable inheritance on /config
    - _Requirements: 2.4_

  - [ ] 7.4 Deploy web.config
    - Copy web.config to application root
    - Verify security headers are configured
    - Verify request filtering is configured
    - Verify HTTPS redirect is configured
    - Test configuration with IIS Manager
    - _Requirements: 2.1, 2.2, 2.3_

  - [ ] 7.5 Install Composer dependencies (if applicable)
    - Run composer install --no-dev --optimize-autoloader
    - Verify all dependencies installed correctly
    - _Requirements: 9.1_

- [ ] 8. Configure database
  - [ ] 8.1 Create production database
    - Connect to MySQL as root
    - Create database with utf8mb4 character set
    - Verify database created correctly
    - _Requirements: 4.1_

  - [ ] 8.2 Create database user
    - Create dedicated application user (not root)
    - Set strong password
    - Grant minimal required privileges (SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER)
    - Flush privileges
    - Test connection with new user
    - _Requirements: 4.2, 2.6_

  - [ ] 8.3 Import database schema
    - Import database/schema.sql
    - Verify all tables created
    - Verify all indexes created
    - Verify all foreign keys created
    - _Requirements: 4.3_

  - [ ] 8.4 Import seed data
    - Import database/seed.sql
    - Verify default categories created
    - Verify admin user created
    - Verify sample knowledge base articles created
    - _Requirements: 4.4_

  - [ ] 8.5 Import CI/Change Management schema (if applicable)
    - Import database/ci_change_migration.sql
    - Verify CI tables created
    - Verify Change Management tables created
    - Verify all indexes and foreign keys created
    - _Requirements: 4.5_

  - [ ] 8.6 Create production configuration files
    - Copy config/database.example.php to config/database.php
    - Update with production database credentials
    - Copy config/email.example.php to config/email.php
    - Update with production email settings
    - Update config/config.php with production settings
    - Set DEBUG_MODE to false
    - Verify all configuration files have correct permissions
    - _Requirements: 4.6, 8.1_

- [ ] 9. Configure SSL certificate
  - [ ] 9.1 Obtain SSL certificate
    - Install Certify The Web (for Let's Encrypt) OR
    - Generate CSR and obtain commercial certificate
    - Verify certificate files received
    - _Requirements: 3.1_

  - [ ] 9.2 Install SSL certificate in IIS
    - Import certificate into IIS
    - Verify certificate installed correctly
    - _Requirements: 3.1_

  - [ ] 9.3 Configure HTTPS binding
    - Add HTTPS binding to IIS site
    - Set port to 443
    - Select SSL certificate
    - Enable SNI if needed
    - Test HTTPS access
    - _Requirements: 3.2_

  - [ ] 9.4 Configure SSL/TLS protocols
    - Disable SSL 2.0, SSL 3.0, TLS 1.0, TLS 1.1
    - Enable TLS 1.2 and TLS 1.3
    - Configure strong cipher suites
    - _Requirements: 3.2_

  - [ ] 9.5 Test HTTPS redirect
    - Access site via HTTP
    - Verify automatic redirect to HTTPS
    - Test with different browsers
    - Verify no browser warnings
    - _Requirements: 3.3, 3.5_

  - [ ] 9.6 Configure certificate auto-renewal
    - Set up auto-renewal in Certify The Web OR
    - Configure renewal reminder for commercial certificate
    - Test renewal process
    - _Requirements: 3.4_

- [ ] 10. Configure Windows Firewall
  - [ ] 10.1 Create firewall rules
    - Allow inbound HTTP (port 80)
    - Allow inbound HTTPS (port 443)
    - Block external MySQL access (port 3306)
    - Verify rules with Get-NetFirewallRule
    - _Requirements: 2.7_

  - [ ] 10.2 Test firewall configuration
    - Test HTTP access from external network
    - Test HTTPS access from external network
    - Verify MySQL not accessible from external network
    - _Requirements: 2.7_

- [ ] 11. Set up automated tasks
  - [ ] 11.1 Deploy PowerShell scripts
    - Create C:\Scripts directory
    - Copy backup_ticketportaal.ps1 to C:\Scripts
    - Copy health_check.ps1 to C:\Scripts
    - Copy disk_space_check.ps1 to C:\Scripts
    - Update script paths to match production environment
    - Test each script manually
    - _Requirements: 5.1, 7.1_

  - [ ] 11.2 Create backup Task Scheduler task
    - Open Task Scheduler
    - Create task "Ticketportaal Daily Backup"
    - Set trigger to daily at 02:00
    - Set action to run backup_ticketportaal.ps1
    - Configure to run as SYSTEM
    - Configure to run whether user is logged on or not
    - Test task execution
    - _Requirements: 5.6_

  - [ ] 11.3 Create email processing Task Scheduler task
    - Create task "Ticketportaal Email Processing"
    - Set trigger to repeat every 5 minutes
    - Set action to run php.exe with email_to_ticket.php
    - Configure to run with highest privileges
    - Configure to run whether user is logged on or not
    - Set timeout to 10 minutes
    - Test task execution
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

  - [ ] 11.4 Create health check Task Scheduler task
    - Create task "Ticketportaal Health Check"
    - Set trigger to repeat every 30 minutes
    - Set action to run health_check.ps1
    - Configure to run as SYSTEM
    - Test task execution
    - Verify email alerts work
    - _Requirements: 7.5_

  - [ ] 11.5 Create disk space monitoring Task Scheduler task
    - Create task "Ticketportaal Disk Space Check"
    - Set trigger to run daily
    - Set action to run disk_space_check.ps1
    - Configure to run as SYSTEM
    - Test task execution
    - _Requirements: 7.4_

- [ ] 12. Configure monitoring and logging
  - [ ] 12.1 Enable IIS logging
    - Configure IIS to log all requests
    - Set log file location
    - Configure log rotation
    - Verify logs are being written
    - _Requirements: 7.6_

  - [ ] 12.2 Configure PHP error logging
    - Verify php.ini error_log setting
    - Create C:\PHP\logs directory
    - Test error logging
    - Verify errors are logged correctly
    - _Requirements: 7.7, 8.2_

  - [ ] 12.3 Configure application logging
    - Verify logs directory is writable
    - Test application error logging
    - Test email processing logging
    - Verify log rotation works
    - _Requirements: 7.7_

  - [ ] 12.4 Set up log monitoring
    - Configure Windows Event Viewer filters
    - Set up alerts for critical errors
    - Document log file locations
    - _Requirements: 7.6_

## Phase 4: Production Testing and Validation

- [ ] 13. Perform production environment testing
  - [ ] 13.1 Functional testing on production
    - Test user registration and login
    - Test ticket creation via web
    - Test ticket creation via email
    - Test file uploads
    - Test email notifications
    - Test all user roles (user, agent, admin)
    - Test knowledge base
    - Test CI Management (if applicable)
    - Test Change Management (if applicable)
    - _Requirements: 10.1_

  - [ ] 13.2 Security testing on production
    - Verify HTTPS works without warnings
    - Test directory access restrictions
    - Verify sensitive files not accessible
    - Test SQL injection protection
    - Test XSS protection
    - Verify error messages don't expose sensitive info
    - Run security scan (e.g., OWASP ZAP)
    - _Requirements: 10.2_

  - [ ] 13.3 Performance testing on production
    - Measure page load times
    - Test with multiple concurrent users
    - Monitor server resource usage
    - Verify performance meets requirements (< 2s)
    - _Requirements: 10.3, 9.5_

  - [ ] 13.4 Backup and restore testing
    - Manually trigger backup script
    - Verify backup files created
    - Test database restore from backup
    - Test file restore from backup
    - Verify restored data is correct
    - _Requirements: 10.4_

  - [ ] 13.5 Monitoring and alerting testing
    - Manually trigger health check
    - Verify health check logs
    - Test email alerts by simulating failures
    - Verify disk space monitoring works
    - _Requirements: 10.5_

  - [ ] 13.6 SSL certificate testing
    - Verify HTTPS works on all browsers
    - Check certificate validity
    - Test HTTP to HTTPS redirect
    - Verify no mixed content warnings
    - Run SSL Labs test (ssllabs.com/ssltest)
    - _Requirements: 10.6_

- [ ] 14. User acceptance testing
  - [ ] 14.1 Conduct UAT with admin user
    - Test all admin functions
    - Test user management
    - Test category management
    - Test knowledge base management
    - Test reports
    - Test CI Management (if applicable)
    - Test Change Management (if applicable)
    - _Requirements: 10.7_

  - [ ] 14.2 Conduct UAT with agent user
    - Test ticket viewing and filtering
    - Test ticket assignment
    - Test status updates
    - Test internal comments
    - Test knowledge base access
    - _Requirements: 10.7_

  - [ ] 14.3 Conduct UAT with regular user
    - Test ticket creation
    - Test ticket viewing
    - Test comments
    - Test file uploads
    - Test knowledge base search
    - _Requirements: 10.7_

  - [ ] 14.4 Collect and address UAT feedback
    - Document all feedback
    - Prioritize issues
    - Fix critical issues before go-live
    - Plan fixes for non-critical issues
    - _Requirements: 10.7, 12.5_

## Phase 5: Go-Live Preparation

- [ ] 15. Prepare for go-live
  - [ ] 15.1 Create go-live communication
    - Draft email to all users
    - Include go-live date and time
    - Include new URL (tickets.kruit-en-kramer.nl)
    - Include login instructions
    - Include support contact information
    - _Requirements: 12.1_

  - [ ] 15.2 Prepare DNS update
    - Verify current DNS settings
    - Prepare DNS change (A record for tickets.kruit-en-kramer.nl)
    - Lower TTL before change (for faster propagation)
    - Document rollback procedure
    - _Requirements: 12.2_

  - [ ] 15.3 Create final pre-go-live backup
    - Run backup script manually
    - Verify backup files created
    - Store backup in safe location
    - Document backup location
    - _Requirements: 12.3_

  - [ ] 15.4 Perform final checks
    - Verify all services running
    - Verify all scheduled tasks enabled
    - Verify health check returns OK
    - Verify SSL certificate valid
    - Verify firewall rules active
    - Review all configuration files
    - _Requirements: 12.3_

  - [ ] 15.5 Prepare rollback plan
    - Document rollback steps
    - Test rollback procedure
    - Prepare rollback scripts
    - Assign rollback responsibilities
    - _Requirements: 11.3_

- [ ] 16. Execute go-live
  - [ ] 16.1 Send go-live communication
    - Send email to all users
    - Post announcement on company intranet (if applicable)
    - Notify IT team
    - _Requirements: 12.1_

  - [ ] 16.2 Update DNS
    - Update A record for tickets.kruit-en-kramer.nl
    - Verify DNS propagation with nslookup
    - Test access from different networks
    - _Requirements: 12.2_

  - [ ] 16.3 Monitor go-live
    - Monitor health check logs
    - Monitor IIS logs
    - Monitor PHP error logs
    - Monitor application logs
    - Monitor server resource usage
    - Be ready to rollback if critical issues occur
    - _Requirements: 12.4_

  - [ ] 16.4 Verify functionality post go-live
    - Test login from external network
    - Test ticket creation
    - Test email notifications
    - Verify all services running
    - _Requirements: 12.4_

## Phase 6: Post-Deployment

- [ ] 17. Post-deployment monitoring and support
  - [ ] 17.1 Daily monitoring (Week 1)
    - Review error logs daily
    - Review health check logs daily
    - Monitor user feedback
    - Address critical bugs immediately
    - _Requirements: 12.4, 12.5_

  - [ ] 17.2 Collect user feedback
    - Set up feedback mechanism
    - Monitor support requests
    - Document common issues
    - Prioritize improvements
    - _Requirements: 12.5_

  - [ ] 17.3 Address post-deployment issues
    - Fix critical bugs within 24 hours
    - Fix high-priority bugs within 1 week
    - Plan fixes for low-priority issues
    - _Requirements: 12.5_

  - [ ] 17.4 Conduct post-implementation review
    - Schedule review meeting after 1 week
    - Document lessons learned
    - Document what went well
    - Document what could be improved
    - Update deployment procedures based on learnings
    - _Requirements: 12.6_

  - [ ] 17.5 Create change management record
    - Document deployment as Change in the system
    - Include implementation details
    - Include rollback plan
    - Include post-implementation review results
    - _Requirements: 11.4_

- [ ] 18. Knowledge transfer and documentation
  - [ ] 18.1 Conduct administrator training
    - Train on daily maintenance tasks
    - Train on backup and restore procedures
    - Train on troubleshooting common issues
    - Train on monitoring and alerting
    - _Requirements: 11.5_

  - [ ] 18.2 Finalize documentation
    - Review all documentation for accuracy
    - Update documentation based on actual deployment
    - Add any missing information
    - Organize documentation for easy access
    - _Requirements: 11.1, 11.2_

  - [ ] 18.3 Create maintenance schedule
    - Document daily maintenance tasks
    - Document weekly maintenance tasks
    - Document monthly maintenance tasks
    - Document quarterly maintenance tasks
    - Assign responsibilities
    - _Requirements: 11.1_

  - [ ] 18.4 Set up ongoing support
    - Define support escalation path
    - Document support contact information
    - Set up support ticketing (use the system itself)
    - Define SLAs for support requests
    - _Requirements: 11.1_

## Success Criteria

The production deployment is successful when:
- ✅ Application is accessible 24/7 via HTTPS
- ✅ All functionality works as tested
- ✅ Automated backups run successfully
- ✅ Monitoring and alerts are operational
- ✅ Security is hardened per best practices
- ✅ Performance meets requirements (< 2s page load)
- ✅ Users can work without issues
- ✅ No critical bugs in first week
- ✅ Documentation is complete and accurate
- ✅ Knowledge transfer is completed
