"""
Qdrant Vector Search
Dense vector search using Qdrant for production-ready performance
"""

import logging
from typing import Dict, List, Optional, Any
from sentence_transformers import SentenceTransformer
from qdrant_client import QdrantClient
from qdrant_client.models import Filter, FieldCondition, MatchValue

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [QDRANT] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


class QdrantVectorSearch:
    """
    Dense vector search using Qdrant.
    
    Performs semantic similarity search using embeddings stored in Qdrant.
    """
    
    def __init__(self, host: str = 'localhost', port: int = 6333, 
                 embedding_model_name: str = 'sentence-transformers/all-mpnet-base-v2'):
        """
        Initialize Qdrant vector search.
        
        Args:
            host: Qdrant server host
            port: Qdrant server port
            embedding_model_name: Sentence transformer model name
        """
        self.host = host
        self.port = port
        self.embedding_model_name = embedding_model_name
        
        logger.info(f"Initializing Qdrant vector search at {host}:{port}")
        
        # Initialize Qdrant client
        self.client = QdrantClient(host=host, port=port)
        
        # Load embedding model
        logger.info(f"Loading embedding model: {embedding_model_name}")
        self.embedding_model = SentenceTransformer(embedding_model_name)
        
        logger.info("Qdrant vector search initialized successfully")
    
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
            search_results = self.client.search(
                collection_name=collection_name,
                query_vector=query_embedding,
                limit=top_k,
                query_filter=query_filter
            )
            
            # Format results
            results = []
            for hit in search_results:
                result = {
                    'id': hit.id,
                    'score': hit.score,
                    'metadata': hit.payload,
                    'document': self._extract_document_text(hit.payload)
                }
                results.append(result)
            
            logger.info(f"Vector search returned {len(results)} results")
            return results
            
        except Exception as e:
            logger.error(f"Error in vector search: {e}")
            return []
    
    def _extract_document_text(self, payload: Dict[str, Any]) -> str:
        """
        Extract readable text from payload.
        
        Args:
            payload: Document payload from Qdrant
        
        Returns:
            Formatted document text
        """
        doc_type = payload.get('type', 'unknown')
        
        if doc_type == 'ticket':
            return f"Ticket {payload.get('ticket_number', 'N/A')}: {payload.get('title', '')} - {payload.get('description', '')}"
        elif doc_type == 'kb_article':
            return f"{payload.get('title', '')}: {payload.get('content', '')}"
        elif doc_type == 'ci_item':
            return f"{payload.get('name', '')} ({payload.get('type', '')}): {payload.get('notes', '')}"
        else:
            # Generic fallback
            return str(payload)
