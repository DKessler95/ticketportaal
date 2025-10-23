"""
Hybrid Retrieval System
Combines dense vector search, sparse BM25 search, and graph traversal for optimal results.

This module implements:
- Dense vector search using ChromaDB
- Sparse keyword search using BM25
- Graph traversal search using NetworkX
- Hybrid search combining all three methods
- Advanced reranking with multi-factor scoring
"""

import os
import sys
from sentence_transformers import SentenceTransformer
import numpy as np
from typing import Dict, List, Optional, Any, Tuple
import logging
from datetime import datetime, timedelta
from rank_bm25 import BM25Okapi
import networkx as nx

# Qdrant imports
from qdrant_client import QdrantClient
from qdrant_client.models import Filter, FieldCondition, MatchValue

# Add parent directory to path for imports
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from knowledge_graph import KnowledgeGraph

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [RETRIEVAL] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


def sanitize_metadata(metadata: Dict[str, Any]) -> Dict[str, Any]:
    """
    Sanitize metadata to ensure all values are JSON-serializable strings.
    
    Args:
        metadata: Raw metadata dictionary
    
    Returns:
        Sanitized metadata with string values
    """
    sanitized = {}
    for key, value in metadata.items():
        if value is None:
            sanitized[key] = ''
        elif isinstance(value, (int, float, bool)):
            sanitized[key] = str(value)
        elif isinstance(value, str):
            sanitized[key] = value
        else:
            sanitized[key] = str(value)
    return sanitized


class VectorSearch:
    """
    Dense vector search using Qdrant and sentence transformers.
    
    Performs semantic similarity search using embeddings.
    """
    
    def __init__(self, qdrant_host: str = 'localhost', qdrant_port: int = 6333, 
                 embedding_model_name: str = 'sentence-transformers/all-mpnet-base-v2'):
        """
        Initialize vector search with Qdrant.
        
        Args:
            qdrant_host: Qdrant server host
            qdrant_port: Qdrant server port
            embedding_model_name: Sentence transformer model name
        """
        self.qdrant_host = qdrant_host
        self.qdrant_port = qdrant_port
        self.embedding_model_name = embedding_model_name
        
        logger.info(f"Initializing vector search with model: {embedding_model_name}")
        
        # Load embedding model
        self.embedding_model = SentenceTransformer(embedding_model_name)
        
        # Initialize Qdrant client
        self.qdrant_client = QdrantClient(host=qdrant_host, port=qdrant_port)
        
        logger.info("Vector search initialized successfully")
    
    def search(self, query: str, collection_name: str = 'tickets', 
              top_k: int = 10, metadata_filter: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        """
        Perform dense vector search in Qdrant.
        
        Args:
            query: Search query text
            collection_name: Qdrant collection to search ('tickets', 'knowledge_base', 'configuration_items')
            top_k: Number of results to return
            metadata_filter: Optional metadata filters (e.g., {'status': 'open'})
        
        Returns:
            List of search results with documents, metadata, and similarity scores
        """
        logger.info(f"Vector search: query='{query[:50]}...', collection={collection_name}, top_k={top_k}")
        
        try:
            # Generate query embedding
            query_embedding = self.embedding_model.encode(query).tolist()
            
            # Build filter if provided
            query_filter = None
            if metadata_filter:
                conditions = []
                for key, value in metadata_filter.items():
                    conditions.append(
                        FieldCondition(
                            key=key,
                            match=MatchValue(value=value)
                        )
                    )
                if conditions:
                    query_filter = Filter(must=conditions)
            
            # Search in Qdrant
            search_results = self.qdrant_client.search(
                collection_name=collection_name,
                query_vector=query_embedding,
                limit=top_k,
                query_filter=query_filter
            )
            
            # Format results
            formatted_results = []
            for hit in search_results:
                # Extract document text from payload
                document = self._extract_document_text(hit.payload)
                
                result = {
                    'id': str(hit.id),  # Convert to string for Pydantic validation
                    'document': document,
                    'metadata': sanitize_metadata(hit.payload),  # Sanitize metadata
                    'similarity_score': hit.score,
                    'source': 'vector_search',
                    'collection': collection_name
                }
                formatted_results.append(result)
            
            logger.info(f"Vector search returned {len(formatted_results)} results")
            return formatted_results
            
        except Exception as e:
            logger.error(f"Error in vector search: {e}")
            return []
    
    def _extract_document_text(self, payload: Dict[str, Any]) -> str:
        """
        Extract readable text from Qdrant payload.
        
        Args:
            payload: Document payload from Qdrant
        
        Returns:
            Formatted document text
        """
        doc_type = payload.get('type', payload.get('type_field', 'unknown'))
        
        if doc_type == 'ticket':
            title = payload.get('title', '')
            description = payload.get('description', '')
            ticket_num = payload.get('ticket_number', 'N/A')
            return f"Ticket {ticket_num}: {title}\n{description}"
        elif doc_type == 'kb_article':
            title = payload.get('title', '')
            content = payload.get('content', '')
            return f"{title}\n{content}"
        elif doc_type == 'ci_item':
            name = payload.get('name', '')
            ci_type = payload.get('type', '')
            notes = payload.get('notes', '')
            return f"{name} ({ci_type})\n{notes}"
        else:
            # Generic fallback - try to extract any text fields
            text_parts = []
            for key in ['title', 'name', 'description', 'content', 'notes']:
                if key in payload and payload[key]:
                    text_parts.append(str(payload[key]))
            return '\n'.join(text_parts) if text_parts else str(payload)
    
    def search_multi_collection(self, query: str, top_k_per_collection: int = 5,
                               metadata_filter: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        """
        Search across multiple collections.
        
        Args:
            query: Search query text
            top_k_per_collection: Number of results per collection
            metadata_filter: Optional metadata filters
        
        Returns:
            Combined results from all collections
        """
        collections = ['tickets', 'knowledge_base', 'configuration_items']
        all_results = []
        
        for collection_name in collections:
            try:
                results = self.search(query, collection_name, top_k_per_collection, metadata_filter)
                all_results.extend(results)
            except Exception as e:
                logger.warning(f"Error searching collection {collection_name}: {e}")
        
        # Sort by similarity score
        all_results.sort(key=lambda x: x['similarity_score'], reverse=True)
        
        return all_results
    
    def search_with_date_filter(self, query: str, collection_name: str = 'tickets',
                                top_k: int = 10, days_back: int = 30) -> List[Dict[str, Any]]:
        """
        Search with date range filter.
        
        Args:
            query: Search query text
            collection_name: ChromaDB collection to search
            top_k: Number of results to return
            days_back: Number of days to look back
        
        Returns:
            List of search results within date range
        """
        # Calculate date threshold
        date_threshold = (datetime.now() - timedelta(days=days_back)).strftime('%Y-%m-%d')
        
        # Note: ChromaDB metadata filtering is limited
        # For complex date filtering, we'll filter results after retrieval
        results = self.search(query, collection_name, top_k * 2)  # Get more results to filter
        
        # Filter by date if metadata contains date field
        filtered_results = []
        for result in results:
            metadata = result.get('metadata', {})
            
            # Check various date fields
            date_fields = ['updated_at', 'created_at', 'date']
            result_date = None
            
            for field in date_fields:
                if field in metadata:
                    result_date = metadata[field]
                    break
            
            if result_date:
                # Simple string comparison (works for ISO format dates)
                if result_date >= date_threshold:
                    filtered_results.append(result)
            else:
                # Include results without date
                filtered_results.append(result)
            
            if len(filtered_results) >= top_k:
                break
        
        return filtered_results[:top_k]
    
    def search_by_category(self, query: str, category: str, 
                          top_k: int = 10) -> List[Dict[str, Any]]:
        """
        Search within specific category.
        
        Args:
            query: Search query text
            category: Category to filter by (e.g., 'Hardware', 'Software')
            top_k: Number of results to return
        
        Returns:
            List of search results in category
        """
        metadata_filter = {'category': category}
        return self.search(query, 'tickets', top_k, metadata_filter)


class BM25Search:
    """
    Sparse keyword search using BM25 algorithm.
    
    Performs traditional keyword-based search with relevance scoring.
    """
    
    def __init__(self, qdrant_host: str = 'localhost', qdrant_port: int = 6333):
        """
        Initialize BM25 search with Qdrant.
        
        Args:
            qdrant_host: Qdrant server host
            qdrant_port: Qdrant server port
        """
        self.qdrant_host = qdrant_host
        self.qdrant_port = qdrant_port
        self.qdrant_client = QdrantClient(host=qdrant_host, port=qdrant_port)
        
        # BM25 indexes for each collection
        self.bm25_indexes = {}
        self.documents = {}
        self.metadata = {}
        self.doc_ids = {}
        
        logger.info("Initializing BM25 search...")
        self._build_indexes()
        logger.info("BM25 search initialized successfully")
    
    def _build_indexes(self) -> None:
        """Build BM25 indexes for all collections from Qdrant."""
        collections = ['tickets', 'knowledge_base', 'configuration_items']
        
        for collection_name in collections:
            try:
                # Scroll through all points in collection
                points, _ = self.qdrant_client.scroll(
                    collection_name=collection_name,
                    limit=10000,  # Get all documents
                    with_payload=True,
                    with_vectors=False  # We don't need vectors for BM25
                )
                
                if not points:
                    logger.warning(f"No documents in collection {collection_name}")
                    continue
                
                # Extract documents and metadata
                documents = []
                metadatas = []
                doc_ids = []
                
                for point in points:
                    # Extract text from payload
                    doc_text = self._extract_text_from_payload(point.payload)
                    documents.append(doc_text)
                    metadatas.append(point.payload)
                    doc_ids.append(point.id)
                
                # Tokenize documents
                tokenized_docs = [doc.lower().split() for doc in documents]
                
                # Create BM25 index
                self.bm25_indexes[collection_name] = BM25Okapi(tokenized_docs)
                self.documents[collection_name] = documents
                self.metadata[collection_name] = metadatas
                self.doc_ids[collection_name] = doc_ids
                
                logger.info(f"Built BM25 index for {collection_name}: {len(documents)} documents")
                
            except Exception as e:
                logger.error(f"Error building BM25 index for {collection_name}: {e}")
    
    def _extract_text_from_payload(self, payload: Dict[str, Any]) -> str:
        """Extract searchable text from payload."""
        doc_type = payload.get('type', payload.get('type_field', 'unknown'))
        
        if doc_type == 'ticket':
            parts = [
                payload.get('title', ''),
                payload.get('description', ''),
                payload.get('ticket_number', '')
            ]
        elif doc_type == 'kb_article':
            parts = [
                payload.get('title', ''),
                payload.get('content', ''),
                payload.get('tags', '')
            ]
        elif doc_type == 'ci_item':
            parts = [
                payload.get('name', ''),
                payload.get('type', ''),
                payload.get('notes', '')
            ]
        else:
            # Generic extraction
            parts = [str(v) for k, v in payload.items() if isinstance(v, str)]
        
        return ' '.join(filter(None, parts))
    
    def search(self, query: str, collection_name: str = 'tickets', 
              top_k: int = 10) -> List[Dict[str, Any]]:
        """
        Perform BM25 keyword search.
        
        Args:
            query: Search query text
            collection_name: Collection to search
            top_k: Number of results to return
        
        Returns:
            List of search results with BM25 scores
        """
        logger.info(f"BM25 search: query='{query[:50]}...', collection={collection_name}, top_k={top_k}")
        
        if collection_name not in self.bm25_indexes:
            logger.warning(f"No BM25 index for collection {collection_name}")
            return []
        
        try:
            # Tokenize query
            tokenized_query = query.lower().split()
            
            # Get BM25 scores
            bm25_index = self.bm25_indexes[collection_name]
            scores = bm25_index.get_scores(tokenized_query)
            
            # Get top-k indices
            top_indices = np.argsort(scores)[::-1][:top_k]
            
            # Format results
            results = []
            for idx in top_indices:
                if scores[idx] > 0:  # Only include results with positive scores
                    # Use the actual Qdrant document ID
                    doc_id = self.doc_ids[collection_name][idx]
                    result = {
                        'id': str(doc_id),  # Convert to string for Pydantic validation
                        'document': self.documents[collection_name][idx],
                        'metadata': sanitize_metadata(self.metadata[collection_name][idx]),  # Sanitize metadata
                        'bm25_score': float(scores[idx]),
                        'source': 'bm25_search',
                        'collection': collection_name
                    }
                    results.append(result)
            
            logger.info(f"BM25 search returned {len(results)} results")
            return results
            
        except Exception as e:
            logger.error(f"Error in BM25 search: {e}")
            return []
    
    def search_multi_collection(self, query: str, 
                               top_k_per_collection: int = 5) -> List[Dict[str, Any]]:
        """
        Search across multiple collections.
        
        Args:
            query: Search query text
            top_k_per_collection: Number of results per collection
        
        Returns:
            Combined results from all collections
        """
        all_results = []
        
        for collection_name in self.bm25_indexes.keys():
            results = self.search(query, collection_name, top_k_per_collection)
            all_results.extend(results)
        
        # Sort by BM25 score
        all_results.sort(key=lambda x: x['bm25_score'], reverse=True)
        
        return all_results
    
    def refresh_index(self, collection_name: str) -> None:
        """
        Refresh BM25 index for a collection.
        
        Args:
            collection_name: Collection to refresh
        """
        logger.info(f"Refreshing BM25 index for {collection_name}")
        
        try:
            collection = self.chroma_client.get_collection(collection_name)
            results = collection.get()
            
            if results['documents']:
                tokenized_docs = [doc.lower().split() for doc in results['documents']]
                self.bm25_indexes[collection_name] = BM25Okapi(tokenized_docs)
                self.documents[collection_name] = results['documents']
                self.metadata[collection_name] = results['metadatas']
                
                logger.info(f"Refreshed BM25 index: {len(results['documents'])} documents")
        except Exception as e:
            logger.error(f"Error refreshing BM25 index: {e}")



class GraphSearch:
    """
    Graph traversal search using NetworkX knowledge graph.
    
    Finds related entities and tickets through graph relationships.
    """
    
    def __init__(self, db_config: Dict[str, str]):
        """
        Initialize graph search.
        
        Args:
            db_config: MySQL connection configuration
        """
        self.db_config = db_config
        self.knowledge_graph = KnowledgeGraph(db_config)
        
        logger.info("Initializing graph search...")
        self.knowledge_graph.load_from_db()
        logger.info("Graph search initialized successfully")
    
    def search(self, query: str, max_hops: int = 2, 
              top_k: int = 10) -> List[Dict[str, Any]]:
        """
        Perform graph traversal search.
        
        Args:
            query: Search query text (used to find starting nodes)
            max_hops: Maximum number of hops to traverse
            top_k: Number of results to return
        
        Returns:
            List of related tickets found through graph traversal
        """
        logger.info(f"Graph search: query='{query[:50]}...', max_hops={max_hops}, top_k={top_k}")
        
        try:
            # Extract entities from query to find starting nodes
            # Simple approach: look for nodes that match query terms
            query_terms = query.lower().split()
            
            # Find starting nodes
            starting_nodes = self._find_starting_nodes(query_terms)
            
            if not starting_nodes:
                logger.info("No starting nodes found for query")
                return []
            
            # Traverse graph from starting nodes
            related_tickets = set()
            
            for start_node in starting_nodes[:5]:  # Limit to top 5 starting nodes
                # Traverse graph
                subgraph = self.knowledge_graph.traverse(
                    start_node,
                    max_depth=max_hops,
                    edge_types=['SIMILAR_TO', 'AFFECTS', 'RESOLVED_BY', 'MENTIONS']
                )
                
                # Extract ticket nodes
                for node in subgraph['nodes']:
                    if node['type'] == 'ticket':
                        related_tickets.add(node['id'])
            
            # Get ticket details and calculate centrality scores
            results = []
            for ticket_node_id in related_tickets:
                centrality = self.knowledge_graph.compute_centrality(ticket_node_id)
                node_data = self.knowledge_graph.graph.nodes[ticket_node_id]
                
                result = {
                    'id': ticket_node_id,
                    'document': self._format_ticket_document(node_data),
                    'metadata': node_data.get('properties', {}),
                    'centrality_score': centrality,
                    'source': 'graph_search',
                    'collection': 'tickets'
                }
                results.append(result)
            
            # Sort by centrality score
            results.sort(key=lambda x: x['centrality_score'], reverse=True)
            
            logger.info(f"Graph search returned {len(results)} results")
            return results[:top_k]
            
        except Exception as e:
            logger.error(f"Error in graph search: {e}")
            return []
    
    def _find_starting_nodes(self, query_terms: List[str]) -> List[str]:
        """
        Find graph nodes that match query terms.
        
        Args:
            query_terms: List of query terms
        
        Returns:
            List of node IDs
        """
        matching_nodes = []
        
        for node_id in self.knowledge_graph.graph.nodes():
            node_data = self.knowledge_graph.graph.nodes[node_id]
            properties = node_data.get('properties', {})
            
            # Check if any property matches query terms
            for prop_value in properties.values():
                if isinstance(prop_value, str):
                    prop_lower = prop_value.lower()
                    if any(term in prop_lower for term in query_terms):
                        matching_nodes.append(node_id)
                        break
        
        return matching_nodes
    
    def _format_ticket_document(self, node_data: Dict[str, Any]) -> str:
        """
        Format ticket node data as document text.
        
        Args:
            node_data: Node data from graph
        
        Returns:
            Formatted document string
        """
        properties = node_data.get('properties', {})
        
        doc = f"Ticket {properties.get('ticket_number', 'Unknown')}: {properties.get('title', '')}\n"
        doc += f"Category: {properties.get('category', 'Unknown')}\n"
        doc += f"Priority: {properties.get('priority', 'Unknown')}\n"
        doc += f"Status: {properties.get('status', 'Unknown')}"
        
        return doc
    
    def find_similar_tickets(self, ticket_id: int, top_k: int = 5) -> List[Dict[str, Any]]:
        """
        Find similar tickets using SIMILAR_TO edges.
        
        Args:
            ticket_id: Ticket ID to find similar tickets for
            top_k: Number of results to return
        
        Returns:
            List of similar tickets with similarity scores
        """
        ticket_node_id = f"ticket_{ticket_id}"
        
        if not self.knowledge_graph.graph.has_node(ticket_node_id):
            logger.warning(f"Ticket node {ticket_node_id} not found in graph")
            return []
        
        # Get similar tickets
        similar = self.knowledge_graph.get_similar_nodes(ticket_node_id, top_k)
        
        # Format results
        results = []
        for node_id, similarity_score in similar:
            node_data = self.knowledge_graph.graph.nodes[node_id]
            
            result = {
                'id': node_id,
                'document': self._format_ticket_document(node_data),
                'metadata': node_data.get('properties', {}),
                'similarity_score': similarity_score,
                'source': 'graph_similar',
                'collection': 'tickets'
            }
            results.append(result)
        
        return results
    
    def refresh_graph(self) -> None:
        """Reload graph from database."""
        logger.info("Refreshing knowledge graph...")
        self.knowledge_graph.load_from_db()
        logger.info("Knowledge graph refreshed")


class HybridRetrieval:
    """
    Hybrid retrieval system combining vector, BM25, and graph search.
    
    Executes multiple search strategies and combines results with weighted scoring.
    """
    
    def __init__(self, qdrant_host: str = 'localhost', qdrant_port: int = 6333,
                 db_config: Dict[str, str] = None,
                 embedding_model_name: str = 'sentence-transformers/all-mpnet-base-v2'):
        """
        Initialize hybrid retrieval system with Qdrant.
        
        Args:
            qdrant_host: Qdrant server host
            qdrant_port: Qdrant server port
            db_config: MySQL connection configuration
            embedding_model_name: Sentence transformer model name
        """
        logger.info("Initializing hybrid retrieval system...")
        
        # Initialize search components with Qdrant
        self.vector_search = VectorSearch(qdrant_host, qdrant_port, embedding_model_name)
        self.bm25_search = BM25Search(qdrant_host, qdrant_port)
        self.graph_search = GraphSearch(db_config) if db_config else None
        
        # Default weights for combining results
        self.weights = {
            'vector': 0.5,
            'bm25': 0.3,
            'graph': 0.2
        }
        
        logger.info("Hybrid retrieval system initialized successfully")
    
    def search(self, query: str, top_k: int = 10, 
              collection_name: str = 'tickets',
              use_vector: bool = True,
              use_bm25: bool = True,
              use_graph: bool = True,
              metadata_filter: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        """
        Perform hybrid search combining multiple strategies.
        
        Args:
            query: Search query text
            top_k: Number of final results to return
            collection_name: Primary collection to search
            use_vector: Enable vector search
            use_bm25: Enable BM25 search
            use_graph: Enable graph search
            metadata_filter: Optional metadata filters
        
        Returns:
            Combined and ranked search results
        """
        logger.info(f"Hybrid search: query='{query[:50]}...', top_k={top_k}")
        logger.info(f"Enabled: vector={use_vector}, bm25={use_bm25}, graph={use_graph}")
        
        all_results = {}  # Dict to track unique results by ID
        
        # Execute vector search
        if use_vector:
            try:
                vector_results = self.vector_search.search(
                    query, collection_name, top_k * 2, metadata_filter
                )
                for result in vector_results:
                    result_id = result['id']
                    if result_id not in all_results:
                        all_results[result_id] = result
                        all_results[result_id]['scores'] = {}
                    all_results[result_id]['scores']['vector'] = result.get('similarity_score', 0)
                
                logger.info(f"Vector search contributed {len(vector_results)} results")
            except Exception as e:
                logger.error(f"Vector search failed: {e}")
        
        # Execute BM25 search
        if use_bm25:
            try:
                bm25_results = self.bm25_search.search(query, collection_name, top_k * 2)
                for result in bm25_results:
                    result_id = result['id']
                    if result_id not in all_results:
                        all_results[result_id] = result
                        all_results[result_id]['scores'] = {}
                    all_results[result_id]['scores']['bm25'] = result.get('bm25_score', 0)
                
                logger.info(f"BM25 search contributed {len(bm25_results)} results")
            except Exception as e:
                logger.error(f"BM25 search failed: {e}")
        
        # Execute graph search
        if use_graph:
            try:
                graph_results = self.graph_search.search(query, max_hops=2, top_k=top_k * 2)
                for result in graph_results:
                    result_id = result['id']
                    if result_id not in all_results:
                        all_results[result_id] = result
                        all_results[result_id]['scores'] = {}
                    all_results[result_id]['scores']['graph'] = result.get('centrality_score', 0)
                
                logger.info(f"Graph search contributed {len(graph_results)} results")
            except Exception as e:
                logger.error(f"Graph search failed: {e}")
        
        # Combine scores
        combined_results = self._combine_scores(list(all_results.values()))
        
        # Sort by combined score
        combined_results.sort(key=lambda x: x['combined_score'], reverse=True)
        
        logger.info(f"Hybrid search returned {len(combined_results[:top_k])} final results")
        return combined_results[:top_k]
    
    def _combine_scores(self, results: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Combine scores from different search methods.
        
        Args:
            results: List of results with individual scores
        
        Returns:
            Results with combined scores
        """
        # Normalize scores for each method
        normalized_results = self._normalize_scores(results)
        
        # Calculate combined score
        for result in normalized_results:
            scores = result.get('scores', {})
            
            combined_score = 0.0
            total_weight = 0.0
            
            for method, weight in self.weights.items():
                if method in scores:
                    combined_score += scores[method] * weight
                    total_weight += weight
            
            # Normalize by total weight used
            if total_weight > 0:
                result['combined_score'] = combined_score / total_weight
            else:
                result['combined_score'] = 0.0
        
        return normalized_results
    
    def _normalize_scores(self, results: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Normalize scores to 0-1 range for each method.
        
        Args:
            results: List of results with scores
        
        Returns:
            Results with normalized scores
        """
        # Find min/max for each score type
        score_ranges = {}
        
        for result in results:
            scores = result.get('scores', {})
            for method, score in scores.items():
                if method not in score_ranges:
                    score_ranges[method] = {'min': score, 'max': score}
                else:
                    score_ranges[method]['min'] = min(score_ranges[method]['min'], score)
                    score_ranges[method]['max'] = max(score_ranges[method]['max'], score)
        
        # Normalize scores
        for result in results:
            scores = result.get('scores', {})
            normalized_scores = {}
            
            for method, score in scores.items():
                score_range = score_ranges[method]
                range_diff = score_range['max'] - score_range['min']
                
                if range_diff > 0:
                    normalized_scores[method] = (score - score_range['min']) / range_diff
                else:
                    normalized_scores[method] = 1.0 if score > 0 else 0.0
            
            result['scores'] = normalized_scores
        
        return results
    
    def set_weights(self, vector: float = 0.5, bm25: float = 0.3, graph: float = 0.2) -> None:
        """
        Set custom weights for combining search results.
        
        Args:
            vector: Weight for vector search (default: 0.5)
            bm25: Weight for BM25 search (default: 0.3)
            graph: Weight for graph search (default: 0.2)
        """
        total = vector + bm25 + graph
        self.weights = {
            'vector': vector / total,
            'bm25': bm25 / total,
            'graph': graph / total
        }
        logger.info(f"Updated weights: {self.weights}")



class AdvancedReranker:
    """
    Advanced reranking with multi-factor scoring.
    
    Combines multiple signals to produce final ranking:
    - Similarity score (40%)
    - BM25 score (20%)
    - Graph centrality (15%)
    - Recency (15%)
    - User feedback (10%)
    """
    
    def __init__(self):
        """Initialize reranker with default weights."""
        self.weights = {
            'similarity': 0.40,
            'bm25': 0.20,
            'centrality': 0.15,
            'recency': 0.15,
            'feedback': 0.10
        }
        
        logger.info(f"Initialized reranker with weights: {self.weights}")
    
    def rerank(self, results: List[Dict[str, Any]], 
              top_n: int = 10) -> List[Dict[str, Any]]:
        """
        Rerank results using multi-factor scoring.
        
        Args:
            results: List of search results
            top_n: Number of top results to return
        
        Returns:
            Reranked results with final scores
        """
        logger.info(f"Reranking {len(results)} results...")
        
        if not results:
            return []
        
        # Calculate individual factor scores
        scored_results = []
        
        for result in results:
            scores = result.get('scores', {})
            metadata = result.get('metadata', {})
            
            # Extract individual scores
            similarity_score = scores.get('vector', 0.0)
            bm25_score = scores.get('bm25', 0.0)
            centrality_score = scores.get('graph', 0.0)
            
            # Calculate recency score
            recency_score = self._calculate_recency_score(metadata)
            
            # Calculate feedback score (placeholder - would come from user feedback data)
            feedback_score = self._calculate_feedback_score(metadata)
            
            # Calculate final score
            final_score = (
                similarity_score * self.weights['similarity'] +
                bm25_score * self.weights['bm25'] +
                centrality_score * self.weights['centrality'] +
                recency_score * self.weights['recency'] +
                feedback_score * self.weights['feedback']
            )
            
            # Add scores to result
            result['rerank_scores'] = {
                'similarity': similarity_score,
                'bm25': bm25_score,
                'centrality': centrality_score,
                'recency': recency_score,
                'feedback': feedback_score
            }
            result['final_score'] = final_score
            
            scored_results.append(result)
        
        # Sort by final score
        scored_results.sort(key=lambda x: x['final_score'], reverse=True)
        
        logger.info(f"Reranking complete, returning top {top_n} results")
        return scored_results[:top_n]
    
    def _calculate_recency_score(self, metadata: Dict[str, Any]) -> float:
        """
        Calculate recency score based on date.
        
        Args:
            metadata: Result metadata
        
        Returns:
            Recency score (0.0-1.0)
        """
        # Check for date fields
        date_fields = ['updated_at', 'created_at', 'date']
        result_date = None
        
        for field in date_fields:
            if field in metadata:
                result_date = metadata[field]
                break
        
        if not result_date:
            return 0.5  # Neutral score if no date
        
        try:
            # Parse date (assuming ISO format or similar)
            if isinstance(result_date, str):
                result_datetime = datetime.fromisoformat(result_date.replace('Z', '+00:00'))
            else:
                result_datetime = result_date
            
            # Calculate days ago
            days_ago = (datetime.now() - result_datetime.replace(tzinfo=None)).days
            
            # Score: 1.0 for today, decreasing to 0.0 for 365+ days ago
            if days_ago < 0:
                return 1.0
            elif days_ago > 365:
                return 0.0
            else:
                return 1.0 - (days_ago / 365.0)
                
        except Exception as e:
            logger.debug(f"Error calculating recency score: {e}")
            return 0.5
    
    def _calculate_feedback_score(self, metadata: Dict[str, Any]) -> float:
        """
        Calculate feedback score based on user interactions.
        
        Args:
            metadata: Result metadata
        
        Returns:
            Feedback score (0.0-1.0)
        """
        # Placeholder implementation
        # In production, this would query user feedback data:
        # - Click-through rate
        # - Helpfulness ratings
        # - Resolution success rate
        
        # For now, check if ticket is resolved (indicates successful solution)
        status = metadata.get('status', '').lower()
        
        if status == 'closed' or status == 'resolved':
            return 0.8  # Higher score for resolved tickets
        elif status == 'in progress':
            return 0.5
        else:
            return 0.3
    
    def set_weights(self, similarity: float = 0.40, bm25: float = 0.20,
                   centrality: float = 0.15, recency: float = 0.15,
                   feedback: float = 0.10) -> None:
        """
        Set custom weights for reranking factors.
        
        Args:
            similarity: Weight for similarity score
            bm25: Weight for BM25 score
            centrality: Weight for graph centrality
            recency: Weight for recency
            feedback: Weight for user feedback
        """
        total = similarity + bm25 + centrality + recency + feedback
        self.weights = {
            'similarity': similarity / total,
            'bm25': bm25 / total,
            'centrality': centrality / total,
            'recency': recency / total,
            'feedback': feedback / total
        }
        logger.info(f"Updated reranker weights: {self.weights}")


# Example usage
if __name__ == "__main__":
    # Configuration
    chromadb_path = os.path.join(
        os.path.dirname(os.path.dirname(__file__)),
        'chromadb_data'
    )
    
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'ticketportaal'
    }
    
    # Initialize hybrid retrieval
    print("Initializing hybrid retrieval system...")
    hybrid = HybridRetrieval(chromadb_path, db_config)
    
    # Example search
    query = "laptop start niet op blue screen error"
    print(f"\nSearching for: '{query}'")
    
    results = hybrid.search(query, top_k=5)
    
    print(f"\nFound {len(results)} results:")
    for i, result in enumerate(results, 1):
        print(f"\n{i}. {result['id']}")
        print(f"   Combined Score: {result['combined_score']:.3f}")
        print(f"   Scores: {result.get('scores', {})}")
        print(f"   Document: {result['document'][:100]}...")
    
    # Rerank results
    print("\n" + "="*60)
    print("Reranking results...")
    reranker = AdvancedReranker()
    reranked = reranker.rerank(results, top_n=5)
    
    print(f"\nReranked top {len(reranked)} results:")
    for i, result in enumerate(reranked, 1):
        print(f"\n{i}. {result['id']}")
        print(f"   Final Score: {result['final_score']:.3f}")
        print(f"   Rerank Scores: {result.get('rerank_scores', {})}")
        print(f"   Document: {result['document'][:100]}...")
