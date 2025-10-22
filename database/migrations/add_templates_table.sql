-- Create templates table for ticket resolution templates
CREATE TABLE IF NOT EXISTS `ticket_templates` (
  `template_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `content` TEXT NOT NULL,
  `template_type` ENUM('resolution', 'comment', 'email') NOT NULL DEFAULT 'resolution',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`),
  KEY `idx_template_type` (`template_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `fk_template_created_by` (`created_by`),
  CONSTRAINT `fk_template_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default resolution templates
INSERT INTO `ticket_templates` (`name`, `description`, `content`, `template_type`, `created_by`) VALUES
('Standaard Oplossing', 'Standaard template voor ticket afsluiting', '<p><strong>Oplossing:</strong></p><p>Uw ticket is succesvol opgelost.</p><p><strong>Uitgevoerde acties:</strong></p><ul><li>[Beschrijf hier de uitgevoerde acties]</li></ul><p>Mocht u nog vragen hebben, aarzel dan niet om contact met ons op te nemen.</p><p>Met vriendelijke groet,<br>ICT Support Team</p>', 'resolution', 1),
('Wachtwoord Reset', 'Template voor wachtwoord reset tickets', '<p><strong>Oplossing:</strong></p><p>Uw wachtwoord is succesvol gereset.</p><p><strong>Uitgevoerde acties:</strong></p><ul><li>Wachtwoord is gereset naar tijdelijk wachtwoord</li><li>U ontvangt een e-mail met instructies</li><li>Bij eerste inlog wordt u gevraagd een nieuw wachtwoord in te stellen</li></ul><p>Mocht u problemen ondervinden bij het inloggen, neem dan contact met ons op.</p><p>Met vriendelijke groet,<br>ICT Support Team</p>', 'resolution', 1),
('Software Installatie', 'Template voor software installatie tickets', '<p><strong>Oplossing:</strong></p><p>De gevraagde software is succesvol geïnstalleerd.</p><p><strong>Geïnstalleerde software:</strong></p><ul><li>[Software naam en versie]</li></ul><p><strong>Locatie:</strong> [Pad naar software]</p><p>De software is klaar voor gebruik. Mocht u ondersteuning nodig hebben bij het gebruik, raadpleeg dan onze Knowledge Base of neem contact met ons op.</p><p>Met vriendelijke groet,<br>ICT Support Team</p>', 'resolution', 1),
('Toegang Verleend', 'Template voor toegangsverzoeken', '<p><strong>Oplossing:</strong></p><p>De gevraagde toegang is verleend.</p><p><strong>Details:</strong></p><ul><li>Toegang tot: [Systeem/Map/Applicatie]</li><li>Rechten: [Lezen/Schrijven/Admin]</li><li>Geldig vanaf: [Datum]</li></ul><p>U kunt nu toegang krijgen tot de gevraagde resources. Test de toegang en laat het ons weten als er problemen zijn.</p><p>Met vriendelijke groet,<br>ICT Support Team</p>', 'resolution', 1),
('Probleem Niet Reproduceerbaar', 'Template wanneer probleem niet kan worden gereproduceerd', '<p><strong>Oplossing:</strong></p><p>We hebben uw melding onderzocht maar konden het probleem niet reproduceren.</p><p><strong>Uitgevoerde controles:</strong></p><ul><li>[Beschrijf de uitgevoerde tests]</li></ul><p>Het probleem lijkt opgelost te zijn. Mocht het probleem opnieuw optreden, neem dan contact met ons op en vermeld:</p><ul><li>Exacte stappen om het probleem te reproduceren</li><li>Screenshots of foutmeldingen</li><li>Tijdstip waarop het probleem optreedt</li></ul><p>Met vriendelijke groet,<br>ICT Support Team</p>', 'resolution', 1);
