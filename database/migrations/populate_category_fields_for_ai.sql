-- ============================================================================
-- Category Fields Population for AI RAG System
-- ============================================================================
-- This migration populates dynamic fields for all ticket categories to improve
-- AI training data quality. Complete, structured data enables better semantic
-- search and more accurate AI suggestions.
--
-- Requirements: 2.1, 11.1
-- ============================================================================

-- First, clear any existing example fields from the initial migration
-- (Keep this safe - only removes fields without values)
DELETE FROM category_fields 
WHERE field_id NOT IN (
    SELECT DISTINCT field_id FROM ticket_field_values
);

-- ============================================================================
-- HARDWARE CATEGORY FIELDS (Category ID: 1)
-- ============================================================================
-- Requirements: 2.1
-- Fields: Merk, Model, Serienummer, Locatie, Afdeling

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
-- Hardware Type (Required)
(1, 'hardware_type', 'Type Hardware', 'select', 
 '["Laptop", "Desktop Computer", "Beeldscherm", "Toetsenbord", "Muis", "Printer", "Scanner", "Docking Station", "Webcam", "Headset", "Overig"]', 
 1, 1, NULL, 'Selecteer het type hardware waar het probleem mee is'),

-- Brand (Merk) with common options
(1, 'hardware_brand', 'Merk', 'select', 
 '["Dell", "HP", "Lenovo", "Apple", "Microsoft", "Asus", "Acer", "Samsung", "LG", "Canon", "Epson", "Brother", "Logitech", "Overig"]', 
 1, 2, NULL, 'Selecteer het merk van de hardware'),

-- Model
(1, 'hardware_model', 'Model', 'text', NULL, 
 0, 3, 'Bijv. Latitude 5520, EliteBook 840', 'Vul het model in indien bekend (te vinden op sticker of in systeem)'),

-- Serial Number (Serienummer)
(1, 'serial_number', 'Serienummer', 'text', NULL, 
 0, 4, 'Bijv. ABC123XYZ456', 'Serienummer te vinden op sticker op apparaat of in BIOS'),

-- Location (Locatie)
(1, 'hardware_location', 'Locatie', 'select', 
 '["Kantoor Hengelo", "Kantoor Enschede", "Thuiswerkplek", "Vestiging Almelo", "Magazijn", "Showroom", "Overig"]', 
 1, 5, NULL, 'Waar bevindt de hardware zich?'),

-- Department (Afdeling)
(1, 'hardware_department', 'Afdeling', 'select', 
 '["ICT", "Sales", "Inkoop", "Facilitair", "Directie", "HR", "Financiën", "Logistiek", "Marketing", "Overig"]', 
 1, 6, NULL, 'Voor welke afdeling is deze hardware?'),

-- Asset Tag (Optional - for inventory tracking)
(1, 'asset_tag', 'Asset Tag', 'text', NULL, 
 0, 7, 'Bijv. KK-2024-001', 'Interne asset tag indien aanwezig'),

-- Purchase Date (Optional - helps with warranty tracking)
(1, 'purchase_date', 'Aankoopdatum', 'date', NULL, 
 0, 8, NULL, 'Aankoopdatum indien bekend (voor garantie)'),

-- Problem Category
(1, 'hardware_problem_type', 'Type Probleem', 'select', 
 '["Start niet op", "Prestatieprobleem", "Schermprobleem", "Geluidsproble em", "Toetsenbord/Muis werkt niet", "Printprobleem", "Verbindingsprobleem", "Fysieke schade", "Overig"]', 
 1, 9, NULL, 'Wat is het hoofdprobleem?');

-- ============================================================================
-- SOFTWARE CATEGORY FIELDS (Category ID: 2)
-- ============================================================================
-- Requirements: 2.1
-- Fields: Applicatie naam, Versie, Licentie type, Installatie locatie

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
-- Application Name (Applicatie naam)
(2, 'software_name', 'Applicatie Naam', 'select', 
 '["Microsoft Office", "Microsoft 365", "Microsoft Teams", "Outlook", "Excel", "Word", "PowerPoint", "Adobe Acrobat", "Adobe Photoshop", "Google Chrome", "Mozilla Firefox", "Zoom", "AutoCAD", "SAP", "ERP Systeem", "CRM Systeem", "Antivirus Software", "VPN Client", "Overig"]', 
 1, 1, NULL, 'Selecteer de software applicatie'),

-- Custom Software Name (if "Overig" selected)
(2, 'software_name_custom', 'Andere Applicatie', 'text', NULL, 
 0, 2, 'Vul applicatie naam in', 'Alleen invullen als "Overig" geselecteerd'),

-- Version (Versie)
(2, 'software_version', 'Versie', 'text', NULL, 
 0, 3, 'Bijv. 2021, 365, 11.0', 'Versie van de software indien bekend'),

-- License Type (Licentie type)
(2, 'license_type', 'Licentie Type', 'select', 
 '["Bedrijfslicentie", "Gebruikerslicentie", "Proefversie", "Gratis/Open Source", "Weet ik niet"]', 
 0, 4, NULL, 'Type licentie voor deze software'),

-- Installation Location (Installatie locatie)
(2, 'installation_location', 'Installatie Locatie', 'select', 
 '["Lokale Computer", "Netwerkschijf", "Cloud/Online", "Server", "Weet ik niet"]', 
 1, 5, NULL, 'Waar is de software geïnstalleerd?'),

-- Problem Type
(2, 'software_problem_type', 'Type Probleem', 'select', 
 '["Installatie mislukt", "Start niet op", "Crasht regelmatig", "Foutmelding", "Licentieprobleem", "Update probleem", "Prestatieprobleem", "Functionaliteit werkt niet", "Compatibiliteitsprobleem", "Overig"]', 
 1, 6, NULL, 'Wat is het hoofdprobleem?'),

-- Error Message (if applicable)
(2, 'error_message', 'Foutmelding', 'textarea', NULL, 
 0, 7, 'Kopieer de exacte foutmelding hier', 'Indien er een foutmelding verschijnt, kopieer deze hier'),

-- Operating System
(2, 'operating_system', 'Besturingssysteem', 'select', 
 '["Windows 10", "Windows 11", "macOS", "Linux", "Weet ik niet"]', 
 0, 8, NULL, 'Op welk besturingssysteem draait de software?');

-- ============================================================================
-- NETWORK CATEGORY FIELDS (Category ID: 3)
-- ============================================================================
-- Requirements: 2.1
-- Fields: Switch/Router, Poort nummer, VLAN, IP adres

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
-- Network Problem Type
(3, 'network_problem_type', 'Type Probleem', 'select', 
 '["Geen verbinding", "Trage verbinding", "Intermitterende verbinding", "VPN probleem", "WiFi probleem", "Kan niet inloggen", "Toegang geweigerd", "DNS probleem", "Overig"]', 
 1, 1, NULL, 'Wat voor soort netwerkprobleem ervaart u?'),

-- Connection Type
(3, 'connection_type', 'Verbindingstype', 'select', 
 '["Bekabeld (LAN)", "Draadloos (WiFi)", "VPN", "Mobiel Hotspot", "Weet ik niet"]', 
 1, 2, NULL, 'Hoe probeert u verbinding te maken?'),

-- Location
(3, 'network_location', 'Locatie', 'select', 
 '["Kantoor Hengelo", "Kantoor Enschede", "Thuiswerkplek", "Vestiging Almelo", "Onderweg", "Overig"]', 
 1, 3, NULL, 'Waar bevindt u zich?'),

-- Switch/Router (for on-site issues)
(3, 'switch_router', 'Switch/Router', 'text', NULL, 
 0, 4, 'Bijv. SW-01, Router-Hengelo-2', 'Indien bekend, welke switch of router (voor kantoorlocaties)'),

-- Port Number (Poort nummer)
(3, 'port_number', 'Poort Nummer', 'text', NULL, 
 0, 5, 'Bijv. 24, Poort 12', 'Netwerk poort nummer indien bekend'),

-- VLAN
(3, 'vlan', 'VLAN', 'text', NULL, 
 0, 6, 'Bijv. VLAN 10, VLAN 100', 'VLAN nummer indien bekend'),

-- IP Address (IP adres)
(3, 'ip_address', 'IP Adres', 'text', NULL, 
 0, 7, 'Bijv. 192.168.1.100', 'IP adres van uw computer (te vinden via ipconfig/ifconfig)'),

-- MAC Address (for WiFi issues)
(3, 'mac_address', 'MAC Adres', 'text', NULL, 
 0, 8, 'Bijv. 00:1A:2B:3C:4D:5E', 'MAC adres indien bekend (voor WiFi problemen)'),

-- WiFi Network Name (for WiFi issues)
(3, 'wifi_network', 'WiFi Netwerk', 'select', 
 '["KK-Office", "KK-Guest", "KK-Secure", "Thuisnetwerk", "Overig"]', 
 0, 9, NULL, 'Welk WiFi netwerk probeert u te gebruiken?'),

-- Affected Services
(3, 'affected_services', 'Getroffen Diensten', 'checkbox', 
 '["Internet", "E-mail", "Gedeelde mappen", "Printer", "Intranet", "Externe applicaties", "Alles"]', 
 0, 10, NULL, 'Welke diensten zijn niet bereikbaar?');

-- ============================================================================
-- ACCOUNT CATEGORY FIELDS (Category ID: 4)
-- ============================================================================
-- Requirements: 2.1
-- Fields: Username, Email, Afdeling, Toegangsniveau, Systeem

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
-- Account Request Type
(4, 'account_request_type', 'Type Aanvraag', 'select', 
 '["Nieuw account", "Wachtwoord reset", "Toegang wijzigen", "Account deactiveren", "Account reactiveren", "Rechten aanpassen", "Overig"]', 
 1, 1, NULL, 'Wat wilt u met het account doen?'),

-- Account Type
(4, 'account_type', 'Type Account', 'select', 
 '["Windows/Netwerk Account", "E-mail Account", "VPN Toegang", "Applicatie Account", "Database Toegang", "Admin Account", "Gedeelde Account", "Overig"]', 
 1, 2, NULL, 'Voor welk type account is dit?'),

-- Username
(4, 'username', 'Gebruikersnaam', 'text', NULL, 
 1, 3, 'Bijv. j.jansen', 'Gebruikersnaam voor het account'),

-- Email
(4, 'email_address', 'E-mailadres', 'email', NULL, 
 1, 4, 'gebruiker@kruit-en-kramer.nl', 'E-mailadres van de gebruiker'),

-- Full Name (for new accounts)
(4, 'full_name', 'Volledige Naam', 'text', NULL, 
 0, 5, 'Voornaam Achternaam', 'Volledige naam van de gebruiker (voor nieuwe accounts)'),

-- Department (Afdeling)
(4, 'account_department', 'Afdeling', 'select', 
 '["ICT", "Sales", "Inkoop", "Facilitair", "Directie", "HR", "Financiën", "Logistiek", "Marketing", "Overig"]', 
 1, 6, NULL, 'Bij welke afdeling hoort deze gebruiker?'),

-- Access Level (Toegangsniveau)
(4, 'access_level', 'Toegangsniveau', 'select', 
 '["Standaard Gebruiker", "Power User", "Afdeling Administrator", "Systeem Administrator", "Alleen Lezen", "Lezen/Schrijven", "Volledige Controle"]', 
 1, 7, NULL, 'Welk toegangsniveau is vereist?'),

-- System (Systeem)
(4, 'target_system', 'Systeem', 'checkbox', 
 '["Windows Netwerk", "E-mail", "VPN", "ERP Systeem", "CRM Systeem", "Fileserver", "Database", "Intranet", "Externe Applicatie"]', 
 1, 8, NULL, 'Voor welke systemen is toegang nodig?'),

-- External Application Name (if applicable)
(4, 'external_app_name', 'Externe Applicatie Naam', 'text', NULL, 
 0, 9, 'Naam van de applicatie', 'Indien externe applicatie, vul de naam in'),

-- Manager Approval
(4, 'manager_name', 'Manager/Leidinggevende', 'text', NULL, 
 0, 10, 'Naam van leidinggevende', 'Naam van de leidinggevende die deze aanvraag goedkeurt'),

-- Start Date (for new accounts)
(4, 'start_date', 'Startdatum', 'date', NULL, 
 0, 11, NULL, 'Wanneer moet het account actief zijn?'),

-- End Date (for temporary accounts)
(4, 'end_date', 'Einddatum', 'date', NULL, 
 0, 12, NULL, 'Einddatum voor tijdelijke accounts (optioneel)');

-- ============================================================================
-- EMAIL CATEGORY FIELDS (Category ID: 5)
-- ============================================================================

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
-- Email Problem Type
(5, 'email_problem_type', 'Type Probleem', 'select', 
 '["Kan geen e-mail verzenden", "Kan geen e-mail ontvangen", "E-mail verdwijnt", "Kan niet inloggen", "Synchronisatie probleem", "Spam/Phishing", "Mailbox vol", "Configuratie probleem", "Overig"]', 
 1, 1, NULL, 'Wat is het e-mail probleem?'),

-- Email Client
(5, 'email_client', 'E-mail Programma', 'select', 
 '["Outlook Desktop", "Outlook Web", "Thunderbird", "Apple Mail", "Gmail App", "Mobiele E-mail", "Overig"]', 
 1, 2, NULL, 'Welk e-mail programma gebruikt u?'),

-- Device Type
(5, 'email_device', 'Apparaat', 'select', 
 '["Windows Computer", "Mac Computer", "iPhone", "iPad", "Android Telefoon", "Android Tablet", "Overig"]', 
 0, 3, NULL, 'Op welk apparaat heeft u het probleem?'),

-- Email Address
(5, 'email_address_affected', 'E-mailadres', 'email', NULL, 
 1, 4, 'gebruiker@kruit-en-kramer.nl', 'Welk e-mailadres heeft het probleem?'),

-- Error Message
(5, 'email_error_message', 'Foutmelding', 'textarea', NULL, 
 0, 5, 'Kopieer de exacte foutmelding', 'Indien er een foutmelding verschijnt, kopieer deze hier'),

-- Affects All Email or Specific
(5, 'email_scope', 'Omvang', 'radio', 
 '["Alle e-mails", "Specifieke afzender/ontvanger", "Specifieke e-mail"]', 
 0, 6, NULL, 'Betreft dit alle e-mails of specifieke berichten?');

-- ============================================================================
-- SECURITY CATEGORY FIELDS (Category ID: 6)
-- ============================================================================

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
-- Security Issue Type
(6, 'security_issue_type', 'Type Beveiligingsprobleem', 'select', 
 '["Virus/Malware detectie", "Phishing e-mail", "Verdachte activiteit", "Data lek", "Ongeautoriseerde toegang", "Wachtwoord gecompromitteerd", "Verloren/gestolen apparaat", "Overig"]', 
 1, 1, NULL, 'Wat voor beveiligingsprobleem is dit?'),

-- Urgency Level
(6, 'security_urgency', 'Urgentie', 'radio', 
 '["Kritiek - Actieve aanval", "Hoog - Potentieel gevaar", "Gemiddeld - Verdacht", "Laag - Preventief"]', 
 1, 2, NULL, 'Hoe urgent is dit probleem?'),

-- Affected System
(6, 'security_affected_system', 'Getroffen Systeem', 'text', NULL, 
 1, 3, 'Bijv. computer naam, server naam', 'Welk systeem is getroffen?'),

-- Description of Threat
(6, 'security_threat_description', 'Beschrijving Dreiging', 'textarea', NULL, 
 1, 4, 'Beschrijf wat u heeft gezien of ervaren', 'Geef een gedetailleerde beschrijving van het beveiligingsprobleem'),

-- Has Data Been Compromised
(6, 'data_compromised', 'Data Gecompromitteerd?', 'radio', 
 '["Ja", "Mogelijk", "Nee", "Weet ik niet"]', 
 1, 5, NULL, 'Is er mogelijk gevoelige data blootgesteld?'),

-- Action Taken
(6, 'security_action_taken', 'Ondernomen Actie', 'checkbox', 
 '["Computer afgesloten", "Netwerk verbinding verbroken", "Wachtwoord gewijzigd", "Antivirus scan uitgevoerd", "Nog geen actie ondernomen"]', 
 0, 6, NULL, 'Welke acties heeft u al ondernomen?');

-- ============================================================================
-- OTHER CATEGORY FIELDS (Category ID: 7)
-- ============================================================================

INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, field_order, placeholder, help_text) VALUES
-- Request Type
(7, 'other_request_type', 'Type Aanvraag', 'select', 
 '["Advies/Consultatie", "Training/Instructie", "Documentatie aanvraag", "Nieuwe functionaliteit", "Verbetersuggestie", "Algemene vraag", "Overig"]', 
 1, 1, NULL, 'Wat voor soort aanvraag is dit?'),

-- Priority Indication
(7, 'other_priority', 'Prioriteit Indicatie', 'radio', 
 '["Urgent", "Normaal", "Laag", "Wanneer tijd is"]', 
 0, 2, NULL, 'Hoe urgent is deze aanvraag?'),

-- Preferred Contact Method
(7, 'preferred_contact', 'Voorkeur Contact', 'radio', 
 '["E-mail", "Telefoon", "Teams", "Persoonlijk"]', 
 0, 3, NULL, 'Hoe wilt u het liefst gecontacteerd worden?');

-- ============================================================================
-- VERIFICATION QUERY
-- ============================================================================
-- Run this to verify all fields were created successfully:
-- 
-- SELECT c.name as category, COUNT(cf.field_id) as field_count
-- FROM categories c
-- LEFT JOIN category_fields cf ON c.category_id = cf.category_id
-- GROUP BY c.category_id, c.name
-- ORDER BY c.category_id;
--
-- Expected results:
-- Hardware: 9 fields
-- Software: 8 fields
-- Network: 10 fields
-- Account: 12 fields
-- Email: 6 fields
-- Security: 6 fields
-- Other: 3 fields
-- ============================================================================
