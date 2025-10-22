-- Create table for category-specific fields
CREATE TABLE IF NOT EXISTS `category_fields` (
  `field_id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) NOT NULL,
  `field_name` VARCHAR(255) NOT NULL,
  `field_label` VARCHAR(255) NOT NULL,
  `field_type` ENUM('text', 'textarea', 'select', 'radio', 'checkbox', 'number', 'email', 'tel') NOT NULL DEFAULT 'text',
  `field_options` TEXT NULL COMMENT 'JSON array for select/radio/checkbox options',
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `field_order` INT(11) NOT NULL DEFAULT 0,
  `placeholder` VARCHAR(255) NULL,
  `help_text` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`field_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_category_field` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create table to store field values for tickets
CREATE TABLE IF NOT EXISTS `ticket_field_values` (
  `value_id` INT(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` INT(11) NOT NULL,
  `field_id` INT(11) NOT NULL,
  `field_value` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`value_id`),
  UNIQUE KEY `unique_ticket_field` (`ticket_id`, `field_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_field_id` (`field_id`),
  CONSTRAINT `fk_ticket_field_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_field_field` FOREIGN KEY (`field_id`) REFERENCES `category_fields` (`field_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert example fields for common categories
-- Assuming category IDs exist (adjust based on your actual category IDs)

-- Example for Hardware category (adjust category_id as needed)
INSERT INTO `category_fields` (`category_id`, `field_name`, `field_label`, `field_type`, `field_options`, `is_required`, `field_order`, `placeholder`, `help_text`) VALUES
(1, 'hardware_type', 'Type Hardware', 'select', '["Laptop", "Desktop Computer", "Beeldscherm", "Toetsenbord", "Muis", "Printer", "Scanner", "Overig"]', 1, 1, NULL, 'Selecteer het type hardware waar het probleem mee is'),
(1, 'hardware_brand', 'Merk', 'text', NULL, 0, 2, 'Bijv. Dell, HP, Lenovo', 'Vul het merk van de hardware in indien bekend'),
(1, 'hardware_model', 'Model', 'text', NULL, 0, 3, 'Bijv. Latitude 5520', 'Vul het model in indien bekend'),
(1, 'serial_number', 'Serienummer', 'text', NULL, 0, 4, 'Bijv. ABC123456', 'Te vinden op sticker op apparaat');

-- Example for Software category
INSERT INTO `category_fields` (`category_id`, `field_name`, `field_label`, `field_type`, `field_options`, `is_required`, `field_order`, `placeholder`, `help_text`) VALUES
(2, 'software_name', 'Software Naam', 'text', NULL, 1, 1, 'Bijv. Microsoft Office, Adobe Acrobat', 'Naam van de software'),
(2, 'software_version', 'Versie', 'text', NULL, 0, 2, 'Bijv. 2021, 365', 'Versie van de software indien bekend'),
(2, 'license_needed', 'Licentie Nodig?', 'radio', '["Ja", "Nee", "Weet ik niet"]', 1, 3, NULL, 'Moet er een nieuwe licentie worden aangeschaft?');

-- Example for Account/Access category
INSERT INTO `category_fields` (`category_id`, `field_name`, `field_label`, `field_type`, `field_options`, `is_required`, `field_order`, `placeholder`, `help_text`) VALUES
(3, 'account_type', 'Type Account', 'select', '["E-mail Account", "Netwerk Account", "Applicatie Account", "Database Toegang", "VPN Toegang", "Overig"]', 1, 1, NULL, 'Selecteer het type account'),
(3, 'account_for', 'Account Voor', 'text', NULL, 1, 2, 'Naam van de gebruiker', 'Voor wie is dit account bedoeld?'),
(3, 'access_level', 'Toegangsniveau', 'select', '["Lezen", "Lezen/Schrijven", "Administrator", "Standaard Gebruiker"]', 1, 3, NULL, 'Welk toegangsniveau is nodig?'),
(3, 'external_application', 'Externe Applicatie?', 'radio', '["Ja", "Nee"]', 1, 4, NULL, 'Is dit voor een externe applicatie?'),
(3, 'application_name', 'Applicatie Naam', 'text', NULL, 0, 5, 'Naam van de applicatie', 'Indien externe applicatie, vul de naam in');

-- Example for Network category
INSERT INTO `category_fields` (`category_id`, `field_name`, `field_label`, `field_type`, `field_options`, `is_required`, `field_order`, `placeholder`, `help_text`) VALUES
(4, 'network_issue_type', 'Type Probleem', 'select', '["Geen Verbinding", "Trage Verbinding", "Intermitterende Verbinding", "VPN Probleem", "WiFi Probleem", "Overig"]', 1, 1, NULL, 'Wat voor soort netwerkprobleem ervaart u?'),
(4, 'location', 'Locatie', 'text', NULL, 1, 2, 'Bijv. Kantoor, Thuiswerken, Vestiging X', 'Waar bevindt u zich?'),
(4, 'connection_type', 'Verbindingstype', 'radio', '["Bekabeld (LAN)", "Draadloos (WiFi)", "VPN"]', 1, 3, NULL, 'Hoe probeert u verbinding te maken?');
