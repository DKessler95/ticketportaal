-- ============================================================================
-- Update Software Category with Company-Specific Applications
-- ============================================================================
-- Adds: Ecoro SHD, Kassa, WinqlWise, Backup & Recovery
-- ============================================================================

-- Update software_name dropdown
UPDATE category_fields 
SET field_options = '["Microsoft Office", "Microsoft 365", "Microsoft Teams", "Outlook", "Excel", "Word", "PowerPoint", "Adobe Acrobat", "Adobe Photoshop", "Google Chrome", "Mozilla Firefox", "Zoom", "AutoCAD", "SAP", "Ecoro SHD", "Kassa", "WinqlWise", "Backup & Recovery", "ERP Systeem", "CRM Systeem", "Antivirus Software", "VPN Client", "Overig"]'
WHERE category_id = 2 
  AND field_name = 'software_name'
  AND is_active = 1;

-- Add Ecoro SHD module field
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'ecoro_module', 'Ecoro SHD Module', 'select', 
 '["Verkooporders", "Bestellingen", "Magazijn", "Logistiek", "Planning", "Facturen", "Algemeen", "Weet ik niet"]', 
 0, 20, NULL, 'In welke module van Ecoro SHD zit het probleem? (alleen invullen bij Ecoro SHD problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

-- Add Kassa location field
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'kassa_location', 'Kassa Locatie', 'select', 
 '["Kantoor Hengelo", "Kantoor Enschede", "Vestiging Almelo", "Showroom", "Overig"]', 
 0, 21, NULL, 'Op welke locatie staat de kassa? (alleen invullen bij Kassa problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

-- Add Kassa number field
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'kassa_number', 'Kassa Nummer', 'text', NULL, 
 0, 22, 'Bijv. Kassa 1, Kassa 2', 'Welk kassanummer heeft het probleem? (alleen invullen bij Kassa problemen)')
ON DUPLICATE KEY UPDATE placeholder = VALUES(placeholder);

-- Add WinqlWise issue field
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'winqlwise_issue', 'WinqlWise Probleem Type', 'select', 
 '["Kan niet inloggen", "Bestellingen plaatsen", "Voorraad inzien", "Prijzen/Offertes", "Leverancier communicatie", "Overig"]', 
 0, 23, NULL, 'Wat is het probleem met WinqlWise? (alleen invullen bij WinqlWise problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

-- Add Backup type field
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'backup_type', 'Backup Type', 'select', 
 '["Automatische backup", "Handmatige backup", "Cloud backup", "Lokale backup", "Weet ik niet"]', 
 0, 24, NULL, 'Welk type backup heeft het probleem? (alleen invullen bij Backup problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

-- Add Backup action field
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'backup_action', 'Backup Actie', 'select', 
 '["Backup maken", "Backup herstellen", "Backup verificatie", "Backup schema wijzigen", "Overig"]', 
 0, 25, NULL, 'Wat probeert u te doen? (alleen invullen bij Backup problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);
