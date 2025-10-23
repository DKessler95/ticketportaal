"""
Relationship Extraction Module
Extracts relationships between entities to build knowledge graph edges.

This module handles:
- Building edges: CREATED_BY, AFFECTS, SIMILAR_TO, RESOLVED_BY, BELONGS_TO
- Calculating confidence scores for relationships
- Handling edge cases (missing data, invalid references)
- Generating graph edges from ticket data
"""

import mysql.connector
from typing import Dict, List, Tuple, Optional, Any
import logging
from datetime import datetime
from sentence_transformers import SentenceTransformer
import numpy as np

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [REL] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


class RelationshipExtractor:
    """
    Extracts relationships between entities for knowledge graph.
    
    Builds edges between tickets, users, CI items, KB articles, and extracted entities.
    """
    
    def __init__(self, db_config: Dict[str, str], similarity_model: Optional[SentenceTransformer] = None):
        """
        Initialize relationship extractor.
        
        Args:
            db_config: MySQL connection configuration
            similarity_model: Optional pre-loaded sentence transformer model for similarity
        """
        self.db_config = db_config
        self.similarity_model = similarity_model
        
        # Confidence thresholds
        self.DIRECT_RELATION_CONFIDENCE = 1.0  # Database foreign keys
        self.EXTRACTED_RELATION_CONFIDENCE = 0.85  # Extracted from text
        self.INFERRED_RELATION_CONFIDENCE = 0.7  # Inferred from patterns
        self.SIMILARITY_THRESHOLD = 0.75  # Minimum similarity for SIMILAR_TO edges
    
    def connect_db(self) -> mysql.connector.MySQLConnection:
        """Create database connection."""
        return mysql.connector.connect(**self.db_config)
    
    def extract_ticket_relationships(self, ticket_data: Dict[str, Any], 
                                    entities: Dict[str, List[Dict[str, Any]]]) -> List[Dict[str, Any]]:
        """
        Extract all relationships for a ticket.
        
        Args:
            ticket_data: Dictionary containing ticket fields
            entities: Extracted entities from entity_extractor
        
        Returns:
            List of edge dictionaries ready for graph insertion
        """
        edges = []
        ticket_id = ticket_data.get('ticket_id')
        
        if not ticket_id:
            logger.warning("No ticket_id provided, skipping relationship extraction")
            return edges
        
        ticket_node_id = f"ticket_{ticket_id}"
        
        # 1. CREATED_BY relationship (ticket -> user)
        if ticket_data.get('user_id'):
            edges.append({
                'source_id': ticket_node_id,
                'target_id': f"user_{ticket_data['user_id']}",
                'edge_type': 'CREATED_BY',
                'confidence': self.DIRECT_RELATION_CONFIDENCE,
                'properties': {
                    'created_at': ticket_data.get('created_at', datetime.now().isoformat())
                }
            })
        
        # 2. ASSIGNED_TO relationship (ticket -> agent)
        if ticket_data.get('assigned_to'):
            edges.append({
                'source_id': ticket_node_id,
                'target_id': f"user_{ticket_data['assigned_to']}",
                'edge_type': 'ASSIGNED_TO',
                'confidence': self.DIRECT_RELATION_CONFIDENCE,
                'properties': {
                    'assigned_at': ticket_data.get('assigned_at', datetime.now().isoformat())
                }
            })
        
        # 3. BELONGS_TO relationship (ticket -> category)
        if ticket_data.get('category'):
            edges.append({
                'source_id': ticket_node_id,
                'target_id': f"category_{ticket_data['category'].lower().replace(' ', '_')}",
                'edge_type': 'BELONGS_TO',
                'confidence': self.DIRECT_RELATION_CONFIDENCE,
                'properties': {
                    'category_name': ticket_data['category']
                }
            })
        
        # 4. AFFECTS relationships (ticket -> CI items)
        if ticket_data.get('related_cis'):
            for ci in ticket_data['related_cis']:
                ci_id = ci if isinstance(ci, (int, str)) else ci.get('ci_id')
                if ci_id:
                    edges.append({
                        'source_id': ticket_node_id,
                        'target_id': f"ci_{ci_id}",
                        'edge_type': 'AFFECTS',
                        'confidence': self.DIRECT_RELATION_CONFIDENCE,
                        'properties': {
                            'impact_level': ci.get('impact_level', 'medium') if isinstance(ci, dict) else 'medium'
                        }
                    })
        
        # 5. MENTIONS relationships (ticket -> extracted entities)
        for entity_type, entity_list in entities.items():
            for entity in entity_list:
                # Create node ID for entity
                entity_node_id = f"{entity_type.rstrip('s')}_{entity['text'].lower().replace(' ', '_')}"
                
                edges.append({
                    'source_id': ticket_node_id,
                    'target_id': entity_node_id,
                    'edge_type': 'MENTIONS',
                    'confidence': entity.get('confidence', 0.8),
                    'properties': {
                        'entity_text': entity['text'],
                        'entity_type': entity_type
                    }
                })
        
        # 6. RESOLVED_BY relationship (ticket -> solution/KB)
        if ticket_data.get('resolution') and ticket_data.get('status') == 'Closed':
            # Try to find related KB article
            kb_id = self._find_related_kb(ticket_data)
            if kb_id:
                edges.append({
                    'source_id': ticket_node_id,
                    'target_id': f"kb_{kb_id}",
                    'edge_type': 'RESOLVED_BY',
                    'confidence': self.INFERRED_RELATION_CONFIDENCE,
                    'properties': {
                        'resolution_text': ticket_data['resolution'][:200]  # First 200 chars
                    }
                })
        
        return edges
    
    def extract_similar_ticket_relationships(self, ticket_id: int, 
                                            ticket_embedding: Optional[np.ndarray] = None,
                                            top_k: int = 5) -> List[Dict[str, Any]]:
        """
        Find similar tickets and create SIMILAR_TO relationships.
        
        Args:
            ticket_id: ID of the ticket
            ticket_embedding: Optional pre-computed embedding
            top_k: Number of similar tickets to find
        
        Returns:
            List of SIMILAR_TO edges
        """
        edges = []
        
        if ticket_embedding is None or self.similarity_model is None:
            logger.warning("No embedding or model provided, skipping similarity extraction")
            return edges
        
        try:
            conn = self.connect_db()
            cursor = conn.cursor(dictionary=True)
            
            # Get other tickets (excluding current one)
            query = """
                SELECT ticket_id, title, description
                FROM tickets
                WHERE ticket_id != %s
                AND status IN ('Open', 'In Progress', 'Closed')
                LIMIT 100
            """
            cursor.execute(query, (ticket_id,))
            other_tickets = cursor.fetchall()
            
            if not other_tickets:
                return edges
            
            # Compute embeddings for other tickets
            other_texts = [
                f"{t['title']} {t['description']}" 
                for t in other_tickets
            ]
            other_embeddings = self.similarity_model.encode(other_texts)
            
            # Compute cosine similarities
            similarities = np.dot(other_embeddings, ticket_embedding) / (
                np.linalg.norm(other_embeddings, axis=1) * np.linalg.norm(ticket_embedding)
            )
            
            # Get top-k similar tickets above threshold
            top_indices = np.argsort(similarities)[::-1][:top_k]
            
            for idx in top_indices:
                similarity_score = float(similarities[idx])
                
                if similarity_score >= self.SIMILARITY_THRESHOLD:
                    other_ticket_id = other_tickets[idx]['ticket_id']
                    
                    edges.append({
                        'source_id': f"ticket_{ticket_id}",
                        'target_id': f"ticket_{other_ticket_id}",
                        'edge_type': 'SIMILAR_TO',
                        'confidence': similarity_score,
                        'properties': {
                            'similarity_score': similarity_score,
                            'method': 'vector_similarity'
                        }
                    })
            
            cursor.close()
            conn.close()
            
        except Exception as e:
            logger.error(f"Error extracting similar tickets: {e}")
        
        return edges
    
    def extract_ci_relationships(self, ci_data: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        Extract relationships for CI items.
        
        Args:
            ci_data: Dictionary containing CI item fields
        
        Returns:
            List of edge dictionaries
        """
        edges = []
        ci_id = ci_data.get('ci_id')
        
        if not ci_id:
            return edges
        
        ci_node_id = f"ci_{ci_id}"
        
        # LOCATED_AT relationship (CI -> location)
        if ci_data.get('location'):
            edges.append({
                'source_id': ci_node_id,
                'target_id': f"location_{ci_data['location'].lower().replace(' ', '_')}",
                'edge_type': 'LOCATED_AT',
                'confidence': self.DIRECT_RELATION_CONFIDENCE,
                'properties': {
                    'location_name': ci_data['location']
                }
            })
        
        # ASSIGNED_TO relationship (CI -> user)
        if ci_data.get('assigned_to_user_id'):
            edges.append({
                'source_id': ci_node_id,
                'target_id': f"user_{ci_data['assigned_to_user_id']}",
                'edge_type': 'ASSIGNED_TO',
                'confidence': self.DIRECT_RELATION_CONFIDENCE,
                'properties': {
                    'assignment_type': 'ci_ownership'
                }
            })
        
        # BELONGS_TO relationship (CI -> department)
        if ci_data.get('department'):
            edges.append({
                'source_id': ci_node_id,
                'target_id': f"department_{ci_data['department'].lower().replace(' ', '_')}",
                'edge_type': 'BELONGS_TO',
                'confidence': self.DIRECT_RELATION_CONFIDENCE,
                'properties': {
                    'department_name': ci_data['department']
                }
            })
        
        return edges
    
    def extract_kb_relationships(self, kb_data: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        Extract relationships for KB articles.
        
        Args:
            kb_data: Dictionary containing KB article fields
        
        Returns:
            List of edge dictionaries
        """
        edges = []
        kb_id = kb_data.get('kb_id')
        
        if not kb_id:
            return edges
        
        kb_node_id = f"kb_{kb_id}"
        
        # CREATED_BY relationship (KB -> author)
        if kb_data.get('author_id'):
            edges.append({
                'source_id': kb_node_id,
                'target_id': f"user_{kb_data['author_id']}",
                'edge_type': 'CREATED_BY',
                'confidence': self.DIRECT_RELATION_CONFIDENCE,
                'properties': {
                    'created_at': kb_data.get('created_at', datetime.now().isoformat())
                }
            })
        
        # BELONGS_TO relationship (KB -> category)
        if kb_data.get('category'):
            edges.append({
                'source_id': kb_node_id,
                'target_id': f"category_{kb_data['category'].lower().replace(' ', '_')}",
                'edge_type': 'BELONGS_TO',
                'confidence': self.DIRECT_RELATION_CONFIDENCE,
                'properties': {
                    'category_name': kb_data['category']
                }
            })
        
        return edges
    
    def _find_related_kb(self, ticket_data: Dict[str, Any]) -> Optional[int]:
        """
        Find related KB article based on ticket resolution.
        
        Args:
            ticket_data: Ticket data with resolution
        
        Returns:
            KB article ID if found, None otherwise
        """
        if not ticket_data.get('resolution'):
            return None
        
        try:
            conn = self.connect_db()
            cursor = conn.cursor(dictionary=True)
            
            # Simple keyword matching for now
            # In production, use vector similarity
            resolution_keywords = ticket_data['resolution'].lower().split()[:10]
            
            query = """
                SELECT kb_id, title, content
                FROM knowledge_base
                WHERE is_published = 1
                AND (
                    LOWER(title) LIKE %s
                    OR LOWER(content) LIKE %s
                )
                LIMIT 1
            """
            
            # Use first few words of resolution
            search_term = f"%{' '.join(resolution_keywords[:3])}%"
            cursor.execute(query, (search_term, search_term))
            
            result = cursor.fetchone()
            cursor.close()
            conn.close()
            
            return result['kb_id'] if result else None
            
        except Exception as e:
            logger.error(f"Error finding related KB: {e}")
            return None
    
    def calculate_edge_confidence(self, edge_type: str, 
                                  source_data: Dict[str, Any],
                                  target_data: Dict[str, Any],
                                  extraction_method: str = 'direct') -> float:
        """
        Calculate confidence score for an edge.
        
        Args:
            edge_type: Type of relationship
            source_data: Source node data
            target_data: Target node data
            extraction_method: How the relationship was extracted
        
        Returns:
            Confidence score between 0.0 and 1.0
        """
        # Base confidence by extraction method
        base_confidence = {
            'direct': self.DIRECT_RELATION_CONFIDENCE,
            'extracted': self.EXTRACTED_RELATION_CONFIDENCE,
            'inferred': self.INFERRED_RELATION_CONFIDENCE
        }.get(extraction_method, 0.5)
        
        # Adjust based on edge type
        if edge_type in ['CREATED_BY', 'ASSIGNED_TO', 'BELONGS_TO']:
            # These are typically direct database relationships
            return self.DIRECT_RELATION_CONFIDENCE
        
        elif edge_type == 'AFFECTS':
            # CI relationships can be direct or inferred
            if extraction_method == 'direct':
                return self.DIRECT_RELATION_CONFIDENCE
            else:
                return self.EXTRACTED_RELATION_CONFIDENCE
        
        elif edge_type == 'SIMILAR_TO':
            # Similarity is based on vector distance
            # Confidence should be passed in properties
            return base_confidence
        
        elif edge_type == 'RESOLVED_BY':
            # Resolution relationships are inferred
            return self.INFERRED_RELATION_CONFIDENCE
        
        else:
            return base_confidence
    
    def validate_edge(self, edge: Dict[str, Any]) -> Tuple[bool, Optional[str]]:
        """
        Validate an edge before insertion.
        
        Args:
            edge: Edge dictionary
        
        Returns:
            Tuple of (is_valid, error_message)
        """
        # Check required fields
        required_fields = ['source_id', 'target_id', 'edge_type', 'confidence']
        for field in required_fields:
            if field not in edge:
                return False, f"Missing required field: {field}"
        
        # Check confidence range
        if not 0.0 <= edge['confidence'] <= 1.0:
            return False, f"Confidence must be between 0.0 and 1.0, got {edge['confidence']}"
        
        # Check edge type is valid
        valid_edge_types = [
            'CREATED_BY', 'ASSIGNED_TO', 'AFFECTS', 'SIMILAR_TO',
            'RESOLVED_BY', 'BELONGS_TO', 'MENTIONS', 'LOCATED_AT',
            'DOCUMENTED_IN', 'DUPLICATE_OF'
        ]
        if edge['edge_type'] not in valid_edge_types:
            return False, f"Invalid edge type: {edge['edge_type']}"
        
        # Check source and target are different
        if edge['source_id'] == edge['target_id']:
            return False, "Source and target cannot be the same"
        
        return True, None


# Example usage
if __name__ == "__main__":
    # Database configuration
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'ticketportaal'
    }
    
    # Initialize extractor
    extractor = RelationshipExtractor(db_config)
    
    # Example ticket data
    ticket_data = {
        'ticket_id': 123,
        'user_id': 45,
        'assigned_to': 12,
        'category': 'Hardware',
        'status': 'Closed',
        'title': 'Dell Latitude laptop start niet op',
        'description': 'Laptop geeft blue screen error',
        'resolution': 'BIOS update uitgevoerd',
        'created_at': '2024-10-20 10:30:00',
        'related_cis': [
            {'ci_id': 789, 'impact_level': 'high'}
        ]
    }
    
    # Example extracted entities
    entities = {
        'products': [
            {'text': 'Dell Latitude 5520', 'confidence': 0.9}
        ],
        'errors': [
            {'text': 'blue screen error', 'confidence': 1.0}
        ],
        'locations': [
            {'text': 'Kantoor Hengelo', 'confidence': 0.85}
        ]
    }
    
    # Extract relationships
    print("Extracting relationships from ticket...")
    edges = extractor.extract_ticket_relationships(ticket_data, entities)
    
    print(f"\nExtracted {len(edges)} relationships:")
    for edge in edges:
        is_valid, error = extractor.validate_edge(edge)
        status = "✓" if is_valid else "✗"
        print(f"{status} {edge['source_id']} --{edge['edge_type']}--> {edge['target_id']} (confidence: {edge['confidence']:.2f})")
        if error:
            print(f"  Error: {error}")
