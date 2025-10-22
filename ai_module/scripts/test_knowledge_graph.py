"""
Test script for Knowledge Graph implementation.

This script demonstrates:
1. Creating the graph schema in MySQL
2. Adding nodes and edges
3. Querying the graph
4. Computing graph metrics
"""

import sys
import os
from knowledge_graph import KnowledgeGraph
import mysql.connector
from typing import Dict

# Add parent directory to path for imports
sys.path.append(os.path.dirname(os.path.abspath(__file__)))


def get_db_config() -> Dict[str, str]:
    """
    Get database configuration.
    Update these values to match your MySQL setup.
    """
    return {
        'host': 'localhost',
        'user': 'root',
        'password': '',  # Update with your password
        'database': 'ticketportaal'
    }


def create_schema(db_config: Dict[str, str]) -> bool:
    """
    Create knowledge graph schema if it doesn't exist.
    """
    print("=" * 70)
    print("STEP 1: Creating Knowledge Graph Schema")
    print("=" * 70)
    
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        # Read and execute migration SQL
        migration_file = os.path.join(
            os.path.dirname(os.path.dirname(os.path.dirname(__file__))),
            'database', 'migrations', '007_create_knowledge_graph_schema.sql'
        )
        
        if not os.path.exists(migration_file):
            print(f"❌ Migration file not found: {migration_file}")
            return False
        
        with open(migration_file, 'r', encoding='utf-8') as f:
            sql_script = f.read()
        
        # Execute each statement
        for statement in sql_script.split(';'):
            statement = statement.strip()
            if statement and not statement.startswith('--') and not statement.startswith('/*'):
                try:
                    cursor.execute(statement)
                    # Consume any results to avoid "Unread result found" error
                    try:
                        cursor.fetchall()
                    except:
                        pass
                except mysql.connector.Error as e:
                    # Ignore "table already exists" errors
                    if "already exists" not in str(e):
                        print(f"⚠️  Warning: {e}")
        
        conn.commit()
        print("✅ Schema created successfully")
        
        # Create new cursor for verification (avoid unread results)
        cursor.close()
        cursor = conn.cursor()
        
        # Verify tables exist
        cursor.execute("SHOW TABLES LIKE 'graph_%'")
        tables = cursor.fetchall()
        print(f"✅ Found {len(tables)} graph tables: {[t[0] for t in tables]}")
        
        cursor.close()
        conn.close()
        return True
        
    except Exception as e:
        print(f"❌ Error creating schema: {e}")
        import traceback
        traceback.print_exc()
        return False


def test_add_nodes(kg: KnowledgeGraph) -> bool:
    """
    Test adding nodes to the graph.
    """
    print("\n" + "=" * 70)
    print("STEP 2: Adding Test Nodes")
    print("=" * 70)
    
    try:
        # Add ticket nodes
        kg.add_node('ticket_1', 'ticket', {
            'ticket_number': 'T-2024-001',
            'title': 'Laptop start niet op',
            'category': 'Hardware',
            'status': 'Closed',
            'priority': 'High'
        })
        print("✅ Added ticket_1")
        
        kg.add_node('ticket_2', 'ticket', {
            'ticket_number': 'T-2024-002',
            'title': 'Printer geeft paper jam',
            'category': 'Hardware',
            'status': 'Open',
            'priority': 'Medium'
        })
        print("✅ Added ticket_2")
        
        kg.add_node('ticket_3', 'ticket', {
            'ticket_number': 'T-2024-003',
            'title': 'Dell laptop BIOS probleem',
            'category': 'Hardware',
            'status': 'Closed',
            'priority': 'High'
        })
        print("✅ Added ticket_3")
        
        # Add user nodes
        kg.add_node('user_1', 'user', {
            'name': 'Jan Jansen',
            'email': 'jan.jansen@example.com',
            'department': 'Sales',
            'location': 'Kantoor Hengelo'
        })
        print("✅ Added user_1")
        
        kg.add_node('user_2', 'user', {
            'name': 'Piet Pietersen',
            'email': 'piet.pietersen@example.com',
            'department': 'IT',
            'location': 'Kantoor Enschede'
        })
        print("✅ Added user_2")
        
        # Add CI nodes
        kg.add_node('ci_1', 'ci', {
            'ci_number': 'CI-2024-001',
            'name': 'Dell Latitude 5520',
            'type': 'Laptop',
            'brand': 'Dell',
            'model': 'Latitude 5520'
        })
        print("✅ Added ci_1")
        
        kg.add_node('ci_2', 'ci', {
            'ci_number': 'CI-2024-002',
            'name': 'HP LaserJet Pro',
            'type': 'Printer',
            'brand': 'HP',
            'model': 'LaserJet Pro M404dn'
        })
        print("✅ Added ci_2")
        
        # Add KB node
        kg.add_node('kb_1', 'kb', {
            'title': 'Laptop Opstartproblemen',
            'category': 'Hardware',
            'tags': 'laptop,hardware,troubleshooting'
        })
        print("✅ Added kb_1")
        
        return True
        
    except Exception as e:
        print(f"❌ Error adding nodes: {e}")
        return False


def test_add_edges(kg: KnowledgeGraph) -> bool:
    """
    Test adding edges to the graph.
    """
    print("\n" + "=" * 70)
    print("STEP 3: Adding Test Edges")
    print("=" * 70)
    
    try:
        # CREATED_BY relationships
        kg.add_edge('ticket_1', 'user_1', 'CREATED_BY', confidence=1.0)
        print("✅ Added edge: ticket_1 --CREATED_BY--> user_1")
        
        kg.add_edge('ticket_2', 'user_1', 'CREATED_BY', confidence=1.0)
        print("✅ Added edge: ticket_2 --CREATED_BY--> user_1")
        
        kg.add_edge('ticket_3', 'user_2', 'CREATED_BY', confidence=1.0)
        print("✅ Added edge: ticket_3 --CREATED_BY--> user_2")
        
        # AFFECTS relationships
        kg.add_edge('ticket_1', 'ci_1', 'AFFECTS', confidence=0.95, properties={
            'impact_level': 'high',
            'extraction_method': 'dynamic_fields'
        })
        print("✅ Added edge: ticket_1 --AFFECTS--> ci_1")
        
        kg.add_edge('ticket_2', 'ci_2', 'AFFECTS', confidence=0.90, properties={
            'impact_level': 'medium',
            'extraction_method': 'dynamic_fields'
        })
        print("✅ Added edge: ticket_2 --AFFECTS--> ci_2")
        
        kg.add_edge('ticket_3', 'ci_1', 'AFFECTS', confidence=0.85, properties={
            'impact_level': 'high',
            'extraction_method': 'text_extraction'
        })
        print("✅ Added edge: ticket_3 --AFFECTS--> ci_1")
        
        # SIMILAR_TO relationships
        kg.add_edge('ticket_1', 'ticket_3', 'SIMILAR_TO', confidence=0.87, properties={
            'similarity_score': 0.87,
            'method': 'vector_similarity'
        })
        print("✅ Added edge: ticket_1 --SIMILAR_TO--> ticket_3")
        
        # DOCUMENTED_IN relationship
        kg.add_edge('ticket_1', 'kb_1', 'DOCUMENTED_IN', confidence=0.80, properties={
            'relevance_score': 0.80
        })
        print("✅ Added edge: ticket_1 --DOCUMENTED_IN--> kb_1")
        
        return True
        
    except Exception as e:
        print(f"❌ Error adding edges: {e}")
        return False


def test_queries(kg: KnowledgeGraph) -> bool:
    """
    Test graph queries.
    """
    print("\n" + "=" * 70)
    print("STEP 4: Testing Graph Queries")
    print("=" * 70)
    
    try:
        # Test 1: Get neighbors
        print("\n📊 Query 1: Get neighbors of ticket_1")
        neighbors = kg.get_neighbors('ticket_1', direction='out')
        print(f"   Found {len(neighbors)} neighbors: {neighbors}")
        
        # Test 2: Get specific edge type
        print("\n📊 Query 2: Get AFFECTS relationships from ticket_1")
        affected = kg.get_neighbors('ticket_1', edge_type='AFFECTS', direction='out')
        print(f"   Affected CI items: {affected}")
        
        # Test 3: Traverse graph
        print("\n📊 Query 3: Traverse from ticket_1 (max depth 2)")
        subgraph = kg.traverse('ticket_1', max_depth=2)
        print(f"   Found {len(subgraph['nodes'])} nodes and {len(subgraph['edges'])} edges")
        print("   Nodes:", [n['id'] for n in subgraph['nodes']])
        print("   Edges:")
        for edge in subgraph['edges']:
            print(f"      {edge['source']} --{edge['type']}--> {edge['target']} (confidence: {edge['confidence']})")
        
        # Test 4: Find paths
        print("\n📊 Query 4: Find paths from ticket_1 to kb_1")
        paths = kg.find_paths('ticket_1', 'kb_1', max_length=3)
        if paths:
            for i, path in enumerate(paths, 1):
                print(f"   Path {i}: {' -> '.join(path)}")
        else:
            print("   No paths found")
        
        # Test 5: Get similar nodes
        print("\n📊 Query 5: Get similar tickets to ticket_1")
        similar = kg.get_similar_nodes('ticket_1', top_k=5)
        if similar:
            for node_id, score in similar:
                print(f"   {node_id}: similarity={score:.2f}")
        else:
            print("   No similar tickets found")
        
        # Test 6: Compute centrality
        print("\n📊 Query 6: Compute centrality scores")
        for node_id in ['ticket_1', 'ticket_2', 'ci_1', 'user_1']:
            centrality = kg.compute_centrality(node_id)
            print(f"   {node_id}: centrality={centrality:.3f}")
        
        return True
        
    except Exception as e:
        print(f"❌ Error in queries: {e}")
        import traceback
        traceback.print_exc()
        return False


def test_statistics(kg: KnowledgeGraph) -> bool:
    """
    Test graph statistics.
    """
    print("\n" + "=" * 70)
    print("STEP 5: Graph Statistics")
    print("=" * 70)
    
    try:
        stats = kg.get_stats()
        
        print(f"\n📈 Total Nodes: {stats['total_nodes']}")
        print(f"📈 Total Edges: {stats['total_edges']}")
        print(f"📈 Average Degree: {stats['avg_degree']:.2f}")
        print(f"📈 Graph Density: {stats['density']:.4f}")
        
        print("\n📊 Node Types:")
        for node_type, count in stats['node_types'].items():
            print(f"   {node_type}: {count}")
        
        print("\n📊 Edge Types:")
        for edge_type, count in stats['edge_types'].items():
            print(f"   {edge_type}: {count}")
        
        return True
        
    except Exception as e:
        print(f"❌ Error getting statistics: {e}")
        return False


def main():
    """
    Main test function.
    """
    print("\n" + "=" * 70)
    print("KNOWLEDGE GRAPH TEST SUITE")
    print("=" * 70)
    
    # Get database configuration
    db_config = get_db_config()
    
    # Test 1: Create schema
    if not create_schema(db_config):
        print("\n❌ Schema creation failed. Exiting.")
        return
    
    # Initialize knowledge graph
    kg = KnowledgeGraph(db_config)
    
    # Test 2: Add nodes
    if not test_add_nodes(kg):
        print("\n❌ Node creation failed. Exiting.")
        return
    
    # Test 3: Add edges
    if not test_add_edges(kg):
        print("\n❌ Edge creation failed. Exiting.")
        return
    
    # Reload graph from database to test persistence
    print("\n" + "=" * 70)
    print("Reloading graph from database...")
    print("=" * 70)
    kg.load_from_db(min_confidence=0.0)
    print(f"✅ Loaded {kg.graph.number_of_nodes()} nodes and {kg.graph.number_of_edges()} edges")
    
    # Test 4: Query graph
    if not test_queries(kg):
        print("\n❌ Graph queries failed.")
        return
    
    # Test 5: Statistics
    if not test_statistics(kg):
        print("\n❌ Statistics failed.")
        return
    
    # Success!
    print("\n" + "=" * 70)
    print("✅ ALL TESTS PASSED!")
    print("=" * 70)
    print("\nThe knowledge graph is ready for use in the RAG pipeline.")
    print("Next steps:")
    print("1. Integrate with sync_tickets_to_vector_db.py")
    print("2. Use graph traversal in rag_api.py for enhanced queries")
    print("3. Monitor graph growth and performance")


if __name__ == "__main__":
    main()
