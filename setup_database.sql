-- Setup Database and User for ICT Ticketportaal
-- Run this in phpMyAdmin or MySQL command line

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS ticketportaal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (for XAMPP localhost, usually no password needed for development)
-- For production, use a strong password!
CREATE USER IF NOT EXISTS 'ticketuser'@'localhost' IDENTIFIED BY 'secure_password';

-- Grant all privileges on the ticketportaal database to ticketuser
GRANT ALL PRIVILEGES ON ticketportaal.* TO 'ticketuser'@'localhost';

-- Apply the changes
FLUSH PRIVILEGES;

-- Verify the user was created
SELECT User, Host FROM mysql.user WHERE User = 'ticketuser';

-- Show databases to verify ticketportaal exists
SHOW DATABASES LIKE 'ticketportaal';
