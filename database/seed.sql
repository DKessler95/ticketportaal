-- ICT Ticketportaal Seed Data
-- Created: 2025-10-03
-- Description: Initial data for categories and admin user

-- Insert default categories with SLA settings
INSERT INTO categories (name, description, default_priority, sla_hours, is_active) VALUES
('Hardware', 'Hardware-related issues including computers, printers, monitors, and peripherals', 'high', 8, TRUE),
('Software', 'Software installation, updates, licensing, and application issues', 'medium', 24, TRUE),
('Network', 'Network connectivity, VPN, Wi-Fi, and internet access issues', 'high', 4, TRUE),
('Account', 'User account management, password resets, and access permissions', 'medium', 12, TRUE),
('Email', 'Email configuration, delivery issues, and mailbox problems', 'medium', 12, TRUE),
('Security', 'Security concerns, virus alerts, and suspicious activity', 'urgent', 2, TRUE),
('Other', 'General ICT support requests that do not fit other categories', 'low', 48, TRUE);

-- Insert initial admin user
-- Email: admin@kruit-en-kramer.nl
-- Password: Admin123! (hashed with bcrypt, cost 12)
-- Note: This password should be changed immediately after first login
INSERT INTO users (email, password, first_name, last_name, department, role, is_active) VALUES
('admin@kruit-en-kramer.nl', '$2y$12$LQv3c1yycEir3LtJlTjkKuHPqVyJpbGlmKq7FZ8U9XvVkKvPX.Ks6', 'System', 'Administrator', 'ICT', 'admin', TRUE);

-- Insert sample knowledge base articles
INSERT INTO knowledge_base (category_id, title, content, tags, author_id, is_published, views) VALUES
(2, 'How to Reset Your Password', 
'# Password Reset Instructions

If you have forgotten your password, follow these steps:

1. Go to the login page
2. Click on "Forgot Password" link
3. Enter your email address
4. Check your email for a reset link
5. Click the link and enter your new password
6. Your password must be at least 8 characters and contain letters and numbers

If you do not receive the email within 5 minutes, check your spam folder or contact ICT support.',
'password,reset,login,account',
1,
TRUE,
0),

(3, 'Connecting to the Company VPN',
'# VPN Connection Guide

To connect to the Kruit & Kramer VPN:

## Windows
1. Click the network icon in the system tray
2. Select "VPN" from the menu
3. Choose "Kruit & Kramer VPN"
4. Enter your company credentials
5. Click "Connect"

## Mac
1. Open System Preferences
2. Click "Network"
3. Select the VPN connection
4. Click "Connect"
5. Enter your credentials

If you experience connection issues, ensure you have the latest VPN client installed. Contact ICT support for assistance.',
'vpn,network,remote,connection',
1,
TRUE,
0),

(1, 'Printer Not Working - Troubleshooting',
'# Printer Troubleshooting Guide

Before creating a ticket, try these steps:

1. **Check Power**: Ensure the printer is turned on and plugged in
2. **Check Connection**: Verify the USB or network cable is connected
3. **Check Paper**: Make sure there is paper in the tray
4. **Check Ink/Toner**: Verify ink or toner levels
5. **Restart Printer**: Turn off the printer, wait 30 seconds, turn it back on
6. **Check Print Queue**: Clear any stuck print jobs on your computer
7. **Restart Computer**: Sometimes a simple restart resolves the issue

If none of these steps work, create a ticket with details about the error message or problem.',
'printer,hardware,troubleshooting,printing',
1,
TRUE,
0),

(5, 'Email Configuration on Mobile Devices',
'# Mobile Email Setup

## iOS (iPhone/iPad)
1. Go to Settings > Mail > Accounts
2. Tap "Add Account"
3. Select "Other"
4. Enter your email and password
5. Use these settings:
   - Incoming Mail Server: mail.kruit-en-kramer.nl
   - Outgoing Mail Server: mail.kruit-en-kramer.nl
   - Use SSL: Yes

## Android
1. Open Email app
2. Tap "Add Account"
3. Select "Other"
4. Enter your email address
5. Choose IMAP
6. Enter the same server settings as above

Contact ICT if you need assistance with configuration.',
'email,mobile,configuration,setup,imap',
1,
TRUE,
0);

-- Note: The admin password hash above corresponds to "Admin123!"
-- This is for initial setup only and should be changed immediately
