<?php
/**
 * Database Configuration Template
 * 
 * Copy this file to database.php and update with your actual database credentials.
 * 
 * IMPORTANT: Never commit database.php to version control!
 * Add database.php to .gitignore to protect your credentials.
 */

// Database connection settings
define('DB_HOST', 'localhost');           // Database host (usually 'localhost')
define('DB_NAME', 'ticketportaal');       // Database name
define('DB_USER', 'ticketuser');          // Database username
define('DB_PASS', 'your_password_here');  // Database password - CHANGE THIS!
define('DB_CHARSET', 'utf8mb4');          // Character set (recommended: utf8mb4)

// Database connection options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
    PDO::ATTR_PERSISTENT         => false,                   // Don't use persistent connections
]);

/**
 * Configuration Instructions:
 * 
 * 1. Copy this file:
 *    cp config/database.example.php config/database.php
 * 
 * 2. Edit database.php with your actual credentials:
 *    - DB_HOST: Your MySQL server hostname (usually 'localhost')
 *    - DB_NAME: The name of your database (default: 'ticketportaal')
 *    - DB_USER: Your database username
 *    - DB_PASS: Your database password (use a strong password!)
 * 
 * 3. Ensure the database exists:
 *    CREATE DATABASE ticketportaal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 * 
 * 4. Grant privileges to the user:
 *    GRANT ALL PRIVILEGES ON ticketportaal.* TO 'ticketuser'@'localhost';
 *    FLUSH PRIVILEGES;
 * 
 * 5. Import the database schema:
 *    mysql -u ticketuser -p ticketportaal < database/schema.sql
 * 
 * 6. (Optional) Import seed data:
 *    mysql -u ticketuser -p ticketportaal < database/seed.sql
 */

// Example configurations for different environments:

/*
 * DEVELOPMENT ENVIRONMENT
 * Use localhost with standard credentials
 */
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'ticketportaal_dev');
// define('DB_USER', 'dev_user');
// define('DB_PASS', 'dev_password');

/*
 * PRODUCTION ENVIRONMENT
 * Use secure credentials and possibly remote host
 */
// define('DB_HOST', 'db.kruit-en-kramer.nl');
// define('DB_NAME', 'ticketportaal_prod');
// define('DB_USER', 'prod_user');
// define('DB_PASS', 'very_secure_password_here');

/*
 * TESTING ENVIRONMENT
 * Use separate database for testing
 */
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'ticketportaal_test');
// define('DB_USER', 'test_user');
// define('DB_PASS', 'test_password');
