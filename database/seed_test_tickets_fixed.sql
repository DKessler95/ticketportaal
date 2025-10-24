-- Seed Test Tickets for RAG AI Training
-- Creates 15 realistic ICT support tickets with resolutions
-- Fixed: Uses category_id and assigned_agent_id

-- Category IDs: 1=Hardware, 2=Software, 3=Network, 4=Account
-- User IDs: 1=admin, 3=test1, 4=user, 5=agent

-- Ticket 1: Printer Paper Jam
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at) 
VALUES ('T-2024-001', 'Printer geeft paper jam error', 
'De HP LaserJet Pro printer op kantoor Hengelo geeft constant een paper jam error. Ik heb al geprobeerd het papier te verwijderen maar de fout blijft.', 
1, 'medium', 'closed', 4, 5,
'Oplossing: Lade 2 had vastgelopen papier achter de roller. Papier verwijderd en printer herstart. Werkt nu weer normaal.',
'2024-10-15 09:30:00', '2024-10-15 11:45:00');

-- Ticket 2: Laptop start niet op
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-002', 'Laptop start niet meer op na Windows update',
'Mijn Dell Latitude 5520 geeft geen beeld meer na de laatste Windows update. Power LED brandt wel maar scherm blijft zwart.',
1, 'high', 'closed', 4, 5,
'Oplossing: BIOS reset uitgevoerd via F2 menu. Windows update opnieuw geïnstalleerd. Laptop start nu normaal op.',
'2024-10-16 08:15:00', '2024-10-16 14:30:00');

-- Ticket 3: Outlook kan geen emails verzenden
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-003', 'Outlook kan geen emails verzenden',
'Sinds vanmorgen kan ik geen emails meer verzenden via Outlook. Ontvangen werkt wel. Krijg foutmelding: "Kan geen verbinding maken met uitgaande mailserver".',
2, 'high', 'closed', 4, 5,
'Oplossing: SMTP poort was gewijzigd naar 25 in plaats van 587. Poort gecorrigeerd in Outlook instellingen. Emails worden nu weer verzonden.',
'2024-10-17 10:00:00', '2024-10-17 11:15:00');

-- Ticket 4: Netwerkschijf niet bereikbaar
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-004', 'Kan niet bij netwerkschijf Z:',
'De gedeelde netwerkschijf Z: is niet meer bereikbaar. Krijg melding "Netwerkpad niet gevonden". Collega\'s hebben wel toegang.',
3, 'medium', 'closed', 4, 5,
'Oplossing: Netwerkdrive mapping was verlopen. Opnieuw gemapped met \\\\server\\share en credentials opgeslagen. Toegang hersteld.',
'2024-10-18 13:20:00', '2024-10-18 14:00:00');

-- Ticket 5: Excel crasht bij openen groot bestand
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-005', 'Excel crasht bij openen van groot bestand',
'Excel 2021 crasht telkens als ik een groot Excel bestand (15MB) probeer te openen. Krijg melding "Excel reageert niet".',
2, 'medium', 'closed', 4, 5,
'Oplossing: Excel in Safe Mode gestart en Add-ins uitgeschakeld. Problematische add-in verwijderd. Excel werkt nu stabiel.',
'2024-10-19 09:45:00', '2024-10-19 10:30:00');

-- Ticket 6: VPN verbinding valt steeds weg
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-006', 'VPN verbinding valt om de 10 minuten weg',
'Wanneer ik thuiswerk valt mijn VPN verbinding steeds weg na ongeveer 10 minuten. Moet dan opnieuw inloggen.',
3, 'high', 'closed', 4, 5,
'Oplossing: VPN client timeout instellingen aangepast van 600 naar 3600 seconden. Keep-alive packets ingeschakeld. Verbinding blijft nu stabiel.',
'2024-10-20 11:00:00', '2024-10-20 12:30:00');

-- Ticket 7: Wachtwoord reset niet ontvangen
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-007', 'Wachtwoord reset email niet ontvangen',
'Ik heb een wachtwoord reset aangevraagd maar ontvang geen email. Heb al in spam gekeken.',
4, 'medium', 'closed', 4, 5,
'Oplossing: Email adres in systeem was verouderd. Nieuw email adres toegevoegd en wachtwoord handmatig gereset. User kan nu inloggen.',
'2024-10-21 14:15:00', '2024-10-21 15:00:00');

-- Ticket 8: Teams audio werkt niet
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-008', 'Geen geluid in Microsoft Teams meetings',
'Tijdens Teams meetings hoor ik niemand en anderen horen mij ook niet. Andere applicaties hebben wel geluid.',
2, 'high', 'closed', 4, 5,
'Oplossing: Teams had verkeerde audio device geselecteerd. Juiste microfoon en speakers ingesteld in Teams instellingen. Audio werkt nu.',
'2024-10-22 09:00:00', '2024-10-22 09:45:00');

-- Ticket 9: Monitor geen signaal
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-009', 'Tweede monitor geeft "Geen signaal"',
'Mijn tweede Dell monitor geeft plotseling "Geen signaal". Eerste monitor werkt wel. HDMI kabel zit goed vast.',
1, 'medium', 'closed', 4, 5,
'Oplossing: HDMI poort op laptop was defect. Monitor aangesloten op DisplayPort met adapter. Beide monitors werken nu.',
'2024-10-22 10:30:00', '2024-10-22 11:15:00');

-- Ticket 10: Kan niet printen naar nieuwe printer
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-010', 'Nieuwe Canon printer niet gevonden',
'De nieuwe Canon printer op de 2e verdieping staat niet in mijn printers lijst. Andere collega\'s kunnen wel printen.',
1, 'low', 'closed', 4, 5,
'Oplossing: Printer driver geïnstalleerd en printer toegevoegd via IP adres 192.168.1.50. Print test succesvol.',
'2024-10-22 13:00:00', '2024-10-22 13:45:00');

-- Ticket 11: Outlook agenda synchroniseert niet
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-011', 'Outlook agenda sync niet met telefoon',
'Mijn Outlook agenda op de telefoon toont geen nieuwe afspraken. Op de computer zie ik ze wel.',
2, 'medium', 'closed', 4, 5,
'Oplossing: Outlook app cache gewist op telefoon. Account opnieuw toegevoegd. Agenda synchroniseert nu correct.',
'2024-10-22 14:30:00', '2024-10-22 15:15:00');

-- Ticket 12: Toetsenbord werkt niet
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-012', 'Draadloos toetsenbord reageert niet meer',
'Mijn Logitech draadloos toetsenbord werkt plotseling niet meer. LED lampje knippert niet.',
1, 'medium', 'closed', 4, 5,
'Oplossing: Batterijen vervangen. USB receiver opnieuw aangesloten. Toetsenbord gekoppeld via Logitech software. Werkt nu weer.',
'2024-10-23 08:00:00', '2024-10-23 08:30:00');

-- Ticket 13: SharePoint bestand kan niet openen
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-013', 'SharePoint Word document geeft foutmelding',
'Wanneer ik een Word document op SharePoint probeer te openen krijg ik: "Dit bestand kan niet worden geopend omdat er problemen zijn met de inhoud".',
2, 'medium', 'closed', 4, 5,
'Oplossing: Document was corrupt. Eerdere versie hersteld vanuit SharePoint versiegeschiedenis. Document opent nu normaal.',
'2024-10-23 10:00:00', '2024-10-23 10:45:00');

-- Ticket 14: Laptop overheet en is traag
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-014', 'Laptop wordt erg heet en is traag',
'Mijn HP laptop wordt heel heet aan de onderkant en is erg traag geworden. Ventilator maakt veel lawaai.',
1, 'medium', 'closed', 4, 5,
'Oplossing: Laptop ventilator schoongemaakt (veel stof). Thermische pasta vervangen. Laptop draait nu koeler en sneller.',
'2024-10-23 11:30:00', '2024-10-23 13:00:00');

-- Ticket 15: Printer offline na netwerkwijziging
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, user_id, assigned_agent_id, resolution, created_at, updated_at)
VALUES ('T-2024-015', 'HP printer staat op offline na netwerkwijziging',
'Na de netwerkwijziging vorige week staat de HP LaserJet Pro printer op offline. Kan niet meer printen.',
1, 'high', 'closed', 4, 5,
'Oplossing: Printer IP adres was gewijzigd. Nieuwe IP adres (192.168.1.45) toegevoegd in printer instellingen. Netwerkkabel opnieuw aangesloten. Printer online.',
'2024-10-23 14:00:00', '2024-10-23 15:30:00');
