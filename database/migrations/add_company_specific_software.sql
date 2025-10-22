-- ============================================================================
-- Add Company-Specific Software Applications
-- ============================================================================
-- Adds Kruit & Kramer specific business applications to the Software category
-- dropdown options for better data quality and AI training.
-- ============================================================================

-- Update the software_name field to include company-specific applications
UPDATE category_fields 
SET field_options = '["Microsoft Office", "Microsoft 365", "Microsoft Teams", "Outlook", "Excel", "Word", "PowerPoint", "Adobe Acrobat", "Adobe Photoshop", "Google Chrome", "Mozilla Firefox", "Zoom", "AutoCAD", "SAP", "Ecoro SHD", "Kassa", "WinqlWise", "Backup & Recovery", "ERP Systeem", "CRM Systeem", "Antivirus Software", "VPN Client", "Overig"]'
WHERE category_id = 2 
  AND field_name = 'software_name'
  AND is_active = 1;

-- Verify the update
SELECT 
    field_label,
    field_options
FROM category_fields
WHERE category_id = 2 
  AND field_name = 'software_name';

-- ============================================================================
-- Application Descriptions (for reference)
-- ============================================================================
-- Ecoro SHD: Bedrijfsapplicatie voor verkooporders, bestellingen, magazijn, 
--            logistiek, planning, en facturen
-- Kassa: Kassasysteem voor verkooppunten
-- WinqlWise: IMG leveranciersportaal
-- Backup & Recovery: Backup en herstel systemen
-- ============================================================================
