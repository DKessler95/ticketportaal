-- ============================================================================
-- Category Fields Verification Query
-- ============================================================================
-- Run this in your SQL console to verify all fields were created correctly
-- ============================================================================

-- Summary: Field counts per category
SELECT 
    c.category_id,
    c.name as category_name,
    COUNT(cf.field_id) as total_fields,
    SUM(CASE WHEN cf.is_active = 1 THEN 1 ELSE 0 END) as active_fields,
    SUM(CASE WHEN cf.is_required = 1 THEN 1 ELSE 0 END) as required_fields
FROM categories c
LEFT JOIN category_fields cf ON c.category_id = cf.category_id
GROUP BY c.category_id, c.name
ORDER BY c.category_id;

-- Expected results:
-- Hardware (1): 9 fields
-- Software (2): 8 fields  
-- Network (3): 10 fields
-- Account (4): 12 fields
-- Email (5): 6 fields
-- Security (6): 6 fields
-- Other (7): 3 fields

-- ============================================================================

-- Detailed: All fields by category
SELECT 
    c.name as category,
    cf.field_order,
    cf.field_label,
    cf.field_type,
    CASE WHEN cf.is_required = 1 THEN 'Yes' ELSE 'No' END as required,
    CASE WHEN cf.is_active = 1 THEN 'Active' ELSE 'Inactive' END as status
FROM categories c
JOIN category_fields cf ON c.category_id = cf.category_id
WHERE cf.is_active = 1
ORDER BY c.category_id, cf.field_order;

-- ============================================================================

-- Check for Hardware category fields (Task 5.1)
SELECT 
    field_label,
    field_type,
    CASE WHEN is_required = 1 THEN 'Required' ELSE 'Optional' END as required_status
FROM category_fields
WHERE category_id = 1 AND is_active = 1
ORDER BY field_order;

-- Expected Hardware fields:
-- 1. Type Hardware (select) - Required
-- 2. Merk (select) - Required
-- 3. Model (text) - Optional
-- 4. Serienummer (text) - Optional
-- 5. Locatie (select) - Required
-- 6. Afdeling (select) - Required
-- 7. Asset Tag (text) - Optional
-- 8. Aankoopdatum (date) - Optional
-- 9. Type Probleem (select) - Required

-- ============================================================================

-- Check for Software category fields (Task 5.2)
SELECT 
    field_label,
    field_type,
    CASE WHEN is_required = 1 THEN 'Required' ELSE 'Optional' END as required_status
FROM category_fields
WHERE category_id = 2 AND is_active = 1
ORDER BY field_order;

-- Expected Software fields:
-- 1. Applicatie Naam (select) - Required
-- 2. Andere Applicatie (text) - Optional
-- 3. Versie (text) - Optional
-- 4. Licentie Type (select) - Optional
-- 5. Installatie Locatie (select) - Required
-- 6. Type Probleem (select) - Required
-- 7. Foutmelding (textarea) - Optional
-- 8. Besturingssysteem (select) - Optional

-- ============================================================================

-- Check for Network category fields (Task 5.3)
SELECT 
    field_label,
    field_type,
    CASE WHEN is_required = 1 THEN 'Required' ELSE 'Optional' END as required_status
FROM category_fields
WHERE category_id = 3 AND is_active = 1
ORDER BY field_order;

-- Expected Network fields:
-- 1. Type Probleem (select) - Required
-- 2. Verbindingstype (select) - Required
-- 3. Locatie (select) - Required
-- 4. Switch/Router (text) - Optional
-- 5. Poort Nummer (text) - Optional
-- 6. VLAN (text) - Optional
-- 7. IP Adres (text) - Optional
-- 8. MAC Adres (text) - Optional
-- 9. WiFi Netwerk (select) - Optional
-- 10. Getroffen Diensten (checkbox) - Optional

-- ============================================================================

-- Check for Account category fields (Task 5.4)
SELECT 
    field_label,
    field_type,
    CASE WHEN is_required = 1 THEN 'Required' ELSE 'Optional' END as required_status
FROM category_fields
WHERE category_id = 4 AND is_active = 1
ORDER BY field_order;

-- Expected Account fields:
-- 1. Type Aanvraag (select) - Required
-- 2. Type Account (select) - Required
-- 3. Gebruikersnaam (text) - Required
-- 4. E-mailadres (email) - Required
-- 5. Volledige Naam (text) - Optional
-- 6. Afdeling (select) - Required
-- 7. Toegangsniveau (select) - Required
-- 8. Systeem (checkbox) - Required
-- 9. Externe Applicatie Naam (text) - Optional
-- 10. Manager/Leidinggevende (text) - Optional
-- 11. Startdatum (date) - Optional
-- 12. Einddatum (date) - Optional

-- ============================================================================

-- Total count verification
SELECT 
    COUNT(*) as total_active_fields,
    SUM(CASE WHEN is_required = 1 THEN 1 ELSE 0 END) as total_required_fields
FROM category_fields
WHERE is_active = 1;

-- Expected: 54 total active fields across all categories

-- ============================================================================
