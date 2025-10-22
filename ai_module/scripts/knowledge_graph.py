"""
Knowledge Graph Manager
Provides NetworkX wrapper for graph operations on the MySQL-backed knowledge graph.

This module handles:
- Loading graph data from MySQL into NetworkX
- Graph traversal and queries
- Entity and relationship management
- Graph analytics (centrality, communities, etc.)
"""

import networkx as nx
import mysql.connector
from typing import Dict, List, Tuple, Optional, Any
import json
from datetime import datetime
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [GRAPH] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


class KnowledgeGraph:
    """
    NetworkX-based knowledge graph manager with MySQL persistence.
    
    Provides high-level interface for:
    - Adding/updating nodes and edges
    - Graph traversal and queries
    - Computing graph metrics
    - Syncing with MySQL database
    """
    
    def __init__(self, db_config: Dict[str, str]):
        """
        Initialize knowledge graph manager.
        
        Args:
            db_config: MySQL connection configuration
                {
                    'host': 'localhost',
                    'user': 'root',
                    'password': 'password',
                    'database': 'ticketportaal'
                }
        """
        self.db_config = db_config
        self.graph = nx.DiGraph()  # Directed graph for relationships
        self._loaded = False
        
    def connect_db(self) -> mysql.connector.MySQLConnection:
        """Create database connection."""
        return mysql.connector.connect(**self.db_config)
    
    def load_from_db(self, node_types: Optional[List[str]] = None,
                     min_confidence: float = 0.0) -> None:
        """
        Load graph data from MySQL into NetworkX.
        
        Args:
            node_types: Optional filter for specific node types (e.g., ['ticket', 'user'])
            min_confidence: Minimum confidence threshold for edges (0.0-1.0)
        """
        logger.info("Loading knowledge graph from database...")
        
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Load nodes
            node_query = "SELECT node_id, node_type, properties, created_at FROM graph_nodes"
            if node_types:
                placeholders = ','.join(['%s'] * len(node_types))
                node_query += f" WHERE node_type IN ({placeholders})"
                cursor.execute(node_query, node_types)
            else:
                cursor.execute(node_query)
            
            nodes = cursor.fetchall()
            for node in nodes:
                properties = json.loads(node['properties']) if isinstance(node['properties'], str) else node['properties']
                self.graph.add_node(
                    node['node_id'],
                    node_type=node['node_type'],
                    properties=properties,
                    created_at=node['created_at']
                )
            
            logger.info(f"Loaded {len(nodes)} nodes")
            
            # Load edges
            edge_query = """
                SELECT edge_id, source_id, target_id, edge_type, confidence, properties
                FROM graph_edges
                WHERE confidence >= %s
            """
            cursor.execute(edge_query, (min_confidence,))
            
            edges = cursor.fetchall()
            for edge in edges:
                properties = json.loads(edge['properties']) if edge['properties'] and isinstance(edge['properties'], str) else (edge['properties'] or {})
                self.graph.add_edge(
                    edge['source_id'],
                    edge['target_id'],
                    edge_id=edge['edge_id'],
                    edge_type=edge['edge_type'],
                    confidence=float(edge['confidence']),
                    properties=properties
                )
            
            logger.info(f"Loaded {len(edges)} edges (min_confidence={min_confidence})")
            self._loaded = True
            
        except Exception as e:
            logger.error(f"Error loading graph from database: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def add_node(self, node_id: str, node_type: str, properties: Dict[str, Any],
                 persist: bool = True) -> None:
        """
        Add or update a node in the graph.
        
        Args:
            node_id: Unique node identifier (e.g., 'ticket_123')
            node_type: Type of entity (e.g., 'ticket', 'user', 'ci')
            properties: Dictionary of node attributes
            persist: If True, save to MySQL database
        """
        self.graph.add_node(
            node_id,
            node_type=node_type,
            properties=properties,
            created_at=datetime.now()
        )
        
        if persist:
            self._persist_node(node_id, node_type, properties)
    
    def add_edge(self, source_id: str, target_id: str, edge_type: str,
                 confidence: float = 1.0, properties: Optional[Dict[str, Any]] = None,
                 persist: bool = True) -> None:
        """
        Add or update an edge in the graph.
        
        Args:
            source_id: Source node ID
            target_id: Target node ID
            edge_type: Relationship type (e.g., 'CREATED_BY', 'SIMILAR_TO')
            confidence: Confidence score 0.0-1.0
            properties: Optional edge metadata
            persist: If True, save to MySQL database
        """
        if not self.graph.has_node(source_id):
            logger.warning(f"Source node {source_id} not found in graph")
            return
        
        if not self.graph.has_node(target_id):
            logger.warning(f"Target node {target_id} not found in graph")
            return
        
        self.graph.add_edge(
            source_id,
            target_id,
            edge_type=edge_type,
            confidence=confidence,
            properties=properties or {}
        )
        
        if persist:
            self._persist_edge(source_id, target_id, edge_type, confidence, properties)
    
    def get_neighbors(self, node_id: str, edge_type: Optional[str] = None,
                     direction: str = 'out') -> List[str]:
        """
        Get neighboring nodes.
        
        Args:
            node_id: Node to get neighbors for
            edge_type: Optional filter by edge type
            direction: 'out' (outgoing), 'in' (incoming), or 'both'
        
        Returns:
            List of neighbor node IDs
        """
        if not self.graph.has_node(node_id):
            return []
        
        neighbors = []
        
        if direction in ['out', 'both']:
            for neighbor in self.graph.successors(node_id):
                edge_data = self.graph[node_id][neighbor]
                if edge_type is None or edge_data.get('edge_type') == edge_type:
                    neighbors.append(neighbor)
        
        if direction in ['in', 'both']:
            for neighbor in self.graph.predecessors(node_id):
                edge_data = self.graph[neighbor][node_id]
                if edge_type is None or edge_data.get('edge_type') == edge_type:
                    neighbors.append(neighbor)
        
        return neighbors
    
    def traverse(self, start_node: str, max_depth: int = 2,
                edge_types: Optional[List[str]] = None) -> Dict[str, Any]:
        """
        Traverse graph from starting node up to max depth.
        
        Args:
            start_node: Starting node ID
            max_depth: Maximum traversal depth (default 2 hops)
            edge_types: Optional filter for edge types
        
        Returns:
            Dictionary with nodes and edges in subgraph
        """
        if not self.graph.has_node(start_node):
            return {'nodes': [], 'edges': []}
        
        visited_nodes = set()
        visited_edges = []
        queue = [(start_node, 0)]  # (node_id, depth)
        
        while queue:
            current_node, depth = queue.pop(0)
            
            if current_node in visited_nodes or depth > max_depth:
                continue
            
            visited_nodes.add(current_node)
            
            # Get outgoing edges
            for neighbor in self.graph.successors(current_node):
                edge_data = self.graph[current_node][neighbor]
                edge_type = edge_data.get('edge_type')
                
                if edge_types is None or edge_type in edge_types:
                    visited_edges.append({
                        'source': current_node,
                        'target': neighbor,
                        'type': edge_type,
                        'confidence': edge_data.get('confidence', 1.0)
                    })
                    
                    if depth < max_depth:
                        queue.append((neighbor, depth + 1))
        
        # Get node data
        nodes = []
        for node_id in visited_nodes:
            node_data = self.graph.nodes[node_id]
            nodes.append({
                'id': node_id,
                'type': node_data.get('node_type'),
                'properties': node_data.get('properties', {})
            })
        
        return {
            'nodes': nodes,
            'edges': visited_edges
        }
    
    def find_paths(self, source_id: str, target_id: str,
                  max_length: int = 3) -> List[List[str]]:
        """
        Find all paths between two nodes.
        
        Args:
            source_id: Source node ID
            target_id: Target node ID
            max_length: Maximum path length
        
        Returns:
            List of paths (each path is a list of node IDs)
        """
        if not self.graph.has_node(source_id) or not self.graph.has_node(target_id):
            return []
        
        try:
            paths = list(nx.all_simple_paths(
                self.graph,
                source_id,
                target_id,
                cutoff=max_length
            ))
            return paths
        except nx.NetworkXNoPath:
            return []
    
    def compute_centrality(self, node_id: str) -> float:
        """
        Compute degree centrality for a node.
        
        Args:
            node_id: Node to compute centrality for
        
        Returns:
            Centrality score (0.0-1.0)
        """
        if not self.graph.has_node(node_id):
            return 0.0
        
        if self.graph.number_of_nodes() == 0:
            return 0.0
        
        degree = self.graph.degree(node_id)
        max_possible_degree = self.graph.number_of_nodes() - 1
        
        if max_possible_degree == 0:
            return 0.0
        
        return degree / max_possible_degree
    
    def get_similar_nodes(self, node_id: str, top_k: int = 5) -> List[Tuple[str, float]]:
        """
        Get most similar nodes based on SIMILAR_TO edges.
        
        Args:
            node_id: Node to find similar nodes for
            top_k: Number of results to return
        
        Returns:
            List of (node_id, similarity_score) tuples
        """
        similar = []
        
        for neighbor in self.get_neighbors(node_id, edge_type='SIMILAR_TO', direction='both'):
            # Get edge data
            if self.graph.has_edge(node_id, neighbor):
                edge_data = self.graph[node_id][neighbor]
            else:
                edge_data = self.graph[neighbor][node_id]
            
            confidence = edge_data.get('confidence', 0.0)
            similar.append((neighbor, confidence))
        
        # Sort by confidence and return top_k
        similar.sort(key=lambda x: x[1], reverse=True)
        return similar[:top_k]
    
    def get_stats(self) -> Dict[str, Any]:
        """
        Get graph statistics.
        
        Returns:
            Dictionary with graph metrics
        """
        stats = {
            'total_nodes': self.graph.number_of_nodes(),
            'total_edges': self.graph.number_of_edges(),
            'node_types': {},
            'edge_types': {},
            'avg_degree': 0.0,
            'density': 0.0
        }
        
        # Count node types
        for node_id in self.graph.nodes():
            node_type = self.graph.nodes[node_id].get('node_type', 'unknown')
            stats['node_types'][node_type] = stats['node_types'].get(node_type, 0) + 1
        
        # Count edge types
        for source, target in self.graph.edges():
            edge_type = self.graph[source][target].get('edge_type', 'unknown')
            stats['edge_types'][edge_type] = stats['edge_types'].get(edge_type, 0) + 1
        
        # Compute metrics
        if self.graph.number_of_nodes() > 0:
            total_degree = sum(dict(self.graph.degree()).values())
            stats['avg_degree'] = total_degree / self.graph.number_of_nodes()
            stats['density'] = nx.density(self.graph)
        
        return stats
    
    def _persist_node(self, node_id: str, node_type: str, properties: Dict[str, Any]) -> None:
        """Save node to MySQL database."""
        conn = self.connect_db()
        cursor = conn.cursor()
        
        try:
            query = """
                INSERT INTO graph_nodes (node_id, node_type, properties)
                VALUES (%s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    node_type = VALUES(node_type),
                    properties = VALUES(properties),
                    updated_at = CURRENT_TIMESTAMP
            """
            cursor.execute(query, (node_id, node_type, json.dumps(properties)))
            conn.commit()
        except Exception as e:
            logger.error(f"Error persisting node {node_id}: {e}")
            conn.rollback()
        finally:
            cursor.close()
            conn.close()
    
    def _persist_edge(self, source_id: str, target_id: str, edge_type: str,
                     confidence: float, properties: Optional[Dict[str, Any]]) -> None:
        """Save edge to MySQL database."""
        conn = self.connect_db()
        cursor = conn.cursor()
        
        try:
            query = """
                INSERT INTO graph_edges (source_id, target_id, edge_type, confidence, properties)
                VALUES (%s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    confidence = VALUES(confidence),
                    properties = VALUES(properties),
                    updated_at = CURRENT_TIMESTAMP
            """
            props_json = json.dumps(properties) if properties else None
            cursor.execute(query, (source_id, target_id, edge_type, confidence, props_json))
            conn.commit()
        except Exception as e:
            logger.error(f"Error persisting edge {source_id}->{target_id}: {e}")
            conn.rollback()
        finally:
            cursor.close()
            conn.close()


# Example usage
if __name__ == "__main__":
    # Example configuration
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'your_password',
        'database': 'ticketportaal'
    }
    
    # Initialize graph
    kg = KnowledgeGraph(db_config)
    
    # Add some example nodes
    kg.add_node('ticket_123', 'ticket', {
        'ticket_number': 'T-2024-001',
        'title': 'Laptop start niet op',
        'category': 'Hardware'
    })
    
    kg.add_node('user_45', 'user', {
        'name': 'Jan Jansen',
        'department': 'Sales'
    })
    
    kg.add_node('ci_789', 'ci', {
        'ci_number': 'CI-2024-789',
        'name': 'Dell Latitude 5520'
    })
    
    # Add relationships
    kg.add_edge('ticket_123', 'user_45', 'CREATED_BY', confidence=1.0)
    kg.add_edge('ticket_123', 'ci_789', 'AFFECTS', confidence=0.9)
    
    # Query graph
    print("Graph Statistics:")
    print(kg.get_stats())
    
    print("\nNeighbors of ticket_123:")
    print(kg.get_neighbors('ticket_123'))
    
    print("\nTraverse from ticket_123:")
    subgraph = kg.traverse('ticket_123', max_depth=2)
    print(f"Found {len(subgraph['nodes'])} nodes and {len(subgraph['edges'])} edges")
