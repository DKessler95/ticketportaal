-- CI & Change Management - Seed Data
-- Test data for development

-- Insert sample Configuration Items
INSERT INTO configuration_items (ci_number, type, category, brand, model, name, serial_number, status, owner_id, department, location, purchase_date, purchase_price, supplier, warranty_expiry, notes, created_by) VALUES
('CI-2025-001', 'Hardware', 'Laptop', 'Dell', 'Latitude 5420', 'Dell Laptop - Sales Manager', 'DL5420-2024-001', 'In gebruik', 2, 'Sales', 'Kantoor', '2024-01-15', 899.00, 'Dell Nederland', '2027-01-15', 'Standaard configuratie met Windows 11 Pro', 1),
('CI-2025-002', 'Hardware', 'Desktop', 'HP', 'EliteDesk 800', 'HP Desktop - Administratie', 'HP800-2024-002', 'In gebruik', 2, 'Administratie', 'Kantoor', '2024-02-20', 1299.00, 'HP Store', '2027-02-20', 'i7 processor, 16GB RAM, 512GB SSD', 1),
('CI-2025-003', 'Hardware', 'Monitor', 'Samsung', '27" 4K', 'Samsung Monitor 27 inch', 'SAM27-2024-003', 'In gebruik', 2, 'ICT', 'Kantoor', '2024-03-10', 349.00, 'Coolblue', '2026-03-10', NULL, 1),
('CI-2025-004', 'Hardware', 'Printer', 'HP', 'LaserJet Pro', 'HP Printer - Algemeen', 'HPLJ-2023-004', 'In gebruik', NULL, 'Algemeen', 'Kantoor - Gang', '2023-06-01', 599.00, 'Office Centre', '2026-06-01', 'Netwerk printer voor hele kantoor', 1),
('CI-2025-005', 'Software', 'Licentie', 'Microsoft', 'Office 365', 'Microsoft 365 Business - 10 licenties', 'M365-2024-001', 'In gebruik', NULL, 'ICT', 'Cloud', '2024-01-01', 1200.00, 'Microsoft', '2025-01-01', 'Jaarlijkse licentie, verlengen in december', 1),
('CI-2025-006', 'Hardware', 'Router', 'Cisco', 'RV340', 'Cisco Router - Hoofdkantoor', 'CISCO-RV340-001', 'In gebruik', NULL, 'ICT', 'Serverruimte', '2023-03-15', 450.00, 'Cisco Partner', '2026-03-15', 'VPN configuratie actief', 1),
('CI-2025-007', 'Hardware', 'Laptop', 'Lenovo', 'ThinkPad X1', 'Lenovo Laptop - Directie', 'LEN-X1-2024-007', 'In gebruik', 1, 'Directie', 'Kantoor', '2024-05-20', 1599.00, 'Lenovo Store', '2027-05-20', 'Premium model met extra garantie', 1),
('CI-2025-008', 'Hardware', 'Server', 'Dell', 'PowerEdge T340', 'Dell Server - Applicaties', 'DELL-T340-2023', 'In gebruik', NULL, 'ICT', 'Serverruimte', '2023-01-10', 2499.00, 'Dell Nederland', '2026-01-10', 'Draait ticketportaal en andere applicaties', 1),
('CI-2025-009', 'Hardware', 'Telefoon', 'Apple', 'iPhone 14', 'iPhone - Sales', 'IPHONE14-2024-009', 'In gebruik', 2, 'Sales', 'Mobiel', '2024-04-01', 899.00, 'Apple Store', '2026-04-01', 'Zakelijk abonnement', 1),
('CI-2025-010', 'Hardware', 'Monitor', 'Dell', '24" Full HD', 'Dell Monitor - Reserve', 'DELL24-2023-010', 'In voorraad', NULL, 'ICT', 'Magazijn', '2023-11-15', 199.00, 'Coolblue', '2025-11-15', 'Reserve monitor voor vervanging', 1);

-- Insert sample Changes
INSERT INTO changes (change_number, title, requested_by, assigned_to, type, priority, impact, status, description, reason, expected_result, affected_systems, affected_users, downtime_expected, downtime_duration, risk_assessment, implementation_plan, rollback_plan, resources_needed, planned_date) VALUES
('CHG-2025-001', 'Upgrade naar Windows 11 voor alle laptops', 1, 1, 'Software', 'Hoog', 'Hoog', 'Goedgekeurd', 
'Alle bedrijfslaptops upgraden van Windows 10 naar Windows 11 Pro', 
'Windows 10 support eindigt in oktober 2025. Upgrade noodzakelijk voor security updates', 
'Alle laptops draaien Windows 11 met laatste security patches',
'Alle laptops (15 stuks), mogelijk compatibiliteitsproblemen met oude software',
15, TRUE, 120,
'Middel risico: Mogelijk compatibiliteitsproblemen met legacy software. Backup van alle data noodzakelijk',
'1. Backup maken van alle laptops\n2. Test upgrade op 1 laptop\n3. Upgrade uitrollen per afdeling\n4. Verificatie en troubleshooting',
'Windows 10 recovery image beschikbaar. Restore vanuit backup mogelijk binnen 2 uur',
'Windows 11 licenties, 2 dagen tijd, externe USB drives voor backup',
'2025-02-01'),

('CHG-2025-002', 'Nieuwe firewall regel voor externe toegang', 1, 1, 'Netwerk', 'Urgent', 'Middel', 'Ingepland',
'Firewall regel toevoegen voor externe toegang tot ticketportaal',
'Medewerkers moeten vanaf thuis toegang hebben tot het ticketportaal',
'Veilige externe toegang via VPN naar ticketportaal',
'Firewall, VPN server, Ticketportaal',
25, FALSE, 0,
'Laag risico: Alleen VPN gebruikers krijgen toegang. Bestaande security blijft intact',
'1. VPN configuratie controleren\n2. Firewall regel aanmaken\n3. Port forwarding instellen\n4. Testen met test account\n5. Documentatie updaten',
'Firewall regel verwijderen. VPN configuratie terugdraaien',
'Toegang tot firewall, 1 uur tijd',
'2025-01-20'),

('CHG-2025-003', 'Email server migratie naar nieuwe Collax versie', 1, 1, 'Infrastructuur', 'Hoog', 'Hoog', 'In beoordeling',
'Collax email server upgraden naar nieuwste versie',
'Huidige versie heeft security vulnerabilities. Update noodzakelijk',
'Email server draait op nieuwste versie met alle security patches',
'Email server, alle email clients, ticketportaal email integratie',
50, TRUE, 30,
'Hoog risico: Email downtime tijdens migratie. Mogelijk configuratie problemen',
'1. Backup van huidige Collax configuratie\n2. Test upgrade op staging server\n3. Maintenance window inplannen (weekend)\n4. Upgrade uitvoeren\n5. Email flow testen\n6. Gebruikers informeren',
'Restore vanuit backup. Rollback naar oude versie mogelijk binnen 1 uur',
'Collax licentie, 4 uur downtime, weekend beschikbaarheid',
'2025-02-15'),

('CHG-2025-004', 'Nieuwe printer installeren - Afdeling Sales', 2, 1, 'Hardware', 'Normaal', 'Laag', 'Goedgekeurd',
'HP LaserJet Pro installeren voor Sales afdeling',
'Oude printer is defect en niet meer te repareren',
'Sales heeft werkende printer met netwerk toegang',
'Netwerk, print server, Sales afdeling',
5, FALSE, 0,
'Laag risico: Standaard printer installatie',
'1. Printer uitpakken en plaatsen\n2. Netwerk configureren\n3. Drivers installeren op alle Sales PC''s\n4. Test prints uitvoeren',
'Oude printer tijdelijk weer aansluiten indien nodig',
'Nieuwe printer (al besteld), 2 uur tijd',
'2025-01-18'),

('CHG-2025-005', 'Ticketportaal uitbreiden met CI & Change Management', 1, 1, 'Feature', 'Hoog', 'Middel', 'Nieuw',
'CI en Change Management modules toevoegen aan ticketportaal',
'Beter overzicht van hardware assets en gestructureerd wijzigingsbeheer',
'Volledig werkende CI en Change Management modules geïntegreerd in ticketportaal',
'Ticketportaal applicatie, database',
25, FALSE, 0,
'Laag risico: Nieuwe functionaliteit, bestaande features blijven werken',
'1. Database schema uitbreiden\n2. CI Management module ontwikkelen\n3. Change Management module ontwikkelen\n4. Integratie met tickets\n5. Testen en deployment',
'Database rollback naar vorige versie. Nieuwe modules uitschakelen',
'Development tijd (6-7 weken), test omgeving',
'2025-03-01');

-- Link some CIs to changes
INSERT INTO change_ci_relations (change_id, ci_id) VALUES
(1, 1), -- Windows 11 upgrade affects laptop CI-2025-001
(1, 7), -- Windows 11 upgrade affects laptop CI-2025-007
(3, 8), -- Email migration affects server CI-2025-008
(4, 4); -- New printer change (will replace old printer)

-- Add some change history
INSERT INTO change_history (change_id, user_id, action, old_status, new_status, comment) VALUES
(1, 1, 'created', NULL, 'Nieuw', 'Change aangemaakt voor Windows 11 upgrade'),
(1, 1, 'status_changed', 'Nieuw', 'In beoordeling', 'Change in review genomen'),
(1, 1, 'approved', 'In beoordeling', 'Goedgekeurd', 'Goedgekeurd door IT Manager. Planning gemaakt voor februari'),
(2, 1, 'created', NULL, 'Nieuw', 'Firewall change aangemaakt voor externe toegang'),
(2, 1, 'status_changed', 'Nieuw', 'Goedgekeurd', 'Urgent - goedgekeurd voor implementatie'),
(2, 1, 'status_changed', 'Goedgekeurd', 'Ingepland', 'Ingepland voor 20 januari'),
(4, 1, 'created', NULL, 'Nieuw', 'Nieuwe printer change aangemaakt'),
(4, 1, 'approved', 'Nieuw', 'Goedgekeurd', 'Goedgekeurd - printer is al besteld');
