<?php
/**
 * Application Configuration
 * 
 * General application settings for the ICT Ticketportaal
 * 
 * IMPORTANT: Update these values for your environment
 */

// Site URL (no trailing slash)
// For localhost development with port 8080, use http://localhost:8080/ticketportaal
// For localhost default port 80, use http://localhost/ticketportaal
// For production, use https://tickets.kruit-en-kramer.nl
define('SITE_URL', 'http://localhost:8080/ticketportaal');

// Site name
define('SITE_NAME', 'ICT Ticketportaal');

// Company name
define('COMPANY_NAME', 'Digimaatwerk Systems');

// Session timeout in seconds (30 minutes = 1800 seconds)
define('SESSION_TIMEOUT', 1800);

// Maximum file upload size in bytes (10MB = 10485760 bytes)
define('MAX_FILE_SIZE', 10485760);

// Upload directory path (relative to project root)
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Allowed file extensions for uploads
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'zip']);

// Timezone
define('TIMEZONE', 'Europe/Amsterdam');
date_default_timezone_set(TIMEZONE);

// Error reporting (set to 0 in production)
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Log file path
define('LOG_PATH', __DIR__ . '/../logs/');

// Password requirements
define('PASSWORD_MIN_LENGTH', 8);

// Failed login attempt settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes in seconds
define('ACCOUNT_LOCK_DURATION', 1800); // 30 minutes in seconds

// Pagination settings
define('ITEMS_PER_PAGE', 25);

// Ticket number prefix
define('TICKET_PREFIX', 'KK');

// Default SLA hours
define('DEFAULT_SLA_HOURS', 24);
