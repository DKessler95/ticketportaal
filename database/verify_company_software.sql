-- ============================================================================
-- Verify Company-Specific Software Fields
-- ============================================================================

-- Check if software_name dropdown includes company applications
SELECT 
    field_label,
    field_options
FROM category_fields
WHERE category_id = 2 
  AND field_name = 'software_name'
  AND is_active = 1;

-- Should include: Ecoro SHD, Kassa, WinqlWise, Backup & Recovery

-- ============================================================================

-- Check all Software category fields (should be 14 total)
SELECT 
    field_order,
    field_name,
    field_label,
    field_type,
    CASE WHEN is_required = 1 THEN 'Required' ELSE 'Optional' END as required_status
FROM category_fields
WHERE category_id = 2 
  AND is_active = 1
ORDER BY field_order;

-- Expected fields:
-- 1. software_name (Applicatie Naam)
-- 2. software_name_custom (Andere Applicatie)
-- 3. software_version (Versie)
-- 4. license_type (Licentie Type)
-- 5. installation_location (Installatie Locatie)
-- 6. software_problem_type (Type Probleem)
-- 7. error_message (Foutmelding)
-- 8. operating_system (Besturingssysteem)
-- 20. ecoro_module (Ecoro SHD Module)
-- 21. kassa_location (Kassa Locatie)
-- 22. kassa_number (Kassa Nummer)
-- 23. winqlwise_issue (WinqlWise Probleem Type)
-- 24. backup_type (Backup Type)
-- 25. backup_action (Backup Actie)

-- ============================================================================

-- Count total Software fields
SELECT COUNT(*) as total_software_fields
FROM category_fields
WHERE category_id = 2 
  AND is_active = 1;

-- Expected: 14 fields

-- ============================================================================

-- Total fields across all categories
SELECT 
    c.name as category_name,
    COUNT(cf.field_id) as field_count
FROM categories c
LEFT JOIN category_fields cf ON c.category_id = cf.category_id AND cf.is_active = 1
GROUP BY c.category_id, c.name
ORDER BY c.category_id;

-- Expected totals:
-- Hardware: 9
-- Software: 14
-- Network: 10
-- Account: 12
-- Email: 6
-- Security: 6
-- Other: 3
-- TOTAL: 60 fields
