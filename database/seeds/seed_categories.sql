-- Seed Categories
-- Clean and repopulate categories table with proper data

-- First, delete all existing categories
DELETE FROM categories;

-- Reset auto increment
ALTER TABLE categories AUTO_INCREMENT = 1;

-- Insert standard ICT categories
INSERT INTO categories (name, description, default_priority, sla_hours, is_active) VALUES
('Hardware', 'Hardware problemen zoals computers, printers, monitors, etc.', 'medium', 24, 1),
('Software', 'Software installaties, licenties en applicatie problemen', 'medium', 24, 1),
('Netwerk', 'Netwerk connectiviteit, WiFi, VPN en internet problemen', 'high', 8, 1),
('Account', 'Account aanvragen, wachtwoord resets en toegangsbeheer', 'low', 48, 1),
('Email', 'Email problemen, configuratie en Outlook issues', 'medium', 24, 1),
('Telefonie', 'Telefoon systemen, mobiele telefoons en voicemail', 'medium', 24, 1),
('Beveiliging', 'Security incidents, virus meldingen en verdachte activiteiten', 'urgent', 4, 1),
('Backup & Recovery', 'Data backup, restore requests en file recovery', 'high', 8, 1);

-- Verify insert
SELECT * FROM categories ORDER BY category_id;
