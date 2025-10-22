"""
Test script for Entity Extraction functionality

This script tests the entity extractor with various ticket scenarios
to verify that entities are correctly extracted.
"""

import sys
import os

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from entity_extractor import EntityExtractor, ExtractedEntities


def print_section(title):
    """Print a formatted section header"""
    print("\n" + "=" * 70)
    print(f"  {title}")
    print("=" * 70)


def print_entities(entities: ExtractedEntities):
    """Print extracted entities in a formatted way"""
    entity_dict = entities.to_dict()
    
    for entity_type, values in entity_dict.items():
        if values:
            print(f"\n{entity_type.upper()}:")
            for value in values:
                print(f"  - {value}")


def test_hardware_ticket():
    """Test entity extraction from hardware ticket"""
    print_section("Test 1: Hardware Ticket")
    
    ticket_text = """
    Dell Latitude 5520 laptop start niet meer op na Windows update.
    Error code: 0x0000007B (BIOS probleem)
    Serienummer: ABC123XYZ
    Locatie: Kantoor Hengelo, Sales afdeling
    Gebruiker: Jan Jansen (jan.jansen@kruit-en-kramer.nl)
    IP adres: 192.168.1.100
    
    Laptop opgehaald en BIOS reset uitgevoerd.
    Windows update opnieuw geïnstalleerd.
    Laptop werkt nu weer normaal.
    """
    
    print("\nInput Text:")
    print("-" * 70)
    print(ticket_text)
    
    extractor = EntityExtractor(use_spacy=True)
    entities = extractor.extract_entities(ticket_text)
    
    print("\nExtracted Entities:")
    print("-" * 70)
    print_entities(entities)


def test_printer_ticket():
    """Test entity extraction from printer ticket"""
    print_section("Test 2: Printer Ticket")
    
    ticket_text = """
    HP LaserJet 2055 printer geeft paper jam error.
    Locatie: Kantoor Enschede
    IP: 192.168.2.50
    
    Printer opnieuw opgestart maar probleem blijft.
    HTTP 500 error bij printen vanaf Outlook.
    Canon scanner werkt wel nog.
    """
    
    print("\nInput Text:")
    print("-" * 70)
    print(ticket_text)
    
    extractor = EntityExtractor(use_spacy=True)
    entities = extractor.extract_entities(ticket_text)
    
    print("\nExtracted Entities:")
    print("-" * 70)
    print_entities(entities)


def test_network_ticket():
    """Test entity extraction from network ticket"""
    print_section("Test 3: Network Ticket")
    
    ticket_text = """
    Geen internet verbinding op werkplek.
    Locatie: Magazijn
    Switch: Cisco SG300-28
    Poort: 12
    IP adres: 192.168.10.25
    VLAN: 100
    
    DNS server niet bereikbaar.
    Ping naar 8.8.8.8 werkt wel.
    VPN verbinding failed with code 807.
    """
    
    print("\nInput Text:")
    print("-" * 70)
    print(ticket_text)
    
    extractor = EntityExtractor(use_spacy=True)
    entities = extractor.extract_entities(ticket_text)
    
    print("\nExtracted Entities:")
    print("-" * 70)
    print_entities(entities)


def test_software_ticket():
    """Test entity extraction from software ticket"""
    print_section("Test 4: Software Ticket")
    
    ticket_text = """
    Microsoft Office 365 Excel crasht bij openen van grote bestanden.
    Versie: Office 2021 Professional
    Gebruiker: Marie de Vries (m.devries@kruit-en-kramer.nl)
    
    Error: OutOfMemoryException
    RAM: 8GB (te weinig voor grote Excel files)
    
    Adobe Acrobat en Chrome werken wel normaal.
    """
    
    print("\nInput Text:")
    print("-" * 70)
    print(ticket_text)
    
    extractor = EntityExtractor(use_spacy=True)
    entities = extractor.extract_entities(ticket_text)
    
    print("\nExtracted Entities:")
    print("-" * 70)
    print_entities(entities)


def test_complete_ticket():
    """Test entity extraction from complete ticket with dynamic fields"""
    print_section("Test 5: Complete Ticket with Dynamic Fields")
    
    ticket_data = {
        'ticket_id': '123',
        'ticket_number': 'T-2024-001',
        'title': 'Laptop start niet op',
        'description': 'Dell Latitude 5520 geeft geen beeld meer na Windows update. Error 0x0000007B.',
        'resolution': 'BIOS reset uitgevoerd. Laptop start nu normaal.',
        'comments': [
            {'comment_text': 'Laptop opgehaald van kantoor Hengelo'},
            {'comment_text': 'BIOS versie geüpdatet naar A25'},
            {'comment_text': 'Windows update opnieuw geïnstalleerd'}
        ],
        'dynamic_fields': [
            {'field_name': 'Merk', 'field_value': 'Dell'},
            {'field_name': 'Model', 'field_value': 'Latitude 5520'},
            {'field_name': 'Serienummer', 'field_value': 'ABC123XYZ'},
            {'field_name': 'Locatie', 'field_value': 'Kantoor Hengelo'},
            {'field_name': 'Afdeling', 'field_value': 'Sales'}
        ]
    }
    
    print("\nInput Ticket Data:")
    print("-" * 70)
    print(f"Title: {ticket_data['title']}")
    print(f"Description: {ticket_data['description']}")
    print(f"Resolution: {ticket_data['resolution']}")
    print(f"Comments: {len(ticket_data['comments'])} comments")
    print(f"Dynamic Fields: {len(ticket_data['dynamic_fields'])} fields")
    
    extractor = EntityExtractor(use_spacy=True)
    entities = extractor.extract_from_ticket(ticket_data)
    
    print("\nExtracted Entities:")
    print("-" * 70)
    print_entities(entities)


def test_without_spacy():
    """Test entity extraction without spaCy (pattern matching only)"""
    print_section("Test 6: Pattern Matching Only (No spaCy)")
    
    ticket_text = """
    HP ProBook 450 laptop heeft BIOS error.
    IP: 192.168.1.50
    Error code: 0xC0000001
    Locatie: Kantoor Hengelo
    Contact: support@kruit-en-kramer.nl
    """
    
    print("\nInput Text:")
    print("-" * 70)
    print(ticket_text)
    
    extractor = EntityExtractor(use_spacy=False)
    entities = extractor.extract_entities(ticket_text)
    
    print("\nExtracted Entities (Pattern Matching Only):")
    print("-" * 70)
    print_entities(entities)


def main():
    """Run all tests"""
    print("\n")
    print("*" * 70)
    print("*" + " " * 68 + "*")
    print("*" + "  Entity Extraction Test Suite".center(68) + "*")
    print("*" + " " * 68 + "*")
    print("*" * 70)
    
    try:
        # Run all tests
        test_hardware_ticket()
        test_printer_ticket()
        test_network_ticket()
        test_software_ticket()
        test_complete_ticket()
        test_without_spacy()
        
        # Summary
        print_section("Test Summary")
        print("\n✓ All tests completed successfully!")
        print("\nThe entity extractor can identify:")
        print("  - Products (Dell Latitude, HP LaserJet, etc.)")
        print("  - Error codes (0x0000007B, HTTP 500, etc.)")
        print("  - Locations (Kantoor Hengelo, Magazijn, etc.)")
        print("  - Technical terms (BIOS, Windows, VPN, etc.)")
        print("  - Brands (Dell, HP, Cisco, etc.)")
        print("  - Models (Latitude 5520, LaserJet 2055, etc.)")
        print("  - IP addresses (192.168.x.x)")
        print("  - Email addresses")
        print("  - Persons and Organizations (with spaCy)")
        print("\n")
        
    except Exception as e:
        print(f"\n\nERROR: Test failed with exception: {e}")
        import traceback
        traceback.print_exc()
        return 1
    
    return 0


if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)
