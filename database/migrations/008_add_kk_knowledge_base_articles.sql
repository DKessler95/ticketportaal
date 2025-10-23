-- Add K&K Specific Knowledge Base Articles
-- These articles provide company-specific information for the AI Assistant

-- First, get or create a default category for K&K articles
INSERT IGNORE INTO categories (name, description, is_active, created_at) 
VALUES ('K&K Informatie', 'Kruit & Kramer bedrijfsinformatie en procedures', 1, NOW());

SET @kk_category_id = (SELECT category_id FROM categories WHERE name = 'K&K Informatie' LIMIT 1);

-- Article 1: Kruit & Kramer Bedrijfsinformatie
INSERT INTO knowledge_base (title, content, tags, category_id, is_published, author_id, created_at, updated_at) VALUES
('Kruit & Kramer - Bedrijfsinformatie', 
'# Over Kruit & Kramer

Kruit & Kramer is een toonaangevend bedrijf in de ICT sector met meerdere vestigingen in Nederland.

## Vestigingen
- **Hoofdkantoor Hengelo**: Industrieweg 12, 7553 AB Hengelo
- **Kantoor Enschede**: Technologiepark 24, 7522 NB Enschede
- **Magazijn/Warehouse**: Logistiekweg 8, 7554 TC Hengelo

## Organisatie
- **Directie**: Verantwoordelijk voor strategische beslissingen
- **ICT Afdeling**: Beheer van alle IT systemen en support
- **Sales**: Verkoop en klantrelaties
- **Administratie**: Financiën en HR
- **Logistiek**: Magazijn en distributie

## Werknemers
Kruit & Kramer heeft ongeveer 50-75 medewerkers verdeeld over de verschillende afdelingen en locaties.

## Kernactiviteiten
- ICT consultancy en advies
- Hardware en software levering
- Managed services
- Cloud oplossingen
- Netwerk infrastructuur', 
'bedrijf,organisatie,locaties,kruit en kramer', 
@kk_category_id, 
1, 
1, 
NOW(), 
NOW());

-- Article 2: ICT Systemen bij K&K
INSERT INTO knowledge_base (title, content, tags, category_id, is_published, author_id, created_at, updated_at) VALUES
('ICT Systemen bij Kruit & Kramer',
'# ICT Systemen en Applicaties

## Primaire Systemen

### Ticketportaal
- **Functie**: Centrale helpdesk en ticket management
- **Toegang**: Via https://ticketportaal.kruitenkramer.nl
- **Gebruikers**: Alle medewerkers kunnen tickets aanmaken
- **Support**: ICT afdeling behandelt alle tickets

### Ecoro ERP Systeem
- **Functie**: Enterprise Resource Planning
- **Modules**: 
  - Voorraad beheer
  - Inkoop en verkoop
  - Financiële administratie
  - CRM functionaliteit
- **Toegang**: Alleen voor geautoriseerde medewerkers
- **Support**: Ecoro helpdesk + interne ICT

### Email Systeem
- **Provider**: Microsoft 365 / Exchange Online
- **Domeinen**: @kruitenkramer.nl
- **Toegang**: Via Outlook desktop, web, en mobiel
- **Features**: Email, kalender, Teams, OneDrive

### File Server
- **Locatie**: On-premise server in Hengelo
- **Toegang**: Via netwerk drives (H:, G:, S:)
- **Backup**: Dagelijkse backups naar cloud
- **Structuur**: Per afdeling georganiseerd

### VPN
- **Functie**: Externe toegang tot bedrijfsnetwerk
- **Software**: Cisco AnyConnect
- **Toegang**: Alle medewerkers met laptop
- **Support**: ICT afdeling voor configuratie

## Secundaire Systemen

### Telefonie
- **Systeem**: VoIP telefonie
- **Provider**: KPN
- **Features**: Doorschakelen, voicemail, conferencing

### Monitoring
- **Tools**: Nagios, PRTG
- **Functie**: Server en netwerk monitoring
- **Toegang**: Alleen ICT afdeling

### Backup Systeem
- **Software**: Veeam Backup & Replication
- **Locatie**: On-premise + cloud backup
- **Schema**: Dagelijks incrementeel, wekelijks full backup',
'systemen,applicaties,software,ecoro,email,vpn',
@kk_category_id,
1,
1,
NOW(),
NOW());

-- Article 3: Hardware Standaarden
INSERT INTO knowledge_base (title, content, tags, category_id, is_published, author_id, created_at, updated_at) VALUES
('Hardware Standaarden K&K',
'# Hardware Standaarden en Specificaties

## Laptops

### Dell Latitude Serie (Standaard)
- **Model**: Dell Latitude 5420/5520
- **Processor**: Intel Core i5 11e generatie
- **RAM**: 16GB DDR4
- **Opslag**: 512GB NVMe SSD
- **Scherm**: 14" of 15" Full HD
- **OS**: Windows 11 Pro
- **Garantie**: 3 jaar Next Business Day

### HP EliteBook (Management)
- **Model**: HP EliteBook 840 G8
- **Processor**: Intel Core i7 11e generatie
- **RAM**: 32GB DDR4
- **Opslag**: 1TB NVMe SSD
- **Scherm**: 14" Full HD
- **OS**: Windows 11 Pro

## Desktops

### Dell OptiPlex (Kantoor)
- **Model**: Dell OptiPlex 7090
- **Processor**: Intel Core i5
- **RAM**: 16GB DDR4
- **Opslag**: 512GB SSD
- **OS**: Windows 11 Pro

## Monitoren
- **Standaard**: Dell P2422H 24" Full HD
- **Dual setup**: Standaard voor alle kantoormedewerkers
- **Aansluitingen**: DisplayPort, HDMI, USB-C

## Printers
- **Kantoor**: HP LaserJet Enterprise MFP
- **Locaties**: Per verdieping 1 multifunctionele printer
- **Features**: Print, scan, copy, fax

## Netwerk Apparatuur
- **Switches**: Cisco Catalyst serie
- **Access Points**: Ubiquiti UniFi
- **Routers**: Cisco ISR serie
- **Firewall**: Fortinet FortiGate

## Mobiele Apparaten
- **Smartphones**: iPhone (management) of Samsung Galaxy (standaard)
- **Tablets**: iPad voor specifieke functies
- **MDM**: Microsoft Intune voor device management',
'hardware,laptops,desktops,dell,hp,cisco,printers',
@kk_category_id,
1,
1,
NOW(),
NOW());

-- Article 4: Netwerk en Toegang
INSERT INTO knowledge_base (title, content, tags, category_id, is_published, author_id, created_at, updated_at) VALUES
('Netwerk en Toegang Procedures',
'# Netwerk Infrastructuur en Toegang

## Netwerk Structuur

### Kantoor Netwerk
- **VLAN 10**: Management (servers, netwerk apparatuur)
- **VLAN 20**: Kantoor (werkplekken)
- **VLAN 30**: Gasten (WiFi voor bezoekers)
- **VLAN 40**: VoIP (telefonie)
- **VLAN 50**: Productie (magazijn systemen)

### WiFi Netwerken
- **KK-Corporate**: Voor medewerkers (WPA2 Enterprise)
- **KK-Guest**: Voor bezoekers (captive portal)
- **KK-IoT**: Voor IoT apparaten (gescheiden netwerk)

## Toegangsprocedures

### Nieuwe Medewerker
1. HR meldt nieuwe medewerker aan bij ICT
2. ICT creëert Active Directory account
3. Email account wordt aangemaakt
4. VPN toegang wordt geconfigureerd
5. Laptop wordt voorbereid en uitgegeven
6. Toegang tot benodigde systemen (Ecoro, file shares)
7. Instructie en onboarding

### Wachtwoord Beleid
- **Minimale lengte**: 12 karakters
- **Complexiteit**: Hoofdletters, kleine letters, cijfers, speciale tekens
- **Verloop**: Elke 90 dagen
- **Geschiedenis**: Laatste 5 wachtwoorden niet hergebruiken
- **Lockout**: Na 5 mislukte pogingen

### Wachtwoord Reset
1. Gebruiker gaat naar ticketportaal
2. Maakt ticket aan met categorie "Account"
3. ICT agent verifieert identiteit
4. Wachtwoord wordt gereset
5. Tijdelijk wachtwoord wordt verstrekt
6. Gebruiker moet bij eerste login nieuw wachtwoord instellen

### VPN Toegang
1. Cisco AnyConnect client installeren
2. Server adres: vpn.kruitenkramer.nl
3. Inloggen met AD credentials
4. Duo MFA verificatie (voor externe toegang)
5. Verbinding wordt gemaakt

### Gedeelde Mappen
- **H: drive**: Persoonlijke home directory
- **G: drive**: Gedeelde afdeling mappen
- **S: drive**: Software en installaties
- **Toegang**: Via Active Directory groepen

## Beveiliging

### Firewall Regels
- Standaard deny all, explicit allow
- Uitgaand verkeer via proxy
- Inkomend verkeer alleen voor specifieke services
- VPN required voor externe toegang

### Antivirus
- **Software**: Microsoft Defender for Endpoint
- **Updates**: Automatisch dagelijks
- **Scans**: Wekelijks full scan
- **Quarantine**: Automatisch bij detectie

### Backup en Recovery
- **Frequentie**: Dagelijks incrementeel, wekelijks full
- **Retentie**: 30 dagen on-premise, 1 jaar cloud
- **Test**: Maandelijkse restore test
- **RTO**: 4 uur voor kritieke systemen
- **RPO**: 24 uur',
'netwerk,toegang,vpn,wifi,beveiliging,wachtwoord',
@kk_category_id,
1,
1,
NOW(),
NOW());

-- Article 5: Veelgestelde Vragen
INSERT INTO knowledge_base (title, content, tags, category_id, is_published, author_id, created_at, updated_at) VALUES
('Veelgestelde Vragen (FAQ)',
'# Veelgestelde Vragen

## Account en Toegang

### Hoe reset ik mijn wachtwoord?
1. Ga naar het ticketportaal
2. Maak een nieuw ticket aan
3. Selecteer categorie "Account"
4. Beschrijf dat je wachtwoord gereset moet worden
5. ICT zal binnen 2 uur reageren

### Ik kan niet inloggen op mijn laptop
**Mogelijke oorzaken:**
- Verkeerd wachtwoord (let op Caps Lock)
- Account is vergrendeld na te veel pogingen
- Wachtwoord is verlopen
- Geen netwerkverbinding (kan niet verifiëren bij domain controller)

**Oplossing:**
- Probeer opnieuw met correct wachtwoord
- Wacht 30 minuten als account vergrendeld is
- Maak ticket aan voor wachtwoord reset
- Check netwerkverbinding (bekabeld of WiFi)

### Hoe krijg ik toegang tot een gedeelde map?
1. Maak ticket aan met categorie "Toegang"
2. Vermeld welke map je nodig hebt
3. Vermeld waarom je toegang nodig hebt
4. Laat goedkeuren door je manager
5. ICT zal toegang verlenen binnen 1 werkdag

## Hardware

### Mijn laptop is traag
**Mogelijke oorzaken:**
- Te veel programma''s open
- Onvoldoende geheugen (RAM)
- Volle harde schijf
- Malware of virus
- Verouderde hardware

**Oplossing:**
1. Sluit onnodige programma''s
2. Herstart je laptop
3. Check schijfruimte (minimaal 20% vrij)
4. Run antivirus scan
5. Als probleem blijft: maak ticket aan

### Mijn scherm werkt niet
**Checklist:**
- Is monitor aangezet?
- Is stroomkabel aangesloten?
- Is video kabel (HDMI/DisplayPort) goed aangesloten?
- Probeer andere video poort op laptop
- Probeer andere kabel
- Test monitor met andere laptop

### Printer print niet
**Checklist:**
- Is printer aangezet?
- Is er papier in de lade?
- Zijn er foutmeldingen op display?
- Is printer online in Windows?
- Probeer print queue te wissen
- Herstart printer
- Herinstalleer printer driver

## Software

### Hoe installeer ik nieuwe software?
1. Check of software al beschikbaar is op S: drive
2. Als niet beschikbaar: maak ticket aan
3. Vermeld welke software en waarom
4. ICT zal beoordelen en installeren
5. Licentie wordt toegewezen indien nodig

### Outlook werkt niet
**Mogelijke problemen:**
- Geen internetverbinding
- Outlook is offline
- Mailbox is vol
- Profiel is corrupt

**Oplossing:**
1. Check internetverbinding
2. Klik op "Send/Receive" tab, check "Work Offline"
3. Archiveer oude emails
4. Herstart Outlook
5. Als probleem blijft: maak ticket aan

### Teams verbinding is slecht
**Tips:**
- Gebruik bekabelde verbinding i.p.v. WiFi
- Sluit andere programma''s die bandwidth gebruiken
- Schakel camera uit als niet nodig
- Check internetsnelheid (minimaal 2 Mbps up/down)
- Gebruik headset i.p.v. laptop speakers/mic

## Ecoro

### Ik kan niet inloggen op Ecoro
**Checklist:**
- Gebruik je de juiste URL?
- Is je account actief? (check met manager)
- Is je wachtwoord correct?
- Probeer andere browser
- Clear browser cache en cookies
- Maak ticket aan als probleem blijft

### Ecoro is traag
**Mogelijke oorzaken:**
- Veel gebruikers tegelijk
- Grote rapporten worden gegenereerd
- Netwerk problemen
- Server onderhoud

**Oplossing:**
- Probeer later opnieuw
- Gebruik filters om rapporten kleiner te maken
- Check of er gepland onderhoud is
- Maak ticket aan als structureel probleem

## VPN

### VPN verbindt niet
**Checklist:**
- Is Cisco AnyConnect geïnstalleerd?
- Gebruik je vpn.kruitenkramer.nl als server?
- Is je internetverbinding actief?
- Zijn je credentials correct?
- Heb je MFA (Duo) goedgekeurd?
- Probeer andere netwerk (bijv. mobiele hotspot)

### VPN is traag
**Tips:**
- VPN voegt overhead toe, enige vertraging is normaal
- Gebruik VPN alleen voor bedrijfsapplicaties
- Sluit VPN als je het niet nodig hebt
- Check je internet snelheid
- Probeer andere VPN server (als beschikbaar)

## Algemeen

### Hoe maak ik een ticket aan?
1. Ga naar ticketportaal.kruitenkramer.nl
2. Log in met je AD credentials
3. Klik op "Nieuw Ticket"
4. Vul alle velden in (categorie, prioriteit, beschrijving)
5. Voeg screenshots toe indien relevant
6. Klik op "Aanmaken"

### Hoe urgent is mijn ticket?
**Prioriteiten:**
- **Laag**: Geen impact op werk, kan wachten
- **Normaal**: Enige impact, binnen 2 werkdagen
- **Hoog**: Significante impact, binnen 1 werkdag
- **Kritiek**: Kan niet werken, binnen 2 uur

### Kan ik de ICT afdeling bellen?
- **Ja**, voor kritieke problemen: 053-1234567
- **Bij voorkeur**: Maak ticket aan voor tracking
- **Bereikbaarheid**: Ma-Vr 08:00-17:00
- **Buiten kantooruren**: Alleen voor kritieke problemen',
'faq,veelgesteld,vragen,problemen,oplossingen',
@kk_category_id,
1,
1,
NOW(),
NOW());

-- Article 6: Nieuwe Medewerker Onboarding
INSERT INTO knowledge_base (title, content, tags, category_id, is_published, author_id, created_at, updated_at) VALUES
('Nieuwe Medewerker - ICT Onboarding',
'# ICT Onboarding voor Nieuwe Medewerkers

## Welkom bij Kruit & Kramer!

Deze handleiding helpt je op weg met alle ICT systemen en procedures.

## Eerste Werkdag

### Je ontvangt:
- Laptop (Dell Latitude of HP EliteBook)
- Laptop tas
- Muis en toetsenbord (optioneel)
- Monitor(en) voor op kantoor
- Headset voor Teams meetings
- Smartphone (indien van toepassing)

### Je credentials:
- **Username**: voornaam.achternaam
- **Email**: voornaam.achternaam@kruitenkramer.nl
- **Tijdelijk wachtwoord**: Ontvang je van ICT
- **Bij eerste login**: Moet je nieuw wachtwoord instellen

## Systemen Setup

### 1. Windows Login
- Start je laptop
- Druk op Ctrl+Alt+Delete
- Voer username en tijdelijk wachtwoord in
- Stel nieuw wachtwoord in (minimaal 12 karakters)

### 2. Email Configuratie
- Outlook opent automatisch
- Voer email adres in
- Voer wachtwoord in
- Email synchroniseert automatisch

### 3. VPN Installatie (voor thuiswerken)
- Open Software Center of S: drive
- Installeer Cisco AnyConnect
- Server: vpn.kruitenkramer.nl
- Test verbinding vanaf kantoor eerst

### 4. Teams Setup
- Teams is voorgeïnstalleerd
- Log in met je email
- Stel je status en profiel foto in
- Test audio en video

### 5. Ecoro Toegang (indien van toepassing)
- URL: https://ecoro.kruitenkramer.nl
- Login met AD credentials
- Volg Ecoro training (gepland door manager)

## Belangrijke Locaties

### Netwerk Drives
- **H: drive**: Je persoonlijke map (automatisch gemapped)
- **G: drive**: Gedeelde afdeling mappen
- **S: drive**: Software en installaties

### Printers
- Ga naar Instellingen > Printers
- Klik op "Printer toevoegen"
- Selecteer de printer voor jouw locatie
- Printers zijn genoemd naar locatie (bijv. HGL-2e-MFP)

### WiFi
- **Netwerk**: KK-Corporate
- **Authenticatie**: WPA2 Enterprise
- **Username**: Je AD username
- **Wachtwoord**: Je AD wachtwoord

## Belangrijke Procedures

### Wachtwoord Beleid
- Minimaal 12 karakters
- Hoofdletters, kleine letters, cijfers, speciale tekens
- Verloopt elke 90 dagen
- Je krijgt 14 dagen van tevoren waarschuwing

### Ticket Aanmaken
- Voor alle ICT vragen en problemen
- Ga naar ticketportaal.kruitenkramer.nl
- Beschrijf probleem zo gedetailleerd mogelijk
- Voeg screenshots toe indien relevant

### Thuiswerken
1. Verbind met VPN
2. Gebruik bedrijfslaptop (niet privé PC)
3. Zorg voor goede internetverbinding
4. Gebruik headset voor meetings

### Beveiliging
- Vergrendel je laptop als je wegloopt (Windows+L)
- Deel nooit je wachtwoord
- Klik niet op verdachte links in emails
- Meld security incidenten direct bij ICT

## Trainingen

### Verplichte Trainingen
- **Security Awareness**: Online training (1 uur)
- **GDPR/AVG**: Online training (30 min)
- **Ecoro Basis**: Indien van toepassing (2 uur)

### Optionele Trainingen
- **Microsoft 365**: Tips en tricks
- **Teams Advanced**: Geavanceerde features
- **Ecoro Advanced**: Voor power users

## Contactinformatie

### ICT Helpdesk
- **Email**: helpdesk@kruitenkramer.nl
- **Telefoon**: 053-1234567
- **Ticketportaal**: ticketportaal.kruitenkramer.nl
- **Bereikbaarheid**: Ma-Vr 08:00-17:00

### Belangrijke Personen
- **ICT Manager**: Jan de Vries (jan.devries@kruitenkramer.nl)
- **Systeembeheerder**: Peter Jansen (peter.jansen@kruitenkramer.nl)
- **Helpdesk**: helpdesk@kruitenkramer.nl

## Checklist Eerste Week

- [ ] Laptop ontvangen en ingelogd
- [ ] Email werkt
- [ ] Teams getest
- [ ] VPN geïnstalleerd en getest
- [ ] Netwerk drives toegankelijk
- [ ] Printer toegevoegd
- [ ] Wachtwoord gewijzigd
- [ ] Security awareness training voltooid
- [ ] Ecoro toegang (indien nodig)
- [ ] Vragen? Maak ticket aan!

## Tips voor Nieuwe Medewerkers

1. **Stel vragen**: Geen vraag is te simpel
2. **Maak notities**: Schrijf procedures op
3. **Test alles**: Probeer systemen uit op kantoor
4. **Backup je data**: Gebruik OneDrive of H: drive
5. **Wees proactief**: Meld problemen direct

Succes en welkom bij het team!',
'onboarding,nieuwe medewerker,setup,training',
@kk_category_id,
1,
1,
NOW(),
NOW());

-- Update existing articles to be more relevant
UPDATE knowledge_base 
SET tags = CONCAT(tags, ',kruit en kramer,k&k')
WHERE is_published = 1;
