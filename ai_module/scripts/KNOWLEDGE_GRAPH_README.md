# Knowledge Graph Implementation

## Overview

The Knowledge Graph is a core component of the RAG AI system that stores entities and their relationships extracted from tickets, KB articles, CI items, and other data sources. It enables advanced queries like "find related tickets", "show affected CI items", and "trace resolution paths".

## Architecture

### Database Schema

**graph_nodes table**: Stores entities
- `node_id` (VARCHAR 255, PRIMARY KEY): Unique identifier (e.g., "ticket_123", "user_45")
- `node_type` (VARCHAR 50): Entity type (ticket, user, ci, kb, category, solution, department, location)
- `properties` (JSON): Flexible storage for entity-specific attributes
- `created_at` (TIMESTAMP): Creation timestamp
- `updated_at` (TIMESTAMP): Last update timestamp

**graph_edges table**: Stores relationships
- `edge_id` (INT, AUTO_INCREMENT PRIMARY KEY): Unique edge identifier
- `source_id` (VARCHAR 255, FOREIGN KEY): Source node ID
- `target_id` (VARCHAR 255, FOREIGN KEY): Target node ID
- `edge_type` (VARCHAR 50): Relationship type
- `confidence` (DECIMAL 3,2): Confidence score 0.00-1.00
- `properties` (JSON): Additional edge metadata
- `created_at` (TIMESTAMP): Creation timestamp
- `updated_at` (TIMESTAMP): Last update timestamp

### Indexes

For fast graph traversal:
- `idx_source_id`: Fast lookup of outgoing edges
- `idx_target_id`: Fast lookup of incoming edges
- `idx_edge_type`: Filter by relationship type
- `idx_confidence`: Filter by confidence threshold
- `idx_source_type`: Composite index for typed traversal
- `idx_target_type`: Composite index for reverse typed traversal
- `unique_edge`: Prevent duplicate edges (source_id, target_id, edge_type)

## Entity Types (Node Types)

### Core Entities

1. **ticket**: Support tickets
   - Properties: ticket_number, title, category, status, priority
   
2. **user**: Users (agents and end users)
   - Properties: name, email, department, location, role

3. **ci**: Configuration Items
   - Properties: ci_number, name, type, brand, model, location

4. **kb**: Knowledge Base articles
   - Properties: title, category, tags, content_summary

5. **category**: Ticket categories
   - Properties: name, description

6. **solution**: Resolution methods
   - Properties: description, steps, success_rate

7. **department**: Organizational departments
   - Properties: name, location

8. **location**: Physical locations
   - Properties: name, address, building

## Relationship Types (Edge Types)

### Core Relationships

1. **CREATED_BY**: Ticket → User
   - Confidence: 1.0 (direct database relation)
   - Properties: created_at

2. **ASSIGNED_TO**: Ticket → User (agent)
   - Confidence: 1.0 (direct database relation)
   - Properties: assigned_at

3. **AFFECTS**: Ticket → CI Item
   - Confidence: 0.8-1.0 (from ticket_ci_relations or extracted)
   - Properties: impact_level

4. **SIMILAR_TO**: Ticket ↔ Ticket
   - Confidence: 0.5-1.0 (from vector similarity)
   - Properties: similarity_score, method (vector/keyword)

5. **RESOLVED_BY**: Ticket → Solution
   - Confidence: 0.7-1.0 (extracted from resolution text)
   - Properties: resolution_time

6. **DOCUMENTED_IN**: Solution → KB Article
   - Confidence: 0.8-1.0 (extracted or manual link)
   - Properties: relevance_score

7. **BELONGS_TO**: Ticket → Category
   - Confidence: 1.0 (direct database relation)
   - Properties: None

8. **WORKS_IN**: User → Department
   - Confidence: 1.0 (direct database relation)
   - Properties: None

9. **LOCATED_AT**: CI Item → Location
   - Confidence: 0.9-1.0 (from CI fields)
   - Properties: None

10. **DUPLICATE_OF**: Ticket → Ticket
    - Confidence: 0.9-1.0 (high similarity + manual verification)
    - Properties: verified_by, verified_at

## Python API Usage

### Initialize Knowledge Graph

```python
from knowledge_graph import KnowledgeGraph

# Database configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'your_password',
    'database': 'ticketportaal'
}

# Create graph instance
kg = KnowledgeGraph(db_config)

# Load existing graph from database
kg.load_from_db(min_confidence=0.5)  # Only load edges with confidence >= 0.5
```

### Add Nodes

```python
# Add a ticket node
kg.add_node('ticket_123', 'ticket', {
    'ticket_number': 'T-2024-001',
    'title': 'Laptop start niet op',
    'category': 'Hardware',
    'status': 'Closed',
    'priority': 'High'
})

# Add a user node
kg.add_node('user_45', 'user', {
    'name': 'Jan Jansen',
    'email': 'jan.jansen@example.com',
    'department': 'Sales',
    'location': 'Kantoor Hengelo'
})

# Add a CI node
kg.add_node('ci_789', 'ci', {
    'ci_number': 'CI-2024-789',
    'name': 'Dell Latitude 5520',
    'type': 'Laptop',
    'brand': 'Dell',
    'model': 'Latitude 5520'
})
```

### Add Edges

```python
# Direct relationship (high confidence)
kg.add_edge('ticket_123', 'user_45', 'CREATED_BY', confidence=1.0)

# Extracted relationship (medium confidence)
kg.add_edge('ticket_123', 'ci_789', 'AFFECTS', confidence=0.85, properties={
    'extraction_method': 'dynamic_fields',
    'impact_level': 'high'
})

# Similarity relationship (variable confidence)
kg.add_edge('ticket_123', 'ticket_456', 'SIMILAR_TO', confidence=0.87, properties={
    'similarity_score': 0.87,
    'method': 'vector_similarity'
})
```

### Query Graph

```python
# Get neighbors
neighbors = kg.get_neighbors('ticket_123', edge_type='AFFECTS', direction='out')
print(f"Affected CI items: {neighbors}")

# Traverse graph (2 hops)
subgraph = kg.traverse('ticket_123', max_depth=2, edge_types=['AFFECTS', 'SIMILAR_TO'])
print(f"Found {len(subgraph['nodes'])} related nodes")

# Find paths between nodes
paths = kg.find_paths('ticket_123', 'kb_45', max_length=3)
for path in paths:
    print(f"Path: {' -> '.join(path)}")

# Get similar tickets
similar = kg.get_similar_nodes('ticket_123', top_k=5)
for node_id, score in similar:
    print(f"{node_id}: {score:.2f}")

# Compute centrality (how connected is this node?)
centrality = kg.compute_centrality('ticket_123')
print(f"Centrality: {centrality:.3f}")
```

### Graph Statistics

```python
stats = kg.get_stats()
print(f"Total nodes: {stats['total_nodes']}")
print(f"Total edges: {stats['total_edges']}")
print(f"Node types: {stats['node_types']}")
print(f"Edge types: {stats['edge_types']}")
print(f"Average degree: {stats['avg_degree']:.2f}")
print(f"Graph density: {stats['density']:.4f}")
```

## Integration with RAG Pipeline

### During Sync (sync_tickets_to_vector_db.py)

```python
from knowledge_graph import KnowledgeGraph

kg = KnowledgeGraph(db_config)
kg.load_from_db()

# For each ticket being synced
for ticket in tickets:
    # Add ticket node
    kg.add_node(f"ticket_{ticket['ticket_id']}", 'ticket', {
        'ticket_number': ticket['ticket_number'],
        'title': ticket['title'],
        'category': ticket['category'],
        'status': ticket['status']
    })
    
    # Add CREATED_BY edge
    kg.add_edge(
        f"ticket_{ticket['ticket_id']}",
        f"user_{ticket['user_id']}",
        'CREATED_BY',
        confidence=1.0
    )
    
    # Add AFFECTS edges for related CIs
    for ci in ticket.get('related_cis', []):
        kg.add_edge(
            f"ticket_{ticket['ticket_id']}",
            f"ci_{ci['ci_id']}",
            'AFFECTS',
            confidence=0.9
        )
    
    # Add SIMILAR_TO edges based on vector similarity
    similar_tickets = find_similar_tickets(ticket, top_k=5)
    for similar in similar_tickets:
        if similar['similarity'] > 0.7:
            kg.add_edge(
                f"ticket_{ticket['ticket_id']}",
                f"ticket_{similar['ticket_id']}",
                'SIMILAR_TO',
                confidence=similar['similarity'],
                properties={'method': 'vector_similarity'}
            )
```

### During RAG Query (rag_api.py)

```python
from knowledge_graph import KnowledgeGraph

kg = KnowledgeGraph(db_config)
kg.load_from_db(min_confidence=0.6)

@app.post("/rag_query")
async def rag_query(request: QueryRequest):
    # ... vector search to find relevant tickets ...
    
    # Enhance results with graph traversal
    for ticket_id in top_ticket_ids:
        # Get related entities
        subgraph = kg.traverse(f"ticket_{ticket_id}", max_depth=2)
        
        # Extract relationship chains
        chains = []
        for edge in subgraph['edges']:
            chains.append(f"{edge['source']} --{edge['type']}--> {edge['target']}")
        
        # Compute centrality (how important is this ticket?)
        centrality = kg.compute_centrality(f"ticket_{ticket_id}")
        
        # Add to reranking score
        graph_score = centrality * 0.2
    
    # Return results with relationship chains
    return {
        'ai_answer': answer,
        'similar_tickets': tickets,
        'relationship_chains': chains
    }
```

## Performance Considerations

### Loading Strategy

- **Full load**: Load entire graph at startup (for small graphs <10K nodes)
- **Filtered load**: Load only specific node types or high-confidence edges
- **Lazy load**: Load subgraphs on-demand during queries

### Caching

- Keep graph in memory after loading (NetworkX is in-memory)
- Reload periodically (e.g., after each sync) to get fresh data
- Cache common subgraph queries (e.g., "all tickets in Hardware category")

### Scalability

- Current design: Up to 50K nodes, 200K edges (fits in ~500MB RAM)
- For larger graphs: Consider Neo4j or other graph databases
- Optimize: Prune low-confidence edges (<0.5) to reduce noise

## Maintenance

### Regular Tasks

1. **Weekly**: Recompute similarity edges for active tickets
2. **Monthly**: Prune edges with confidence <0.5
3. **Quarterly**: Full graph audit and cleanup

### Monitoring

- Track graph size (nodes, edges)
- Monitor query performance (traversal time)
- Alert on graph growth >20% per month

## Future Enhancements

1. **Community Detection**: Group related tickets using Louvain algorithm
2. **PageRank**: Identify most important tickets/solutions
3. **Link Prediction**: Suggest missing relationships
4. **Temporal Graphs**: Track how relationships change over time
5. **Graph Visualization**: Web UI for exploring graph structure

## Troubleshooting

### Graph not loading
- Check MySQL connection
- Verify tables exist: `SHOW TABLES LIKE 'graph_%'`
- Check for data: `SELECT COUNT(*) FROM graph_nodes`

### Slow queries
- Verify indexes exist: `SHOW INDEX FROM graph_edges`
- Reduce max_depth in traversal
- Filter by edge_type to reduce search space

### Memory issues
- Load with higher min_confidence to reduce edges
- Load only specific node_types
- Consider pagination for large result sets

## References

- NetworkX Documentation: https://networkx.org/documentation/stable/
- Graph Database Best Practices: https://neo4j.com/developer/graph-database/
- Knowledge Graph Design Patterns: https://patterns.dataincubator.org/book/graph-data-modeling.html
