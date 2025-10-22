"""
Knowledge Graph Usage Example

This script demonstrates practical usage of the knowledge graph
in the context of the RAG AI system.
"""

from knowledge_graph import KnowledgeGraph

# Database configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # Update with your password
    'database': 'ticketportaal'
}

# Initialize knowledge graph
kg = KnowledgeGraph(db_config)

# Load existing graph from database
print("Loading knowledge graph from database...")
kg.load_from_db(min_confidence=0.5)  # Only load edges with confidence >= 0.5
print(f"Loaded {kg.graph.number_of_nodes()} nodes and {kg.graph.number_of_edges()} edges\n")

# ============================================================================
# Example 1: Find Related Tickets
# ============================================================================
print("=" * 70)
print("Example 1: Find Related Tickets")
print("=" * 70)

ticket_id = 'ticket_1'
print(f"\nFinding tickets related to {ticket_id}...")

# Get similar tickets
similar = kg.get_similar_nodes(ticket_id, top_k=5)
if similar:
    print(f"\nSimilar tickets:")
    for node_id, score in similar:
        node_data = kg.graph.nodes[node_id]
        props = node_data.get('properties', {})
        print(f"  • {node_id}: {props.get('title', 'N/A')} (similarity: {score:.2f})")
else:
    print("  No similar tickets found")

# ============================================================================
# Example 2: Trace Ticket Relationships
# ============================================================================
print("\n" + "=" * 70)
print("Example 2: Trace Ticket Relationships")
print("=" * 70)

print(f"\nTracing relationships from {ticket_id}...")

# Traverse graph to find all related entities
subgraph = kg.traverse(ticket_id, max_depth=2)

print(f"\nFound {len(subgraph['nodes'])} related entities:")
for node in subgraph['nodes']:
    node_type = node['type']
    node_id = node['id']
    props = node['properties']
    
    if node_type == 'ticket':
        print(f"  📋 {node_id}: {props.get('title', 'N/A')}")
    elif node_type == 'user':
        print(f"  👤 {node_id}: {props.get('name', 'N/A')}")
    elif node_type == 'ci':
        print(f"  🖥️  {node_id}: {props.get('name', 'N/A')}")
    elif node_type == 'kb':
        print(f"  📚 {node_id}: {props.get('title', 'N/A')}")

print(f"\nRelationship chains:")
for edge in subgraph['edges']:
    print(f"  {edge['source']} --{edge['type']}--> {edge['target']} (confidence: {edge['confidence']:.2f})")

# ============================================================================
# Example 3: Find Affected CI Items
# ============================================================================
print("\n" + "=" * 70)
print("Example 3: Find Affected CI Items")
print("=" * 70)

print(f"\nFinding CI items affected by {ticket_id}...")

# Get CI items connected via AFFECTS relationship
affected_cis = kg.get_neighbors(ticket_id, edge_type='AFFECTS', direction='out')

if affected_cis:
    print(f"\nAffected CI items:")
    for ci_id in affected_cis:
        ci_data = kg.graph.nodes[ci_id]
        props = ci_data.get('properties', {})
        
        # Get edge confidence
        edge_data = kg.graph[ticket_id][ci_id]
        confidence = edge_data.get('confidence', 0.0)
        
        print(f"  • {props.get('name', 'N/A')} ({props.get('type', 'N/A')})")
        print(f"    Confidence: {confidence:.2f}")
        print(f"    Brand: {props.get('brand', 'N/A')}, Model: {props.get('model', 'N/A')}")
else:
    print("  No affected CI items found")

# ============================================================================
# Example 4: Find Resolution Path
# ============================================================================
print("\n" + "=" * 70)
print("Example 4: Find Resolution Path")
print("=" * 70)

# Find path from ticket to KB article
kb_id = 'kb_1'
print(f"\nFinding resolution path from {ticket_id} to {kb_id}...")

paths = kg.find_paths(ticket_id, kb_id, max_length=3)

if paths:
    print(f"\nFound {len(paths)} path(s):")
    for i, path in enumerate(paths, 1):
        print(f"\n  Path {i}:")
        for j in range(len(path) - 1):
            source = path[j]
            target = path[j + 1]
            
            # Get edge type
            if kg.graph.has_edge(source, target):
                edge_data = kg.graph[source][target]
                edge_type = edge_data.get('edge_type', 'UNKNOWN')
                print(f"    {source} --{edge_type}--> {target}")
else:
    print("  No paths found")

# ============================================================================
# Example 5: Identify Important Tickets (Centrality)
# ============================================================================
print("\n" + "=" * 70)
print("Example 5: Identify Important Tickets (Centrality)")
print("=" * 70)

print("\nComputing centrality scores for tickets...")

# Get all ticket nodes
ticket_nodes = [node_id for node_id in kg.graph.nodes() 
                if kg.graph.nodes[node_id].get('node_type') == 'ticket']

# Compute centrality for each ticket
ticket_centrality = []
for ticket_id in ticket_nodes:
    centrality = kg.compute_centrality(ticket_id)
    props = kg.graph.nodes[ticket_id].get('properties', {})
    ticket_centrality.append((ticket_id, props.get('title', 'N/A'), centrality))

# Sort by centrality (most connected first)
ticket_centrality.sort(key=lambda x: x[2], reverse=True)

print("\nMost connected tickets (highest centrality):")
for ticket_id, title, centrality in ticket_centrality[:5]:
    print(f"  • {ticket_id}: {title}")
    print(f"    Centrality: {centrality:.3f} (connected to {int(centrality * (kg.graph.number_of_nodes() - 1))} nodes)")

# ============================================================================
# Example 6: Graph Statistics
# ============================================================================
print("\n" + "=" * 70)
print("Example 6: Graph Statistics")
print("=" * 70)

stats = kg.get_stats()

print(f"\n📊 Graph Overview:")
print(f"  Total Nodes: {stats['total_nodes']}")
print(f"  Total Edges: {stats['total_edges']}")
print(f"  Average Degree: {stats['avg_degree']:.2f}")
print(f"  Graph Density: {stats['density']:.4f}")

print(f"\n📊 Node Distribution:")
for node_type, count in sorted(stats['node_types'].items(), key=lambda x: x[1], reverse=True):
    percentage = (count / stats['total_nodes']) * 100
    print(f"  {node_type}: {count} ({percentage:.1f}%)")

print(f"\n📊 Relationship Distribution:")
for edge_type, count in sorted(stats['edge_types'].items(), key=lambda x: x[1], reverse=True):
    percentage = (count / stats['total_edges']) * 100
    print(f"  {edge_type}: {count} ({percentage:.1f}%)")

# ============================================================================
# Example 7: RAG Query Enhancement
# ============================================================================
print("\n" + "=" * 70)
print("Example 7: RAG Query Enhancement")
print("=" * 70)

print("\nSimulating RAG query enhancement with graph data...")

# Simulate: Vector search found these tickets
top_tickets = ['ticket_1', 'ticket_3']

print(f"\nVector search returned: {top_tickets}")
print("\nEnhancing with graph data...")

for ticket_id in top_tickets:
    print(f"\n  {ticket_id}:")
    
    # Get ticket properties
    props = kg.graph.nodes[ticket_id].get('properties', {})
    print(f"    Title: {props.get('title', 'N/A')}")
    
    # Get centrality (importance score)
    centrality = kg.compute_centrality(ticket_id)
    print(f"    Importance: {centrality:.3f}")
    
    # Get related entities
    neighbors = kg.get_neighbors(ticket_id, direction='out')
    print(f"    Related entities: {len(neighbors)}")
    
    # Get similar tickets
    similar = kg.get_similar_nodes(ticket_id, top_k=3)
    if similar:
        print(f"    Similar tickets: {[s[0] for s in similar]}")
    
    # Compute enhanced score (vector + graph)
    vector_score = 0.85  # Simulated vector similarity
    graph_boost = centrality * 0.2  # 20% weight for graph centrality
    final_score = vector_score + graph_boost
    print(f"    Final score: {final_score:.3f} (vector: {vector_score:.2f} + graph: {graph_boost:.2f})")

print("\n" + "=" * 70)
print("Examples complete!")
print("=" * 70)
