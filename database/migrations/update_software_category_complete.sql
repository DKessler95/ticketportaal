-- ============================================================================
-- Complete Software Category Update with Company-Specific Applications
-- ============================================================================
-- This script:
-- 1. Updates the software_name dropdown with company applications
-- 2. Adds detailed fields for Ecoro SHD, Kassa, WinqlWise, and Backup & Recovery
-- 
-- Company Applications:
-- - Ecoro SHD: Verkooporders, bestellingen, magazijn, logistiek, planning, facturen
-- - Kassa: Kassasysteem
-- - WinqlWise: IMG leveranciersportaal
-- - Backup & Recovery: Backup en herstel systemen
-- ============================================================================

-- Step 1: Update software_name dropdown with company applications
UPDATE category_fields 
SET field_options = '["Microsoft Office", "Microsoft 365", "Microsoft Teams", "Outlook", "Excel", "Word", "PowerPoint", "Adobe Acrobat", "Adobe Photoshop", "Google Chrome", "Mozilla Firefox", "Zoom", "AutoCAD", "SAP", "Ecoro SHD", "Kassa", "WinqlWise", "Backup & Recovery", "ERP Systeem", "CRM Systeem", "Antivirus Software", "VPN Client", "Overig"]'
WHERE category_id = 2 
  AND field_name = 'software_name'
  AND is_active = 1;

-- Step 2: Add Ecoro SHD specific field
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'ecoro_module', 'Ecoro SHD Module', 'select', 
 '["Verkooporders", "Bestellingen", "Magazijn", "Logistiek", "Planning", "Facturen", "Algemeen", "Weet ik niet"]', 
 0, 20, NULL, 'In welke module van Ecoro SHD zit het probleem? (alleen invullen bij Ecoro SHD problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

-- Step 3: Add Kassa specific fields
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'kassa_location', 'Kassa Locatie', 'select', 
 '["Kantoor Hengelo", "Kantoor Enschede", "Vestiging Almelo", "Showroom", "Overig"]', 
 0, 21, NULL, 'Op welke locatie staat de kassa? (alleen invullen bij Kassa problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'kassa_number', 'Kassa Nummer', 'text', NULL, 
 0, 22, 'Bijv. Kassa 1, Kassa 2', 'Welk kassanummer heeft het probleem? (alleen invullen bij Kassa problemen)')
ON DUPLICATE KEY UPDATE placeholder = VALUES(placeholder);

-- Step 4: Add WinqlWise specific field
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'winqlwise_issue', 'WinqlWise Probleem Type', 'select', 
 '["Kan niet inloggen", "Bestellingen plaatsen", "Voorraad inzien", "Prijzen/Offertes", "Leverancier communicatie", "Overig"]', 
 0, 23, NULL, 'Wat is het probleem met WinqlWise? (alleen invullen bij WinqlWise problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

-- Step 5: Add Backup & Recovery specific fields
INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'backup_type', 'Backup Type', 'select', 
 '["Automatische backup", "Handmatige backup", "Cloud backup", "Lokale backup", "Weet ik niet"]', 
 0, 24, NULL, 'Welk type backup heeft het probleem? (alleen invullen bij Backup problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
(2, 'backup_action', 'Backup Actie', 'select', 
 '["Backup maken", "Backup herstellen", "Backup verificatie", "Backup schema wijzigen", "Overig"]', 
 0, 25, NULL, 'Wat probeert u te doen? (alleen invullen bij Backup problemen)')
ON DUPLICATE KEY UPDATE field_options = VALUES(field_options);

-- ============================================================================
-- Verification Queries
-- ============================================================================

-- Check updated software_name dropdown
SELECT 'Software Name Dropdown:' as info;
SELECT field_label, field_options
FROM category_fields
WHERE category_id = 2 AND field_name = 'software_name';

-- Check all Software category fields
SELECT '---' as separator;
SELECT 'All Software Category Fields:' as info;
SELECT 
    field_order,
    field_label,
    field_type,
    CASE WHEN is_required = 1 THEN 'Required' ELSE 'Optional' END as required_status,
    CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as status
FROM category_fields
WHERE category_id = 2 AND is_active = 1
ORDER BY field_order;

-- Count total fields
SELECT '---' as separator;
SELECT 'Field Count:' as info;
SELECT COUNT(*) as total_software_fields
FROM category_fields
WHERE category_id = 2 AND is_active = 1;

-- Expected: 14 fields total (8 original + 6 new company-specific fields)

-- ============================================================================
-- Application Reference Guide
-- ============================================================================
-- 
-- ECORO SHD
-- ---------
-- Bedrijfsapplicatie voor:
-- - Verkooporders: Klantorders beheren en verwerken
-- - Bestellingen: Inkooporders bij leveranciers
-- - Magazijn: Voorraad beheer en mutaties
-- - Logistiek: Transport en verzending
-- - Planning: Productie en resource planning
-- - Facturen: Facturatie en administratie
--
-- KASSA
-- -----
-- Kassasysteem voor verkooppunten
-- - Meerdere locaties (Hengelo, Enschede, Almelo, Showroom)
-- - Genummerde kassa's per locatie
--
-- WINQLWISE
-- ---------
-- IMG Leveranciersportaal voor:
-- - Inloggen en authenticatie
-- - Bestellingen plaatsen bij leveranciers
-- - Voorraad inzien
-- - Prijzen en offertes opvragen
-- - Communicatie met leveranciers
--
-- BACKUP & RECOVERY
-- -----------------
-- Backup en herstel systemen:
-- - Automatische backups (scheduled)
-- - Handmatige backups (on-demand)
-- - Cloud backups (off-site)
-- - Lokale backups (on-premise)
-- - Backup maken, herstellen, verificatie
--
-- ============================================================================
