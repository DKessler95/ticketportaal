"""
Test script for sync pipeline
Verifies that all components work correctly before running full sync.
"""

import sys
import os

# Add parent directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

def test_imports():
    """Test that all required modules can be imported."""
    print("Testing imports...")
    
    try:
        import mysql.connector
        print("✓ mysql-connector-python")
    except ImportError as e:
        print(f"✗ mysql-connector-python: {e}")
        return False
    
    try:
        import chromadb
        print("✓ chromadb")
    except ImportError as e:
        print(f"✗ chromadb: {e}")
        return False
    
    try:
        from sentence_transformers import SentenceTransformer
        print("✓ sentence-transformers")
    except ImportError as e:
        print(f"✗ sentence-transformers: {e}")
        return False
    
    try:
        import spacy
        print("✓ spacy")
    except ImportError as e:
        print(f"✗ spacy: {e}")
        return False
    
    try:
        from tqdm import tqdm
        print("✓ tqdm")
    except ImportError as e:
        print(f"✗ tqdm: {e}")
        return False
    
    try:
        import numpy as np
        print("✓ numpy")
    except ImportError as e:
        print(f"✗ numpy: {e}")
        return False
    
    return True


def test_spacy_model():
    """Test that spaCy Dutch model is installed."""
    print("\nTesting spaCy model...")
    
    try:
        import spacy
        nlp = spacy.load("nl_core_news_lg")
        print("✓ nl_core_news_lg model loaded")
        
        # Test extraction
        doc = nlp("Dell Latitude laptop in Kantoor Hengelo")
        entities = [(ent.text, ent.label_) for ent in doc.ents]
        print(f"  Extracted {len(entities)} entities: {entities}")
        
        return True
    except OSError as e:
        print(f"✗ nl_core_news_lg model not found")
        print("  Install with: python -m spacy download nl_core_news_lg")
        return False
    except Exception as e:
        print(f"✗ Error loading model: {e}")
        return False


def test_database_connection():
    """Test MySQL database connection."""
    print("\nTesting database connection...")
    
    try:
        import mysql.connector
        
        db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'ticketportaal'
        }
        
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        # Test query
        cursor.execute("SELECT COUNT(*) FROM tickets")
        count = cursor.fetchone()[0]
        print(f"✓ Database connected: {count} tickets found")
        
        # Check for required tables
        cursor.execute("SHOW TABLES")
        tables = [table[0] for table in cursor.fetchall()]
        
        required_tables = ['tickets', 'knowledge_base', 'configuration_items', 
                          'graph_nodes', 'graph_edges', 'ticket_field_values',
                          'category_fields', 'ticket_comments', 'ticket_ci_relations']
        
        missing_tables = [t for t in required_tables if t not in tables]
        if missing_tables:
            print(f"⚠ Missing tables: {missing_tables}")
        else:
            print("✓ All required tables exist")
        
        cursor.close()
        conn.close()
        
        return True
        
    except Exception as e:
        print(f"✗ Database connection failed: {e}")
        return False


def test_chromadb():
    """Test ChromaDB initialization."""
    print("\nTesting ChromaDB...")
    
    try:
        import chromadb
        from chromadb.config import Settings
        
        chromadb_path = os.path.join(
            os.path.dirname(os.path.dirname(__file__)),
            'chromadb_data'
        )
        
        # Create directory if it doesn't exist
        os.makedirs(chromadb_path, exist_ok=True)
        
        client = chromadb.Client(Settings(
            persist_directory=chromadb_path,
            anonymized_telemetry=False
        ))
        
        # Try to create a test collection
        test_collection = client.get_or_create_collection("test_collection")
        print(f"✓ ChromaDB initialized at: {chromadb_path}")
        
        # Clean up test collection
        client.delete_collection("test_collection")
        
        return True
        
    except Exception as e:
        print(f"✗ ChromaDB initialization failed: {e}")
        return False


def test_embedding_model():
    """Test sentence transformer model loading."""
    print("\nTesting embedding model...")
    
    try:
        from sentence_transformers import SentenceTransformer
        
        print("  Loading model (this may take a minute)...")
        model = SentenceTransformer('sentence-transformers/all-mpnet-base-v2')
        print("✓ Embedding model loaded")
        
        # Test embedding generation
        test_text = "Test ticket description"
        embedding = model.encode([test_text])[0]
        print(f"  Generated embedding: shape={embedding.shape}, dtype={embedding.dtype}")
        
        if embedding.shape[0] != 768:
            print(f"⚠ Unexpected embedding dimension: {embedding.shape[0]} (expected 768)")
            return False
        
        return True
        
    except Exception as e:
        print(f"✗ Embedding model failed: {e}")
        return False


def test_custom_modules():
    """Test custom module imports."""
    print("\nTesting custom modules...")
    
    try:
        from entity_extractor import EntityExtractor
        print("✓ entity_extractor")
        
        from relationship_extractor import RelationshipExtractor
        print("✓ relationship_extractor")
        
        from knowledge_graph import KnowledgeGraph
        print("✓ knowledge_graph")
        
        return True
        
    except Exception as e:
        print(f"✗ Custom module import failed: {e}")
        return False


def test_entity_extraction():
    """Test entity extraction functionality."""
    print("\nTesting entity extraction...")
    
    try:
        from entity_extractor import EntityExtractor
        
        extractor = EntityExtractor()
        
        test_text = "Dell Latitude 5520 laptop geeft error 0x0000007B in Kantoor Hengelo"
        entities = extractor.extract_entities(test_text)
        
        print(f"✓ Entity extraction working")
        print(f"  Products: {len(entities['products'])}")
        print(f"  Errors: {len(entities['errors'])}")
        print(f"  Locations: {len(entities['locations'])}")
        
        return True
        
    except Exception as e:
        print(f"✗ Entity extraction failed: {e}")
        return False


def test_knowledge_graph():
    """Test knowledge graph functionality."""
    print("\nTesting knowledge graph...")
    
    try:
        from knowledge_graph import KnowledgeGraph
        
        db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'ticketportaal'
        }
        
        kg = KnowledgeGraph(db_config)
        
        # Try to load graph
        kg.load_from_db()
        stats = kg.get_stats()
        
        print(f"✓ Knowledge graph loaded")
        print(f"  Nodes: {stats['total_nodes']}")
        print(f"  Edges: {stats['total_edges']}")
        
        return True
        
    except Exception as e:
        print(f"✗ Knowledge graph failed: {e}")
        return False


def main():
    """Run all tests."""
    print("=" * 60)
    print("SYNC PIPELINE TEST SUITE")
    print("=" * 60)
    
    results = []
    
    # Run tests
    results.append(("Imports", test_imports()))
    results.append(("spaCy Model", test_spacy_model()))
    results.append(("Database Connection", test_database_connection()))
    results.append(("ChromaDB", test_chromadb()))
    results.append(("Embedding Model", test_embedding_model()))
    results.append(("Custom Modules", test_custom_modules()))
    results.append(("Entity Extraction", test_entity_extraction()))
    results.append(("Knowledge Graph", test_knowledge_graph()))
    
    # Summary
    print("\n" + "=" * 60)
    print("TEST SUMMARY")
    print("=" * 60)
    
    passed = sum(1 for _, result in results if result)
    total = len(results)
    
    for test_name, result in results:
        status = "✓ PASS" if result else "✗ FAIL"
        print(f"{status}: {test_name}")
    
    print("=" * 60)
    print(f"Results: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n✓ All tests passed! Ready to run sync pipeline.")
        return 0
    else:
        print(f"\n✗ {total - passed} test(s) failed. Fix issues before running sync.")
        return 1


if __name__ == "__main__":
    sys.exit(main())
