"""
Generate test tickets for RAG system testing
"""
import mysql.connector
from datetime import datetime, timedelta
import random

# Database config
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ticketportaal'
}

# Test data with category_id
TEST_TICKETS = [
    {
        'title': 'Laptop start niet op',
        'description': 'Mijn laptop doet helemaal niets meer. Geen lampjes, geen geluid. Ik heb de oplader geprobeerd maar dat helpt niet.',
        'category_id': 1,  # Hardware
        'priority': 'high',
        'status': 'closed',
        'resolution': 'Oplossing: Laptop batterij was volledig leeg. Na 30 minuten opladen startte de laptop weer normaal op. Advies: laat laptop niet volledig leeglopen.',
        'comments': [
            'Heb je de oplader al geprobeerd?',
            'Ja, maar dat helpt niet',
            'Probeer de laptop 30 minuten op te laden en dan opnieuw te starten',
            'Het werkt weer! Bedankt!'
        ]
    },
    {
        'title': 'Printer print niet in kleur',
        'description': 'De printer in de gang print alleen zwart-wit. Ik moet een presentatie in kleur printen voor morgen.',
        'category_id': 1,  # Hardware
        'priority': 'medium',
        'status': 'closed',
        'resolution': 'Oplossing: Kleurcartridge was leeg. Nieuwe cartridge geïnstalleerd. Printer werkt weer normaal.',
        'comments': [
            'Welke printer bedoel je precies?',
            'De HP printer bij de koffieautomaat',
            'Ik ga even kijken',
            'Cartridge vervangen, kun je het nu proberen?',
            'Perfect, werkt weer!'
        ]
    },
    {
        'title': 'Kan niet inloggen op SHD',
        'description': 'Ik krijg steeds "verkeerd wachtwoord" maar ik weet zeker dat mijn wachtwoord klopt.',
        'category_id': 9,  # Ecoro SHD
        'priority': 'high',
        'status': 'closed',
        'resolution': 'Oplossing: Account was vergrendeld na 3 mislukte inlogpogingen. Account ontgrendeld en wachtwoord gereset. Gebruiker kan weer inloggen.',
        'comments': [
            'Heb je caps lock aan staan?',
            'Nee, dat heb ik al gecheckt',
            'Ik zie dat je account vergrendeld is. Ik ga het ontgrendelen',
            'Je ontvangt een email om je wachtwoord te resetten',
            'Gelukt, bedankt!'
        ]
    },
    {
        'title': 'Email werkt niet op telefoon',
        'description': 'Sinds vanmorgen kan ik geen emails meer ontvangen op mijn telefoon. Op de computer werkt het wel.',
        'category_id': 5,  # Email
        'priority': 'medium',
        'status': 'closed',
        'resolution': 'Oplossing: Email app had verkeerde server instellingen na update. Instellingen gecorrigeerd: IMAP server mail.bedrijf.nl poort 993 SSL. Email synchroniseert weer.',
        'comments': [
            'Welke telefoon heb je?',
            'iPhone 12',
            'Heb je recent een update gedaan?',
            'Ja, gisteren',
            'Dat verklaart het. Ik stuur je de juiste instellingen',
            'Werkt weer, thanks!'
        ]
    },
    {
        'title': 'Monitor flikkert',
        'description': 'Mijn tweede monitor flikkert de hele tijd. Heel vervelend om naar te kijken.',
        'category_id': 1,  # Hardware
        'priority': 'low',
        'status': 'closed',
        'resolution': 'Oplossing: HDMI kabel zat los. Kabel goed aangesloten en monitor werkt stabiel.',
        'comments': [
            'Gebeurt dit constant of alleen soms?',
            'Vooral als ik beweeg',
            'Klinkt als een losse kabel. Ik kom even kijken',
            'Kabel zat inderdaad los, moet nu goed zijn'
        ]
    },
    {
        'title': 'VPN verbinding valt steeds weg',
        'description': 'Als ik thuiswerk valt mijn VPN verbinding elke 10 minuten weg. Ik moet dan opnieuw inloggen.',
        'category_id': 3,  # Netwerk
        'priority': 'high',
        'status': 'closed',
        'resolution': 'Oplossing: VPN client was verouderd. Nieuwe versie 3.2.1 geïnstalleerd met stabielere verbinding. Timeout verhoogd naar 30 minuten.',
        'comments': [
            'Welke VPN client gebruik je?',
            'FortiClient versie 2.8',
            'Die is inderdaad oud. Ik stuur je de nieuwe versie',
            'Geïnstalleerd, lijkt stabieler nu',
            'Top! Laat het weten als het nog problemen geeft'
        ]
    },
    {
        'title': 'Kan bestand niet openen',
        'description': 'Ik krijg een foutmelding als ik een Excel bestand probeer te openen: "Bestand is beschadigd"',
        'category_id': 2,  # Software
        'priority': 'medium',
        'status': 'closed',
        'resolution': 'Oplossing: Bestand was niet beschadigd maar had verkeerde extensie (.xlsx.tmp). Extensie gecorrigeerd en bestand opent normaal.',
        'comments': [
            'Kun je het bestand naar mij sturen?',
            'Verstuurd naar support@bedrijf.nl',
            'Ik zie het probleem, verkeerde extensie',
            'Gecorrigeerd bestand teruggestuurd',
            'Werkt! Bedankt'
        ]
    },
    {
        'title': 'Teams audio werkt niet',
        'description': 'In Teams meetings kan niemand mij horen. Mijn microfoon werkt wel in andere programmas.',
        'category_id': 2,  # Software
        'priority': 'high',
        'status': 'closed',
        'resolution': 'Oplossing: Teams had geen toegang tot microfoon in Windows privacy instellingen. Toegang verleend en audio werkt weer.',
        'comments': [
            'Heb je de microfoon instellingen in Teams gecheckt?',
            'Ja, daar staat de juiste microfoon geselecteerd',
            'Laat me even remote kijken',
            'Gevonden! Privacy instellingen blokkeerden Teams',
            'Ah, dat was het. Werkt nu perfect'
        ]
    },
    {
        'title': 'Wifi is heel langzaam',
        'description': 'Internet is super traag op mijn laptop. Andere collega\'s hebben geen problemen.',
        'category_id': 3,  # Netwerk
        'priority': 'low',
        'status': 'closed',
        'resolution': 'Oplossing: Laptop was verbonden met 2.4GHz netwerk in plaats van 5GHz. Verbinding gewijzigd naar 5GHz netwerk. Snelheid nu normaal.',
        'comments': [
            'Met welk netwerk ben je verbonden?',
            'Bedrijf-WiFi',
            'Dat is het 2.4GHz netwerk. Probeer Bedrijf-WiFi-5G',
            'Wow, veel sneller nu!',
            'Mooi! 5GHz heeft meer bandbreedte'
        ]
    },
    {
        'title': 'Outlook crasht bij opstarten',
        'description': 'Elke keer als ik Outlook open doe, crasht het programma direct.',
        'category_id': 5,  # Email
        'priority': 'high',
        'status': 'closed',
        'resolution': 'Oplossing: Corrupt Outlook profiel. Nieuw profiel aangemaakt en emails opnieuw gesynchroniseerd. Outlook werkt stabiel.',
        'comments': [
            'Krijg je een foutmelding?',
            'Nee, het programma sluit gewoon direct',
            'Ik ga je profiel opnieuw aanmaken',
            'Duurt het lang om emails te synchroniseren?',
            'Ongeveer 10 minuten. Klaar nu, kun je het proberen?',
            'Werkt weer! Alle emails zijn er'
        ]
    }
]

def generate_tickets():
    """Generate test tickets in database"""
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()
    
    print("Generating test tickets...")
    print("=" * 60)
    
    # Get user_id (assuming user 1 exists)
    user_id = 1
    
    tickets_created = 0
    
    for ticket_data in TEST_TICKETS:
        try:
            # Create ticket
            created_at = datetime.now() - timedelta(days=random.randint(1, 30))
            updated_at = created_at + timedelta(hours=random.randint(1, 48))
            
            cursor.execute("""
                INSERT INTO tickets 
                (ticket_number, title, description, category_id, priority, status, resolution, 
                 user_id, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """, (
                f"TKT-{2024000 + tickets_created + 1}",
                ticket_data['title'],
                ticket_data['description'],
                ticket_data['category_id'],
                ticket_data['priority'],
                ticket_data['status'],
                ticket_data['resolution'],
                user_id,
                created_at,
                updated_at
            ))
            
            ticket_id = cursor.lastrowid
            
            # Add comments
            comment_time = created_at + timedelta(minutes=30)
            for comment_text in ticket_data['comments']:
                cursor.execute("""
                    INSERT INTO ticket_comments 
                    (ticket_id, user_id, comment_text, created_at)
                    VALUES (%s, %s, %s, %s)
                """, (ticket_id, user_id, comment_text, comment_time))
                comment_time += timedelta(minutes=random.randint(5, 30))
            
            tickets_created += 1
            print(f"✓ Created: {ticket_data['title']}")
            print(f"  - {len(ticket_data['comments'])} comments")
            print(f"  - Status: {ticket_data['status']}")
            print()
            
        except Exception as e:
            print(f"✗ Error creating ticket '{ticket_data['title']}': {e}")
            print()
    
    conn.commit()
    cursor.close()
    conn.close()
    
    print("=" * 60)
    print(f"✓ Generated {tickets_created} test tickets")
    print()
    print("Next steps:")
    print("1. Run sync: python sync_tickets_to_vector_db.py --limit 20")
    print("2. Test RAG: python test_rag_query.py")

if __name__ == "__main__":
    generate_tickets()
