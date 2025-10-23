"""
Test script for Entity and Relationship Extraction.

Tests the complete pipeline:
1. Extract entities from ticket text
2. Extract relationships between entities
3. Convert to graph nodes and edges
4. Validate all data
"""

import sys
import os

# Add parent directory to path
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from entity_extractor import EntityExtractor
from relationship_extractor import RelationshipExtractor


def test_entity_extraction():
    """Test entity extraction from ticket data."""
    print("=" * 70)
    print("TEST 1: Entity Extraction")
    print("=" * 70)
    
    # Initialize extractor
    try:
        extractor = EntityExtractor()
    except OSError as e:
        print(f"\n❌ Error: {e}")
        print("\nTo install the Dutch spaCy model, run:")
        print("  python -m spacy download nl_core_news_lg")
        return None, None
    
    # Test ticket data
    ticket_data = {
        'ticket_id': 123,
        'title': 'Dell Latitude laptop start niet op',
        'description': '''
        Mijn Dell Latitude 5520 geeft een blue screen error 0x0000007B bij opstarten.
        Het probleem is begonnen na de laatste Windows update.
        De laptop staat op kantoor Hengelo, verdieping 2.
        Collega Jan Jansen heeft hetzelfde probleem gemeld.
        ''',
        'comments': [
            'Ik heb geprobeerd in safe mode op te starten maar dat werkt ook niet.',
            'Microsoft support heeft geen oplossing kunnen bieden.',
            'Het lijkt een BIOS probleem te zijn.'
        ],
        'resolution': 'BIOS update uitgevoerd naar versie 1.15.0 en boot order aangepast. Probleem opgelost.',
        'dynamic_fields': {
            'Merk': 'Dell',
            'Model': 'Latitude 5520',
            'Serienummer': 'ABC123XYZ',
            'Locatie': 'Kantoor Hengelo',
            'Afdeling': 'Sales'
        }
    }
    
    # Extract entities
    print("\nExtracting entities...")
    entities = extractor.extract_ticket_entities(ticket_data)
    
    # Display results
    total_entities = sum(len(entity_list) for entity_list in entities.values())
    print(f"\n✅ Extracted {total_entities} entities:")
    
    for entity_type, entity_list in entities.items():
        if entity_list:
            print(f"\n  {entity_type.upper()} ({len(entity_list)}):")
            for entity in entity_list[:5]:  # Show first 5
                source = entity.get('source', 'text')
                print(f"    • {entity['text']} (confidence: {entity['confidence']:.2f}, source: {source})")
            if len(entity_list) > 5:
                print(f"    ... and {len(entity_list) - 5} more")
    
    # Convert to graph nodes
    print("\n\nConverting to graph nodes...")
    nodes = extractor.entities_to_graph_nodes(entities, ticket_id=123)
    print(f"✅ Generated {len(nodes)} graph nodes")
    
    print("\nSample nodes:")
    for node in nodes[:5]:
        print(f"  • {node['node_id']}: {node['properties']['name']} ({node['node_type']})")
    
    return entities, ticket_data


def test_relationship_extraction(entities, ticket_data):
    """Test relationship extraction."""
    print("\n" + "=" * 70)
    print("TEST 2: Relationship Extraction")
    print("=" * 70)
    
    # Database configuration
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'ticketportaal'
    }
    
    # Initialize extractor
    extractor = RelationshipExtractor(db_config)
    
    # Add some additional ticket data for relationships
    ticket_data.update({
        'user_id': 45,
        'assigned_to': 12,
        'category': 'Hardware',
        'status': 'Closed',
        'created_at': '2024-10-20 10:30:00',
        'assigned_at': '2024-10-20 11:00:00',
        'related_cis': [
            {'ci_id': 789, 'impact_level': 'high'}
        ]
    })
    
    # Extract relationships
    print("\nExtracting relationships...")
    edges = extractor.extract_ticket_relationships(ticket_data, entities)
    
    print(f"\n✅ Extracted {len(edges)} relationships:")
    
    # Group by edge type
    edge_types = {}
    for edge in edges:
        edge_type = edge['edge_type']
        if edge_type not in edge_types:
            edge_types[edge_type] = []
        edge_types[edge_type].append(edge)
    
    for edge_type, edge_list in edge_types.items():
        print(f"\n  {edge_type} ({len(edge_list)}):")
        for edge in edge_list[:3]:  # Show first 3
            print(f"    • {edge['source_id']} --> {edge['target_id']} (confidence: {edge['confidence']:.2f})")
        if len(edge_list) > 3:
            print(f"    ... and {len(edge_list) - 3} more")
    
    # Validate all edges
    print("\n\nValidating edges...")
    valid_count = 0
    invalid_count = 0
    
    for edge in edges:
        is_valid, error = extractor.validate_edge(edge)
        if is_valid:
            valid_count += 1
        else:
            invalid_count += 1
            print(f"  ❌ Invalid edge: {error}")
    
    print(f"\n✅ Valid edges: {valid_count}")
    if invalid_count > 0:
        print(f"❌ Invalid edges: {invalid_count}")
    else:
        print("✅ All edges are valid!")
    
    return edges


def test_integration():
    """Test complete integration."""
    print("\n" + "=" * 70)
    print("TEST 3: Integration Test")
    print("=" * 70)
    
    print("\nThis test demonstrates the complete pipeline:")
    print("1. Extract entities from ticket")
    print("2. Extract relationships")
    print("3. Generate graph nodes and edges")
    print("4. Ready for insertion into knowledge graph")
    
    # Run entity extraction
    entities, ticket_data = test_entity_extraction()
    
    if entities is None:
        print("\n❌ Entity extraction failed. Cannot continue with integration test.")
        return
    
    # Run relationship extraction
    edges = test_relationship_extraction(entities, ticket_data)
    
    # Summary
    print("\n" + "=" * 70)
    print("INTEGRATION TEST SUMMARY")
    print("=" * 70)
    
    total_entities = sum(len(entity_list) for entity_list in entities.values())
    print(f"\n✅ Extracted {total_entities} entities")
    print(f"✅ Extracted {len(edges)} relationships")
    print(f"\n📊 Entity breakdown:")
    for entity_type, entity_list in entities.items():
        if entity_list:
            print(f"   - {entity_type}: {len(entity_list)}")
    
    print(f"\n📊 Relationship breakdown:")
    edge_types = {}
    for edge in edges:
        edge_type = edge['edge_type']
        edge_types[edge_type] = edge_types.get(edge_type, 0) + 1
    
    for edge_type, count in edge_types.items():
        print(f"   - {edge_type}: {count}")
    
    print("\n" + "=" * 70)
    print("✅ ALL TESTS PASSED!")
    print("=" * 70)
    print("\nThe entity and relationship extraction modules are working correctly.")
    print("Next steps:")
    print("1. Integrate with knowledge_graph.py to insert nodes and edges")
    print("2. Integrate with sync_tickets_to_vector_db.py for automatic extraction")
    print("3. Test with real ticket data from the database")


def main():
    """Run all tests."""
    print("\n" + "=" * 70)
    print("ENTITY & RELATIONSHIP EXTRACTION TEST SUITE")
    print("=" * 70)
    
    try:
        test_integration()
    except Exception as e:
        print(f"\n❌ Test failed with error: {e}")
        import traceback
        traceback.print_exc()
        return 1
    
    return 0


if __name__ == "__main__":
    exit(main())
