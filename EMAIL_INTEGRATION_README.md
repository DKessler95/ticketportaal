# Email Integration Setup Guide

## Overview

The ICT Ticketportaal includes comprehensive email integration that supports:
- Automatic email notifications for ticket events
- Email-to-ticket conversion (users can create tickets by sending emails)
- Auto-reply functionality

## Components

### 1. EmailHandler.php Class
Located in `classes/EmailHandler.php`, this class handles:
- Sending ticket confirmation emails
- Sending status update notifications
- Sending assignment notifications
- Sending comment notifications
- Sending resolution notifications
- Parsing incoming emails
- Creating tickets from emails
- Finding or creating user accounts from email addresses

### 2. Email Notifications Integration
The `Ticket.php` class has been updated to automatically send email notifications when:
- A ticket is created → sends confirmation email
- A ticket is assigned → sends notification to both agent and user
- Ticket status changes → sends status update email
- A ticket is resolved → sends resolution notification
- A comment is added (non-internal) → sends comment notification

### 3. Email-to-Ticket Processor
The `email_to_ticket.php` script processes incoming emails and creates tickets automatically.

## Configuration

### Email Settings
Update the following settings in `config/email.php`:

```php
// SMTP Configuration
define('SMTP_HOST', 'mail.kruit-en-kramer.nl');
define('SMTP_PORT', 587);
define('SMTP_USER', 'ict@kruit-en-kramer.nl');
define('SMTP_PASS', 'your_email_password');
define('SMTP_ENCRYPTION', 'tls');

// From Address
define('FROM_EMAIL', 'ict@kruit-en-kramer.nl');
define('FROM_NAME', 'ICT Support - Kruit & Kramer');

// IMAP Configuration (for email-to-ticket)
define('IMAP_HOST', '{mail.kruit-en-kramer.nl:993/imap/ssl}INBOX');
define('IMAP_USER', 'ict@kruit-en-kramer.nl');
define('IMAP_PASS', 'your_email_password');
```

### PHP Mail Configuration
Ensure your PHP installation is configured to send emails. Update `php.ini`:

```ini
SMTP = mail.kruit-en-kramer.nl
smtp_port = 587
```

For production environments, consider using PHPMailer or SwiftMailer for more robust SMTP support.

## Setting Up Email-to-Ticket Processing

### Cron Job Setup

Add the following cron job to process emails every 5 minutes:

```bash
*/5 * * * * /usr/bin/php /path/to/ticketportaal/email_to_ticket.php >> /path/to/logs/email_cron.log 2>&1
```

### Manual Testing

You can manually test the email processor by running:

```bash
php email_to_ticket.php
```

Check the logs in `logs/app.log` for processing results.

## How Email-to-Ticket Works

1. The cron script connects to the IMAP mailbox
2. Searches for unread emails
3. For each unread email:
   - Extracts sender, subject, body, and attachments
   - Checks if sender email exists in the system
   - If not, creates a basic user account
   - Creates a ticket with the email content
   - Saves any attachments (up to 10MB, allowed types: pdf, doc, docx, jpg, jpeg, png, txt, zip)
   - Sends an auto-reply with the ticket number
   - Marks the email as read
4. Logs all processing activity

## Email Notification Types

### 1. Ticket Confirmation
Sent when a user creates a ticket via the web portal or email.
- Includes ticket number, title, priority, status, and description
- Provides link to track ticket

### 2. Assignment Notification
Sent when a ticket is assigned to an agent.
- Agent receives: ticket details and link to manage ticket
- User receives: notification that ticket is being handled

### 3. Status Update
Sent when ticket status changes.
- Includes new status and last update time
- If resolved, includes resolution text

### 4. Comment Notification
Sent when a new comment is added (public comments only).
- Includes the comment text and commenter name
- Link to view full conversation

### 5. Resolution Notification
Sent when a ticket is marked as resolved.
- Includes resolution details
- Prompts user to rate their experience

### 6. Auto-Reply
Sent when a ticket is created from email.
- Confirms receipt of email
- Provides ticket number for reference

## Troubleshooting

### Emails Not Sending

1. Check PHP mail configuration in `php.ini`
2. Verify SMTP credentials in `config/email.php`
3. Check `logs/app.log` for error messages
4. Test SMTP connection manually

### Email-to-Ticket Not Working

1. Verify IMAP credentials in `config/email.php`
2. Check if cron job is running: `crontab -l`
3. Check cron log file for errors
4. Verify PHP IMAP extension is installed: `php -m | grep imap`
5. Test manual execution: `php email_to_ticket.php`

### Installing PHP IMAP Extension

**Ubuntu/Debian:**
```bash
sudo apt-get install php-imap
sudo service apache2 restart
```

**CentOS/RHEL:**
```bash
sudo yum install php-imap
sudo service httpd restart
```

**Windows:**
Enable in `php.ini`:
```ini
extension=php_imap.dll
```

## Security Considerations

1. **Email Credentials**: Store email passwords securely, consider using environment variables
2. **Attachment Validation**: Only allowed file types are processed (configurable in code)
3. **File Size Limits**: Maximum 10MB per attachment
4. **User Creation**: Auto-created users get random passwords and must reset
5. **Email Parsing**: Strips signatures and quoted replies to keep tickets clean

## Future Enhancements

- Support for replying to tickets via email
- Rich HTML email templates
- Email threading (group related emails)
- Attachment virus scanning
- Email bounce handling
- Priority detection from email content
- Category auto-assignment based on keywords
