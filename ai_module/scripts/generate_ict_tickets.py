"""
Generate 20 realistic ICT test tickets with comments and resolutions
"""
import mysql.connector
from datetime import datetime, timedelta
import random

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ticketportaal'
}

# 20 realistic ICT tickets across all categories
TICKETS = [
    # Hardware (category_id: 1)
    {
        'title': 'Laptop start niet meer op na Windows update',
        'description': 'Na de laatste Windows update blijft mijn laptop hangen op het opstartscherm. Ik zie alleen het Dell logo en verder gebeurt er niets.',
        'category_id': 1,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Laptop opgestart in safe mode en problematische update verwijderd. Daarna opnieuw opgestart en werkt weer normaal. Advies: wacht met updates tot deze getest zijn.',
        'comments': [
            'Heb je al geprobeerd om de laptop uit te zetten en opnieuw op te starten?',
            'Ja, meerdere keren geprobeerd maar blijft hangen',
            'Ik ga remote kijken via TeamViewer',
            'Probleem gevonden, bezig met oplossen',
            'Opgelost! Je kunt weer verder werken'
        ]
    },
    {
        'title': 'Printer geeft papierstoring maar er zit geen papier vast',
        'description': 'De HP printer op de 2e verdieping geeft constant een papierstoring melding, maar ik kan geen vastgelopen papier vinden.',
        'category_id': 1,
        'priority': 'medium',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Sensor in de printer was vervuild. Printer schoongemaakt en sensor gereinigd. Test print succesvol.',
        'comments': [
            'Welke printer precies? We hebben er 3 op die verdieping',
            'De grote HP bij de vergaderzaal',
            'Ik kom even kijken',
            'Sensor was vies, aan het schoonmaken',
            'Klaar, kun je het testen?',
            'Werkt perfect, bedankt!'
        ]
    },
    {
        'title': 'Muis werkt niet meer',
        'description': 'Mijn draadloze muis reageert niet meer. Batterijen zijn nieuw.',
        'category_id': 1,
        'priority': 'low',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'USB ontvanger was losgekomen. Opnieuw aangesloten en muis werkt weer.',
        'comments': [
            'Heb je de USB ontvanger gecheckt?',
            'Die zit er nog in',
            'Probeer hem in een andere USB poort',
            'Oh, hij zat los! Werkt nu weer'
        ]
    },
    {
        'title': 'Monitor heeft geen beeld meer',
        'description': 'Mijn tweede monitor geeft geen beeld meer. Het lampje brandt wel.',
        'category_id': 1,
        'priority': 'medium',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'HDMI kabel was defect. Vervangen door nieuwe kabel en monitor werkt weer.',
        'comments': [
            'Zie je een "No Signal" melding?',
            'Ja, dat staat er',
            'Ik breng een nieuwe kabel',
            'Nieuwe kabel werkt! Dankjewel'
        ]
    },
    
    # Software (category_id: 2)
    {
        'title': 'Excel crasht bij openen van grote bestanden',
        'description': 'Telkens als ik een Excel bestand groter dan 10MB probeer te openen, crasht het programma.',
        'category_id': 2,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Excel add-ins veroorzaakten het probleem. Add-ins uitgeschakeld en Excel opnieuw gestart. Werkt nu stabiel.',
        'comments': [
            'Welke Excel versie gebruik je?',
            'Office 365, laatste versie',
            'Krijg je een foutmelding?',
            'Nee, programma sluit gewoon',
            'Ik ga add-ins checken',
            'Probleem opgelost, Excel werkt weer'
        ]
    },
    {
        'title': 'Kan Adobe Reader niet installeren',
        'description': 'Installatie van Adobe Reader geeft foutmelding "Installation failed". Heb het al 3x geprobeerd.',
        'category_id': 2,
        'priority': 'medium',
        'ticket_type': 'service_request',
        'status': 'closed',
        'resolution': 'Oude versie was niet volledig verwijderd. Cleanup tool gebruikt en daarna nieuwe versie geïnstalleerd. Werkt nu.',
        'comments': [
            'Welke foutcode zie je?',
            'Error 1603',
            'Dat is een installatie probleem. Ik ga het remote fixen',
            'Oude versie verwijderd, nieuwe aan het installeren',
            'Klaar! Adobe Reader werkt'
        ]
    },
    {
        'title': 'Word documenten openen heel langzaam',
        'description': 'Het duurt soms wel 30 seconden voordat een Word document opent. Vroeger ging dit veel sneller.',
        'category_id': 2,
        'priority': 'low',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Word template cache was corrupt. Cache geleegd en Word opnieuw gestart. Documenten openen nu snel.',
        'comments': [
            'Gebeurt dit met alle documenten?',
            'Ja, zelfs nieuwe lege documenten',
            'Ik ga de cache legen',
            'Veel sneller nu, thanks!'
        ]
    },
    
    # Netwerk (category_id: 3)
    {
        'title': 'Geen internet verbinding op laptop',
        'description': 'Mijn laptop kan geen verbinding maken met het WiFi netwerk. Andere apparaten werken wel.',
        'category_id': 3,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'WiFi driver was verouderd. Driver geüpdatet en laptop opnieuw opgestart. WiFi werkt weer normaal.',
        'comments': [
            'Zie je het WiFi netwerk wel in de lijst?',
            'Ja, maar kan niet verbinden',
            'Krijg je een foutmelding?',
            'Kan geen verbinding maken',
            'Ik update de WiFi driver',
            'Werkt weer! Bedankt'
        ]
    },
    {
        'title': 'VPN verbinding zeer traag',
        'description': 'Sinds gisteren is mijn VPN verbinding extreem traag. Downloaden duurt eeuwen.',
        'category_id': 3,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'VPN server had hoge load. Gebruiker verbonden met alternatieve VPN server. Snelheid nu normaal.',
        'comments': [
            'Welke VPN server gebruik je?',
            'vpn1.bedrijf.nl',
            'Die heeft inderdaad problemen. Ik switch je naar vpn2',
            'Veel sneller nu!'
        ]
    },
    {
        'title': 'Kan niet bij netwerkschijf',
        'description': 'Ik krijg "Toegang geweigerd" als ik de S: schijf probeer te openen.',
        'category_id': 3,
        'priority': 'medium',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Gebruiker was niet toegevoegd aan de juiste AD groep. Rechten toegekend en netwerkschijf is nu toegankelijk.',
        'comments': [
            'Welke map probeer je te openen?',
            'S:\\Gedeeld\\Projecten',
            'Ik check je rechten',
            'Je had geen toegang, nu wel',
            'Werkt! Dankjewel'
        ]
    },
    
    # Account (category_id: 4)
    {
        'title': 'Wachtwoord vergeten',
        'description': 'Ik ben mijn wachtwoord vergeten en kan niet meer inloggen.',
        'category_id': 4,
        'priority': 'high',
        'ticket_type': 'service_request',
        'status': 'closed',
        'resolution': 'Wachtwoord gereset via AD. Tijdelijk wachtwoord verstuurd naar privé email. Gebruiker kan weer inloggen.',
        'comments': [
            'Ik ga je wachtwoord resetten',
            'Check je privé email voor het tijdelijke wachtwoord',
            'Email ontvangen, kan weer inloggen. Thanks!'
        ]
    },
    {
        'title': 'Account vergrendeld na verkeerde wachtwoorden',
        'description': 'Mijn account is vergrendeld. Ik heb per ongeluk een paar keer het verkeerde wachtwoord ingetypt.',
        'category_id': 4,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Account ontgrendeld in Active Directory. Gebruiker kan weer inloggen met bestaande wachtwoord.',
        'comments': [
            'Ik ontgrendel je account',
            'Klaar, probeer het nu',
            'Werkt weer!'
        ]
    },
    
    # Email (category_id: 5)
    {
        'title': 'Emails komen niet aan',
        'description': 'Ik verstuur emails maar ontvangers krijgen ze niet. Geen foutmelding.',
        'category_id': 5,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Emails zaten vast in Outbox. Outlook opnieuw gestart en emails zijn verzonden.',
        'comments': [
            'Staan de emails in je Verzonden items?',
            'Nee, zie ze niet',
            'Check je Outbox',
            'Oh daar staan ze! Hoe verstuur ik ze?',
            'Outlook herstarten, dan gaan ze vanzelf',
            'Verstuurd! Bedankt'
        ]
    },
    {
        'title': 'Outlook geeft foutmelding bij opstarten',
        'description': 'Elke keer als ik Outlook open krijg ik: "Cannot start Microsoft Outlook"',
        'category_id': 5,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Outlook profiel was corrupt. Nieuw profiel aangemaakt en emails gesynchroniseerd. Outlook werkt weer.',
        'comments': [
            'Ik ga je Outlook profiel opnieuw aanmaken',
            'Duurt het lang?',
            'Ongeveer 10 minuten voor alle emails',
            'Klaar, probeer het nu',
            'Werkt perfect!'
        ]
    },
    {
        'title': 'Kan geen bijlagen versturen groter dan 10MB',
        'description': 'Als ik een bestand groter dan 10MB probeer te versturen krijg ik een foutmelding.',
        'category_id': 5,
        'priority': 'low',
        'ticket_type': 'service_request',
        'status': 'closed',
        'resolution': 'Dit is een limiet van onze mailserver. Gebruiker geïnstrueerd om WeTransfer of OneDrive te gebruiken voor grote bestanden.',
        'comments': [
            'Dat is een limiet van de mailserver',
            'Hoe kan ik dan grote bestanden delen?',
            'Gebruik WeTransfer of OneDrive',
            'Ik stuur je de instructies',
            'Duidelijk, bedankt!'
        ]
    },
    
    # Ecoro SHD (category_id: 9)
    {
        'title': 'SHD geeft foutmelding bij inloggen',
        'description': 'Als ik probeer in te loggen op SHD krijg ik: "Database connection failed"',
        'category_id': 9,
        'priority': 'urgent',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'SHD database server was tijdelijk offline. Server herstart en SHD werkt weer normaal.',
        'comments': [
            'Kunnen anderen wel inloggen?',
            'Nee, collega heeft hetzelfde probleem',
            'Dan is het een server probleem. Ik check het',
            'Database server herstart',
            'Werkt weer voor iedereen'
        ]
    },
    {
        'title': 'Kan geen nieuwe klant aanmaken in SHD',
        'description': 'De "Nieuwe klant" knop doet niets als ik erop klik.',
        'category_id': 9,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Browser cache was vol. Cache geleegd en SHD werkt weer normaal.',
        'comments': [
            'Welke browser gebruik je?',
            'Chrome',
            'Probeer eens Ctrl+F5 om te refreshen',
            'Werkt nu! Wat deed dat?',
            'Cache refresh, probleem opgelost'
        ]
    },
    
    # WinqlWise (category_id: 10)
    {
        'title': 'WinqlWise start niet op',
        'description': 'Als ik WinqlWise probeer te starten gebeurt er niets. Geen foutmelding, programma start gewoon niet.',
        'category_id': 10,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'WinqlWise proces draaide al op de achtergrond. Proces beëindigd via Task Manager en programma opnieuw gestart.',
        'comments': [
            'Ik check of het proces al draait',
            'Inderdaad, draait op achtergrond',
            'Proces gestopt, probeer nu opnieuw',
            'Start nu wel op!'
        ]
    },
    
    # Kassa (category_id: 11)
    {
        'title': 'Kassa kan geen verbinding maken met server',
        'description': 'Kassa geeft foutmelding: "Geen verbinding met server". Kan geen transacties verwerken.',
        'category_id': 11,
        'priority': 'urgent',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Netwerkkabel was los. Kabel opnieuw aangesloten en kassa werkt weer.',
        'comments': [
            'Welke kassa?',
            'Kassa 2 bij de ingang',
            'Ik kom direct kijken',
            'Kabel zat los, nu weer vast',
            'Werkt weer, thanks!'
        ]
    },
    {
        'title': 'Bonprinter print niet',
        'description': 'De bonprinter bij kassa 3 print geen bonnen meer. Lampje knippert rood.',
        'category_id': 11,
        'priority': 'high',
        'ticket_type': 'incident',
        'status': 'closed',
        'resolution': 'Bonrol was op. Nieuwe rol geplaatst en printer werkt weer.',
        'comments': [
            'Ik kom een nieuwe rol brengen',
            'Nieuwe rol geplaatst',
            'Test bon print succesvol'
        ]
    }
]

def generate_tickets():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()
    
    print("Generating 20 ICT test tickets...")
    print("=" * 70)
    
    user_id = 1
    tickets_created = 0
    
    # Get highest ticket number
    cursor.execute("SELECT MAX(CAST(SUBSTRING(ticket_number, 5) AS UNSIGNED)) FROM tickets WHERE ticket_number LIKE 'TKT-%'")
    result = cursor.fetchone()
    start_number = (result[0] or 2024000) + 1
    
    for idx, ticket_data in enumerate(TICKETS):
        try:
            created_at = datetime.now() - timedelta(days=random.randint(1, 60))
            updated_at = created_at + timedelta(hours=random.randint(2, 72))
            resolved_at = updated_at if ticket_data['status'] == 'closed' else None
            
            cursor.execute("""
                INSERT INTO tickets 
                (ticket_number, user_id, category_id, title, description, 
                 priority, ticket_type, status, resolution, source,
                 created_at, updated_at, resolved_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """, (
                f"TKT-{start_number + idx}",
                user_id,
                ticket_data['category_id'],
                ticket_data['title'],
                ticket_data['description'],
                ticket_data['priority'],
                ticket_data['ticket_type'],
                ticket_data['status'],
                ticket_data['resolution'],
                'web',
                created_at,
                updated_at,
                resolved_at
            ))
            
            ticket_id = cursor.lastrowid
            
            # Add comments
            comment_time = created_at + timedelta(minutes=30)
            for comment_text in ticket_data['comments']:
                cursor.execute("""
                    INSERT INTO ticket_comments 
                    (ticket_id, user_id, comment, is_internal, created_at)
                    VALUES (%s, %s, %s, %s, %s)
                """, (ticket_id, user_id, comment_text, 0, comment_time))
                comment_time += timedelta(minutes=random.randint(10, 60))
            
            tickets_created += 1
            print(f"✓ [{tickets_created:2d}/20] {ticket_data['title'][:50]}")
            print(f"           Category: {ticket_data['category_id']}, Comments: {len(ticket_data['comments'])}")
            
        except Exception as e:
            print(f"✗ Error: {ticket_data['title'][:40]}: {e}")
    
    conn.commit()
    cursor.close()
    conn.close()
    
    print("=" * 70)
    print(f"✓ Successfully created {tickets_created} tickets!")
    print("\nNext steps:")
    print("1. Sync tickets: python sync_tickets_to_vector_db.py --limit 25")
    print("2. Test RAG: python test_rag_query.py")

if __name__ == "__main__":
    generate_tickets()
