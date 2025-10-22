-- ============================================================================
-- Add Detailed Fields for Company-Specific Software
-- ============================================================================
-- Adds additional context fields for Kruit & Kramer business applications
-- to improve AI understanding and ticket resolution.
-- ============================================================================

-- Add Ecoro SHD specific fields
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'ecoro_module', 'Ecoro SHD Module', 'select', 
 '["Verkooporders", "Bestellingen", "Magazijn", "Logistiek", "Planning", "Facturen", "Algemeen", "Weet ik niet"]', 
 0, 20, NULL, 'In welke module van Ecoro SHD zit het probleem? (alleen invullen bij Ecoro SHD problemen)');

-- Add Kassa specific fields
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'kassa_location', 'Kassa Locatie', 'select', 
 '["Kantoor Hengelo", "Kantoor Enschede", "Vestiging Almelo", "Showroom", "Overig"]', 
 0, 21, NULL, 'Op welke locatie staat de kassa? (alleen invullen bij Kassa problemen)'),

(2, 'kassa_number', 'Kassa Nummer', 'text', NULL, 
 0, 22, 'Bijv. Kassa 1, Kassa 2', 'Welk kassanummer heeft het probleem? (alleen invullen bij Kassa problemen)');

-- Add WinqlWise specific fields
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'winqlwise_issue', 'WinqlWise Probleem Type', 'select', 
 '["Kan niet inloggen", "Bestellingen plaatsen", "Voorraad inzien", "Prijzen/Offertes", "Leverancier communicatie", "Overig"]', 
 0, 23, NULL, 'Wat is het probleem met WinqlWise? (alleen invullen bij WinqlWise problemen)');

-- Add Backup & Recovery specific fields
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'backup_type', 'Backup Type', 'select', 
 '["Automatische backup", "Handmatige backup", "Cloud backup", "Lokale backup", "Weet ik niet"]', 
 0, 24, NULL, 'Welk type backup heeft het probleem? (alleen invullen bij Backup problemen)'),

(2, 'backup_action', 'Backup Actie', 'select', 
 '["Backup maken", "Backup herstellen", "Backup verificatie", "Backup schema wijzigen", "Overig"]', 
 0, 25, NULL, 'Wat probeert u te doen? (alleen invullen bij Backup problemen)');

-- ============================================================================
-- Verification Query
-- ============================================================================
-- Run this to verify the new fields were added:
-- 
-- SELECT field_label, field_type, field_order
-- FROM category_fields
-- WHERE category_id = 2 AND field_order >= 20
-- ORDER BY field_order;
-- ============================================================================
