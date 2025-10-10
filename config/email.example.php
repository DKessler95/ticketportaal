<?php
/**
 * Email Configuration Template
 * 
 * Copy this file to email.php and update with your actual email server settings.
 * 
 * IMPORTANT: Never commit email.php to version control!
 * Add email.php to .gitignore to protect your credentials.
 */

// SMTP Configuration for sending emails
define('SMTP_HOST', 'mail.kruit-en-kramer.nl');     // SMTP server hostname
define('SMTP_PORT', 587);                            // SMTP port (587 for TLS, 465 for SSL, 25 for non-encrypted)
define('SMTP_SECURE', 'tls');                        // Encryption type: 'tls', 'ssl', or '' for none
define('SMTP_AUTH', true);                           // Enable SMTP authentication
define('SMTP_USER', 'ict@kruit-en-kramer.nl');      // SMTP username (usually your email address)
define('SMTP_PASS', 'your_smtp_password_here');     // SMTP password - CHANGE THIS!

// Email sender information
define('FROM_EMAIL', 'ict@kruit-en-kramer.nl');     // From email address
define('FROM_NAME', 'ICT Support - Kruit & Kramer'); // From name displayed in emails

// IMAP Configuration for receiving emails (email-to-ticket feature)
define('IMAP_HOST', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX'); // IMAP server with options
define('IMAP_USER', 'ict@kruit-en-kramer.nl');      // IMAP username (usually same as SMTP)
define('IMAP_PASS', 'your_imap_password_here');     // IMAP password - CHANGE THIS!
define('IMAP_PORT', 993);                            // IMAP port (993 for SSL, 143 for non-encrypted)
define('IMAP_SECURE', 'ssl');                        // Encryption: 'ssl', 'tls', or '' for none

// Email processing settings
define('EMAIL_PROCESS_LIMIT', 50);                   // Maximum emails to process per cron run
define('EMAIL_MARK_AS_READ', true);                  // Mark processed emails as read
define('EMAIL_DELETE_AFTER_PROCESS', false);         // Delete emails after processing (not recommended)

// Email notification settings
define('ENABLE_EMAIL_NOTIFICATIONS', true);          // Master switch for all email notifications
define('NOTIFY_ON_TICKET_CREATE', true);            // Send confirmation when ticket is created
define('NOTIFY_ON_TICKET_ASSIGN', true);            // Send notification when ticket is assigned
define('NOTIFY_ON_STATUS_CHANGE', true);            // Send notification when status changes
define('NOTIFY_ON_COMMENT_ADD', true);              // Send notification when comment is added
define('NOTIFY_ON_TICKET_RESOLVE', true);           // Send notification when ticket is resolved

// Email template settings
define('EMAIL_FOOTER', "\n\n---\nICT Support - Kruit & Kramer\nEmail: ict@kruit-en-kramer.nl\nPortal: https://tickets.kruit-en-kramer.nl");

/**
 * Configuration Instructions:
 * 
 * 1. Copy this file:
 *    cp config/email.example.php config/email.php
 * 
 * 2. Edit email.php with your actual email server settings
 * 
 * 3. For Collax Email Server:
 *    - SMTP_HOST: Your Collax server hostname
 *    - SMTP_PORT: Usually 587 for TLS or 465 for SSL
 *    - SMTP_USER: Your email address (ict@kruit-en-kramer.nl)
 *    - SMTP_PASS: Your email password
 *    - IMAP_HOST: Format is {hostname:port/protocol}INBOX
 *    - IMAP_USER: Same as SMTP_USER
 *    - IMAP_PASS: Same as SMTP_PASS
 * 
 * 4. Test email sending:
 *    - Create a test ticket and verify confirmation email is received
 *    - Check logs/app.log for any email errors
 * 
 * 5. Test email receiving:
 *    - Send an email to ict@kruit-en-kramer.nl
 *    - Run: php email_to_ticket.php
 *    - Verify ticket is created in the system
 * 
 * 6. Set up cron job for email processing:
 *    */5 * * * * /usr/bin/php /path/to/ticketportaal/email_to_ticket.php
 */

// Example configurations for different email providers:

/*
 * GMAIL CONFIGURATION
 * Note: You may need to enable "Less secure app access" or use App Passwords
 */
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_PORT', 587);
// define('SMTP_SECURE', 'tls');
// define('SMTP_USER', 'your-email@gmail.com');
// define('SMTP_PASS', 'your-app-password');
// define('IMAP_HOST', '{imap.gmail.com:993/imap/ssl}INBOX');
// define('IMAP_USER', 'your-email@gmail.com');
// define('IMAP_PASS', 'your-app-password');

/*
 * MICROSOFT 365 / OUTLOOK CONFIGURATION
 */
// define('SMTP_HOST', 'smtp.office365.com');
// define('SMTP_PORT', 587);
// define('SMTP_SECURE', 'tls');
// define('SMTP_USER', 'your-email@outlook.com');
// define('SMTP_PASS', 'your-password');
// define('IMAP_HOST', '{outlook.office365.com:993/imap/ssl}INBOX');
// define('IMAP_USER', 'your-email@outlook.com');
// define('IMAP_PASS', 'your-password');

/*
 * GENERIC IMAP/SMTP SERVER
 */
// define('SMTP_HOST', 'mail.example.com');
// define('SMTP_PORT', 587);
// define('SMTP_SECURE', 'tls');
// define('SMTP_USER', 'support@example.com');
// define('SMTP_PASS', 'password');
// define('IMAP_HOST', '{mail.example.com:993/imap/ssl}INBOX');
// define('IMAP_USER', 'support@example.com');
// define('IMAP_PASS', 'password');

/*
 * DEVELOPMENT ENVIRONMENT (using Mailtrap or similar)
 * For testing without sending real emails
 */
// define('SMTP_HOST', 'smtp.mailtrap.io');
// define('SMTP_PORT', 2525);
// define('SMTP_SECURE', 'tls');
// define('SMTP_USER', 'your-mailtrap-username');
// define('SMTP_PASS', 'your-mailtrap-password');
// define('ENABLE_EMAIL_NOTIFICATIONS', true);
// define('IMAP_HOST', ''); // Disable IMAP in development
// define('IMAP_USER', '');
// define('IMAP_PASS', '');

/**
 * Troubleshooting:
 * 
 * 1. SMTP Connection Issues:
 *    - Verify SMTP_HOST, SMTP_PORT, and SMTP_SECURE settings
 *    - Check firewall allows outbound connections on SMTP port
 *    - Test connection: telnet mail.kruit-en-kramer.nl 587
 *    - Check PHP has openssl extension enabled
 * 
 * 2. IMAP Connection Issues:
 *    - Verify IMAP_HOST format: {hostname:port/protocol}INBOX
 *    - Check PHP has imap extension enabled: php -m | grep imap
 *    - Verify credentials are correct
 *    - Check mailbox has emails to process
 * 
 * 3. Authentication Failures:
 *    - Verify username and password are correct
 *    - Check if email account requires app-specific passwords
 *    - Ensure account is not locked or suspended
 * 
 * 4. Emails Not Sending:
 *    - Check logs/app.log for error messages
 *    - Verify FROM_EMAIL is a valid email address
 *    - Check spam folder on recipient side
 *    - Verify ENABLE_EMAIL_NOTIFICATIONS is true
 * 
 * 5. Emails Not Being Processed:
 *    - Verify cron job is running: sudo crontab -u www-data -l
 *    - Check logs/cron.log for processing errors
 *    - Run manually: php email_to_ticket.php
 *    - Verify IMAP credentials and connection
 */
