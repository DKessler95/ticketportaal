# Production Deployment Requirements - ICT Ticketportaal

## Introduction

This specification covers the production deployment of the ICT Ticketportaal on a Windows Server environment using IIS, PHP, and MySQL. The goal is to create a stable, secure, and maintainable production environment that can handle 24/7 operations with proper monitoring, backups, and security hardening.

## Glossary

- **IIS**: Internet Information Services - Microsoft's web server for Windows
- **FastCGI**: Protocol for interfacing external applications with web servers
- **SSL/TLS**: Secure Sockets Layer/Transport Layer Security for HTTPS
- **Task Scheduler**: Windows service for scheduling automated tasks
- **Application Pool**: IIS isolation boundary for web applications
- **web.config**: IIS configuration file (equivalent to Apache's .htaccess)

## Requirements

### Requirement 1: Production Environment Setup

**User Story:** As a system administrator, I want a properly configured Windows Server environment with IIS and PHP, so that the application runs reliably in production.

#### Acceptance Criteria

1. WHEN IIS is installed THEN the system SHALL include Web-Server, Web-CGI, and Web-Asp-Net45 features
2. WHEN PHP is installed THEN the system SHALL use PHP 8.2+ Non-Thread Safe version in C:\PHP
3. WHEN PHP extensions are configured THEN the system SHALL enable pdo_mysql, mbstring, openssl, fileinfo, curl, imap, gd, and intl
4. WHEN FastCGI is configured THEN the system SHALL map *.php requests to php-cgi.exe
5. WHEN MySQL is installed THEN the system SHALL run as a Windows Service with utf8mb4 character set
6. WHEN the application is deployed THEN the system SHALL place files in C:\inetpub\wwwroot\ticketportaal
7. WHEN IIS site is created THEN the system SHALL configure Application Pool with No Managed Code and Integrated pipeline

### Requirement 2: Security Hardening

**User Story:** As a security administrator, I want the production environment to be hardened against common attacks, so that user data and system integrity are protected.

#### Acceptance Criteria

1. WHEN web.config is created THEN the system SHALL include security headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection)
2. WHEN sensitive directories exist THEN the system SHALL block access to /config, /classes, /database, /logs, /.git
3. WHEN HTTPS is configured THEN the system SHALL redirect all HTTP traffic to HTTPS
4. WHEN file permissions are set THEN the system SHALL grant IIS AppPool read access to application and write access only to /uploads and /logs
5. WHEN PHP is configured THEN the system SHALL disable display_errors, expose_php, allow_url_fopen, and allow_url_include
6. WHEN database user is created THEN the system SHALL use a dedicated non-root user with minimal required privileges
7. WHEN Windows Firewall is configured THEN the system SHALL allow ports 80/443 and block external MySQL access (port 3306)

### Requirement 3: SSL Certificate Configuration

**User Story:** As a system administrator, I want HTTPS enabled with a valid SSL certificate, so that all communications are encrypted and trusted by browsers.

#### Acceptance Criteria

1. WHEN SSL certificate is obtained THEN the system SHALL use either Let's Encrypt or a commercial certificate
2. WHEN SSL binding is configured THEN the system SHALL bind HTTPS on port 443 with the certificate
3. WHEN HTTP requests are made THEN the system SHALL automatically redirect to HTTPS
4. WHEN certificate expires THEN the system SHALL send alerts 30 days before expiration
5. WHEN browsers access the site THEN the system SHALL show a valid certificate without warnings

### Requirement 4: Database Configuration and Migration

**User Story:** As a database administrator, I want the production database properly configured with all schema and seed data, so that the application has a clean starting state.

#### Acceptance Criteria

1. WHEN database is created THEN the system SHALL use utf8mb4 character set and utf8mb4_unicode_ci collation
2. WHEN database user is created THEN the system SHALL grant only SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER privileges
3. WHEN schema is imported THEN the system SHALL create all tables with proper indexes and foreign keys
4. WHEN seed data is imported THEN the system SHALL include default categories, admin user, and sample knowledge base articles
5. WHEN CI/Change Management is enabled THEN the system SHALL run ci_change_migration.sql to add required tables
6. WHEN configuration files are created THEN the system SHALL use strong passwords and correct connection parameters

### Requirement 5: Automated Backup System

**User Story:** As a system administrator, I want automated daily backups of database and files, so that data can be recovered in case of failure.

#### Acceptance Criteria

1. WHEN backup script runs THEN the system SHALL export the database to a timestamped SQL file
2. WHEN backup script runs THEN the system SHALL compress the /uploads directory to a timestamped ZIP file
3. WHEN backups are created THEN the system SHALL store them in D:\Backups\Ticketportaal
4. WHEN backup retention is applied THEN the system SHALL delete backups older than 30 days
5. WHEN backup fails THEN the system SHALL log the error and send an email alert
6. WHEN Task Scheduler is configured THEN the system SHALL run backups daily at 02:00
7. WHEN backup completes THEN the system SHALL log success with timestamp

### Requirement 6: Email Processing Automation

**User Story:** As a system administrator, I want email-to-ticket processing to run automatically, so that tickets are created from incoming emails without manual intervention.

#### Acceptance Criteria

1. WHEN Task Scheduler is configured THEN the system SHALL run email_to_ticket.php every 5 minutes
2. WHEN email processing runs THEN the system SHALL connect to the IMAP mailbox and process unread emails
3. WHEN emails are processed THEN the system SHALL create tickets and mark emails as read
4. WHEN email processing fails THEN the system SHALL log errors to logs/email_processing.log
5. WHEN email processing completes THEN the system SHALL log the number of emails processed
6. WHEN Task Scheduler task is created THEN the system SHALL run with highest privileges and whether user is logged on or not

### Requirement 7: Monitoring and Health Checks

**User Story:** As a system administrator, I want automated monitoring and health checks, so that I am alerted when the application has issues.

#### Acceptance Criteria

1. WHEN health check script runs THEN the system SHALL test application availability via HTTP request
2. WHEN application is healthy THEN the system SHALL log success with timestamp
3. WHEN application is unhealthy THEN the system SHALL send email alert to ICT team
4. WHEN disk space is low (< 20GB) THEN the system SHALL send email alert
5. WHEN Task Scheduler is configured THEN the system SHALL run health checks every 30 minutes
6. WHEN IIS logging is enabled THEN the system SHALL log all requests to IIS log files
7. WHEN errors occur THEN the system SHALL log to PHP error log at C:\PHP\logs\php_errors.log

### Requirement 8: Production Configuration

**User Story:** As a developer, I want production-specific configuration settings, so that the application runs optimally and securely in production.

#### Acceptance Criteria

1. WHEN config.php is configured THEN the system SHALL set DEBUG_MODE to false
2. WHEN php.ini is configured THEN the system SHALL set display_errors to Off and log_errors to On
3. WHEN php.ini is configured THEN the system SHALL set memory_limit to 256M and max_execution_time to 60
4. WHEN php.ini is configured THEN the system SHALL set upload_max_filesize to 50M and post_max_size to 64M
5. WHEN session settings are configured THEN the system SHALL enable session.cookie_httponly, session.cookie_secure, and session.use_strict_mode
6. WHEN timezone is configured THEN the system SHALL set date.timezone to Europe/Amsterdam
7. WHEN error reporting is configured THEN the system SHALL log all errors but not display them to users

### Requirement 9: Performance Optimization

**User Story:** As a system administrator, I want the application optimized for production performance, so that pages load quickly and the system handles concurrent users efficiently.

#### Acceptance Criteria

1. WHEN Composer is used THEN the system SHALL run with --no-dev and --optimize-autoloader flags
2. WHEN database queries are executed THEN the system SHALL use proper indexes on frequently queried columns
3. WHEN static assets are served THEN the system SHALL enable browser caching with far-future expires headers
4. WHEN Application Pool is configured THEN the system SHALL set appropriate recycling settings
5. WHEN page load time is measured THEN the system SHALL render pages in less than 2 seconds under normal load
6. WHEN concurrent users test is performed THEN the system SHALL handle 20+ concurrent users without degradation

### Requirement 10: Deployment Testing and Validation

**User Story:** As a QA tester, I want comprehensive testing procedures before go-live, so that all functionality works correctly in production.

#### Acceptance Criteria

1. WHEN functional testing is performed THEN the system SHALL verify login, ticket creation, file uploads, and email notifications work
2. WHEN security testing is performed THEN the system SHALL verify SQL injection, XSS, and file upload validation protections work
3. WHEN performance testing is performed THEN the system SHALL verify page load times and concurrent user handling
4. WHEN backup testing is performed THEN the system SHALL verify database and file backups can be restored successfully
5. WHEN monitoring testing is performed THEN the system SHALL verify health checks and alerts work correctly
6. WHEN SSL testing is performed THEN the system SHALL verify HTTPS works without browser warnings
7. WHEN user acceptance testing is performed THEN the system SHALL have 3-5 users test all major workflows

### Requirement 11: Documentation and Knowledge Transfer

**User Story:** As a system administrator, I want comprehensive documentation, so that I can maintain and troubleshoot the system independently.

#### Acceptance Criteria

1. WHEN administrator documentation is created THEN the system SHALL include server configuration, backup procedures, and troubleshooting guides
2. WHEN user documentation is created THEN the system SHALL include login instructions, ticket creation, and FAQ
3. WHEN runbook is created THEN the system SHALL include emergency procedures and rollback plans
4. WHEN change management is documented THEN the system SHALL record the deployment as a Change in the system
5. WHEN knowledge transfer is completed THEN the system SHALL include training session for administrators

### Requirement 12: Go-Live and Post-Deployment

**User Story:** As a project manager, I want a structured go-live process, so that the transition to production is smooth and users are properly informed.

#### Acceptance Criteria

1. WHEN go-live communication is sent THEN the system SHALL email all users with URL, login instructions, and support contact
2. WHEN DNS is updated THEN the system SHALL point tickets.kruit-en-kramer.nl to the production server IP
3. WHEN go-live occurs THEN the system SHALL have a final backup taken immediately before
4. WHEN post-deployment monitoring begins THEN the system SHALL check logs daily for the first week
5. WHEN user feedback is collected THEN the system SHALL address critical bugs within 24 hours
6. WHEN post-implementation review is conducted THEN the system SHALL document lessons learned after 1 week
