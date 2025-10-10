# ICT Ticketportaal - Kruit & Kramer

A comprehensive web-based ticket management system for ICT support operations.

## Project Structure

```
/ticketportaal/
├── /admin/              # Admin portal pages
├── /agent/              # Agent portal pages
├── /api/                # API endpoints for AJAX operations
├── /assets/             # Static assets
│   ├── /css/           # Stylesheets
│   ├── /images/        # Images and logos
│   └── /js/            # JavaScript files
├── /classes/            # Core business logic classes
├── /config/             # Configuration files
├── /database/           # Database schema and seed files
├── /includes/           # Reusable UI components and helpers
├── /logs/               # Application logs
├── /uploads/            # User-uploaded files
│   └── /tickets/       # Ticket attachments
└── /user/               # User portal pages
```

## Features

- **Dual Ticket Creation**: Web portal and email integration
- **Role-Based Access**: User, Agent, and Admin roles
- **Knowledge Base**: Self-service help articles
- **Email Notifications**: Automated updates for ticket events
- **SLA Tracking**: Category-based SLA monitoring
- **File Attachments**: Support for ticket attachments
- **Reporting**: Analytics and performance metrics
- **Responsive Design**: Mobile-friendly interface

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, JavaScript
- **Email**: PHP IMAP for email parsing
- **Server**: Apache/Nginx with mod_rewrite

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- SSL certificate (recommended)

### Setup Steps

1. **Clone or extract the project files**

2. **Configure the database**
   - See `database/README.md` for detailed instructions
   - Run `schema.sql` to create tables
   - Run `seed.sql` to insert initial data

3. **Configure the application**
   - Copy configuration templates from `/config/`
   - Update database credentials
   - Configure email settings for Collax integration

4. **Set file permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 logs/
   chmod 644 config/*.php
   ```

5. **Set up cron job for email processing**
   ```bash
   */5 * * * * /usr/bin/php /path/to/ticketportaal/email_to_ticket.php
   ```

## Default Login

After installation, log in with:
- **Email**: admin@kruit-en-kramer.nl
- **Password**: Admin123!

**⚠️ Change this password immediately after first login!**

## Security

- All passwords are hashed using bcrypt
- CSRF protection on all forms
- Prepared statements for SQL queries
- Input validation and sanitization
- Session security with timeout
- File upload validation

## Development Status

This project is currently under development. See `.kiro/specs/ict-ticketportaal/tasks.md` for implementation progress.

## Support

For issues or questions, contact the ICT department at ict@kruit-en-kramer.nl

## License

Internal use only - Kruit & Kramer
