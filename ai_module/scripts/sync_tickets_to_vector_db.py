"""
Enhanced Data Sync Script
Synchronizes ticket, KB, and CI data from MySQL to ChromaDB with rich data extraction.

This script handles:
- Querying tickets WITH dynamic fields (JSON aggregation)
- Querying tickets WITH comments (JSON aggregation)
- Querying tickets WITH related CI items (JSON aggregation)
- Semantic chunking (header, description, comments, resolution)
- Embedding generation with sentence-transformers
- ChromaDB upsert operations
- Knowledge graph population
- Comprehensive logging and error handling
"""

import sys
import os
import mysql.connector
import chromadb
from chromadb.config import Settings
from sentence_transformers import SentenceTransformer
import numpy as np
from typing import Dict, List, Optional, Any, Tuple
import json
import logging
from datetime import datetime, timedelta
from tqdm import tqdm
import argparse

# Add parent directory to path for imports
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Import our custom modules
from entity_extractor import EntityExtractor
from relationship_extractor import RelationshipExtractor
from knowledge_graph import KnowledgeGraph

# Configure logging
log_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'logs')
os.makedirs(log_dir, exist_ok=True)

log_file = os.path.join(log_dir, f"sync_{datetime.now().strftime('%Y-%m-%d')}.log")
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [SYNC] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S',
    handlers=[
        logging.FileHandler(log_file),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


class DataSyncPipeline:
    """
    Enhanced data synchronization pipeline with rich data extraction.
    
    Handles complete sync workflow:
    1. Query MySQL with JSON aggregation for dynamic fields, comments, CI relations
    2. Semantic chunking of ticket content
    3. Embedding generation with batch processing
    4. ChromaDB upsert with metadata
    5. Knowledge graph population with entities and relationships
    """
    
    def __init__(self, db_config: Dict[str, str], chromadb_path: str, 
                 embedding_model_name: str = 'sentence-transformers/all-mpnet-base-v2'):
        """
        Initialize sync pipeline.
        
        Args:
            db_config: MySQL connection configuration
            chromadb_path: Path to ChromaDB persistent storage
            embedding_model_name: Sentence transformer model name
        """
        self.db_config = db_config
        self.chromadb_path = chromadb_path
        self.embedding_model_name = embedding_model_name
        
        # Initialize components
        logger.info("Initializing sync pipeline...")
        self.embedding_model = None
        self.chroma_client = None
        self.entity_extractor = None
        self.relationship_extractor = None
        self.knowledge_graph = None
        
        # Statistics
        self.stats = {
            'tickets_synced': 0,
            'kb_synced': 0,
            'ci_synced': 0,
            'entities_extracted': 0,
            'relationships_created': 0,
            'errors': 0,
            'start_time': None,
            'end_time': None
        }
    
    def initialize(self) -> None:
        """Initialize all components."""
        try:
            # Load embedding model
            logger.info(f"Loading embedding model: {self.embedding_model_name}")
            self.embedding_model = SentenceTransformer(self.embedding_model_name)
            logger.info("Embedding model loaded successfully")
            
            # Initialize ChromaDB client
            logger.info(f"Initializing ChromaDB at: {self.chromadb_path}")
            self.chroma_client = chromadb.Client(Settings(
                persist_directory=self.chromadb_path,
                anonymized_telemetry=False
            ))
            logger.info("ChromaDB initialized successfully")
            
            # Initialize entity extractor
            logger.info("Initializing entity extractor...")
            self.entity_extractor = EntityExtractor()
            logger.info("Entity extractor initialized")
            
            # Initialize relationship extractor
            logger.info("Initializing relationship extractor...")
            self.relationship_extractor = RelationshipExtractor(
                self.db_config,
                similarity_model=self.embedding_model
            )
            logger.info("Relationship extractor initialized")
            
            # Initialize knowledge graph
            logger.info("Initializing knowledge graph...")
            self.knowledge_graph = KnowledgeGraph(self.db_config)
            logger.info("Knowledge graph initialized")
            
        except Exception as e:
            logger.error(f"Error initializing pipeline: {e}")
            raise
    
    def connect_db(self) -> mysql.connector.MySQLConnection:
        """Create database connection."""
        return mysql.connector.connect(**self.db_config)
    
    def get_tickets_with_rich_data(self, since_hours: Optional[int] = 24, 
                                   limit: Optional[int] = None) -> List[Dict[str, Any]]:
        """
        Query tickets WITH dynamic fields, comments, and related CI items.
        
        Args:
            since_hours: Get tickets updated in last N hours (None for all)
            limit: Maximum number of tickets to fetch
        
        Returns:
            List of ticket dictionaries with all related data
        """
        logger.info(f"Fetching tickets (since_hours={since_hours}, limit={limit})...")
        
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Build simple query without JSON_ARRAYAGG (for MySQL 5.7 compatibility)
            query = """
                SELECT 
                    t.ticket_id,
                    t.ticket_number,
                    t.title,
                    t.description,
                    t.priority,
                    t.status,
                    t.resolution,
                    t.created_at,
                    t.updated_at,
                    t.user_id,
                    u.first_name as user_first_name,
                    u.last_name as user_last_name,
                    u.email as user_email
                FROM tickets t
                LEFT JOIN users u ON t.user_id = u.user_id
            """
            
            # Add time filter if specified
            params = []
            if since_hours is not None:
                query += " WHERE t.updated_at >= DATE_SUB(NOW(), INTERVAL %s HOUR) OR t.created_at >= DATE_SUB(NOW(), INTERVAL %s HOUR)"
                params.extend([since_hours, since_hours])
            
            # Add ordering and limit
            query += " ORDER BY t.updated_at DESC"
            if limit:
                query += " LIMIT %s"
                params.append(limit)
            
            cursor.execute(query, params)
            tickets = cursor.fetchall()
            
            # Fetch related data for each ticket
            for ticket in tickets:
                ticket_id = ticket['ticket_id']
                
                # Fetch comments
                cursor.execute("""
                    SELECT tc.comment_id, tc.comment, tc.created_at, tc.user_id,
                           CONCAT(cu.first_name, ' ', cu.last_name) as user_name
                    FROM ticket_comments tc
                    JOIN users cu ON tc.user_id = cu.user_id
                    WHERE tc.ticket_id = %s
                    ORDER BY tc.created_at
                """, (ticket_id,))
                ticket['comments'] = cursor.fetchall() or []
                
                # Fetch dynamic fields (if table exists)
                try:
                    cursor.execute("""
                        SELECT cf.field_id, cf.field_name, cf.field_type, tfv.field_value
                        FROM ticket_field_values tfv
                        JOIN category_fields cf ON tfv.field_id = cf.field_id
                        WHERE tfv.ticket_id = %s
                    """, (ticket_id,))
                    ticket['dynamic_fields'] = cursor.fetchall() or []
                except:
                    ticket['dynamic_fields'] = []
                
                # Fetch related CIs (if table exists)
                try:
                    cursor.execute("""
                        SELECT ci.ci_id, ci.ci_number, ci.name as ci_name, 
                               ci.type as ci_type, ci.brand as ci_brand, ci.model as ci_model
                        FROM ticket_ci_relations tcr
                        JOIN configuration_items ci ON tcr.ci_id = ci.ci_id
                        WHERE tcr.ticket_id = %s
                    """, (ticket_id,))
                    ticket['related_cis'] = cursor.fetchall() or []
                except:
                        ticket['related_cis'] = []
                else:
                    ticket['related_cis'] = []
            
            logger.info(f"Fetched {len(tickets)} tickets")
            return tickets
            
        except Exception as e:
            logger.error(f"Error fetching tickets: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def chunk_ticket_semantically(self, ticket: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        Split ticket into semantic chunks for better embedding quality.
        
        Args:
            ticket: Ticket dictionary with all fields
        
        Returns:
            List of chunk dictionaries with content and metadata
        """
        chunks = []
        
        # Chunk 1: Header info (always include)
        header_content = f"Ticket {ticket['ticket_number']}: {ticket['title']}\n"
        header_content += f"Priority: {ticket['priority']}\n"
        header_content += f"Status: {ticket['status']}"
        
        chunks.append({
            'type': 'header',
            'content': header_content,
            'metadata': {
                'ticket_id': ticket['ticket_id'],
                'ticket_number': ticket['ticket_number'],
                'priority': ticket['priority'],
                'status': ticket['status'],
                'chunk_type': 'header'
            }
        })
        
        # Chunk 2: Description (if exists)
        if ticket.get('description') and ticket['description'].strip():
            chunks.append({
                'type': 'description',
                'content': f"Problem Description:\n{ticket['description']}",
                'metadata': {
                    'ticket_id': ticket['ticket_id'],
                    'ticket_number': ticket['ticket_number'],
                    'chunk_type': 'description'
                }
            })
        
        # Chunk 3: Dynamic fields (if exists)
        if ticket.get('dynamic_fields') and len(ticket['dynamic_fields']) > 0:
            fields_content = "Additional Details:\n"
            for field in ticket['dynamic_fields']:
                fields_content += f"- {field['field_name']}: {field['field_value']}\n"
            
            chunks.append({
                'type': 'dynamic_fields',
                'content': fields_content,
                'metadata': {
                    'ticket_id': ticket['ticket_id'],
                    'ticket_number': ticket['ticket_number'],
                    'chunk_type': 'dynamic_fields'
                }
            })
        
        # Chunk 4: Each comment separately (if exists)
        if ticket.get('comments') and len(ticket['comments']) > 0:
            for i, comment in enumerate(ticket['comments']):
                comment_content = f"Comment by {comment['user_name']} ({comment['created_at']}):\n{comment['comment']}"
                
                chunks.append({
                    'type': 'comment',
                    'content': comment_content,
                    'metadata': {
                        'ticket_id': ticket['ticket_id'],
                        'ticket_number': ticket['ticket_number'],
                        'chunk_type': 'comment',
                        'comment_index': i,
                        'comment_author': comment['user_name']
                    }
                })
        
        # Chunk 5: Resolution (if exists)
        if ticket.get('resolution') and ticket['resolution'].strip():
            chunks.append({
                'type': 'resolution',
                'content': f"Resolution:\n{ticket['resolution']}",
                'metadata': {
                    'ticket_id': ticket['ticket_id'],
                    'ticket_number': ticket['ticket_number'],
                    'chunk_type': 'resolution'
                }
            })
        
        # Chunk 6: Related CI items (if exists)
        if ticket.get('related_cis') and len(ticket['related_cis']) > 0:
            ci_content = "Related Configuration Items:\n"
            for ci in ticket['related_cis']:
                ci_content += f"- {ci['ci_number']}: {ci['ci_name']} ({ci['ci_type']})\n"
            
            chunks.append({
                'type': 'related_cis',
                'content': ci_content,
                'metadata': {
                    'ticket_id': ticket['ticket_id'],
                    'ticket_number': ticket['ticket_number'],
                    'chunk_type': 'related_cis'
                }
            })
        
        return chunks

    
    def generate_embeddings_batch(self, chunks: List[Dict[str, Any]], 
                                  batch_size: int = 100) -> List[np.ndarray]:
        """
        Generate embeddings for chunks with batch processing and progress bar.
        
        Args:
            chunks: List of chunk dictionaries
            batch_size: Number of chunks to process at once
        
        Returns:
            List of embedding vectors
        """
        logger.info(f"Generating embeddings for {len(chunks)} chunks (batch_size={batch_size})...")
        
        embeddings = []
        texts = [chunk['content'] for chunk in chunks]
        
        # Process in batches with progress bar
        with tqdm(total=len(texts), desc="Generating embeddings", unit="chunk") as pbar:
            for i in range(0, len(texts), batch_size):
                batch_texts = texts[i:i + batch_size]
                
                try:
                    batch_embeddings = self.embedding_model.encode(
                        batch_texts,
                        show_progress_bar=False,
                        convert_to_numpy=True
                    )
                    embeddings.extend(batch_embeddings)
                    pbar.update(len(batch_texts))
                    
                except Exception as e:
                    logger.error(f"Error generating embeddings for batch {i//batch_size}: {e}")
                    # Add zero vectors for failed batch
                    embedding_dim = 768  # all-mpnet-base-v2 dimension
                    for _ in range(len(batch_texts)):
                        embeddings.append(np.zeros(embedding_dim))
                    pbar.update(len(batch_texts))
                    self.stats['errors'] += 1
        
        logger.info(f"Generated {len(embeddings)} embeddings")
        return embeddings
    
    def upsert_to_chromadb(self, collection_name: str, chunks: List[Dict[str, Any]], 
                          embeddings: List[np.ndarray]) -> int:
        """
        Upsert documents with embeddings to ChromaDB collection.
        
        Args:
            collection_name: Name of ChromaDB collection
            chunks: List of chunk dictionaries
            embeddings: List of embedding vectors
        
        Returns:
            Number of documents upserted
        """
        logger.info(f"Upserting {len(chunks)} documents to collection '{collection_name}'...")
        
        try:
            # Get or create collection
            collection = self.chroma_client.get_or_create_collection(
                name=collection_name,
                metadata={"description": f"K&K Ticketportaal {collection_name}"}
            )
            
            # Prepare data for upsert
            ids = []
            documents = []
            metadatas = []
            embeddings_list = []
            
            for i, (chunk, embedding) in enumerate(zip(chunks, embeddings)):
                # Create unique ID based on collection type
                chunk_type = chunk['metadata']['chunk_type']
                chunk_index = chunk['metadata'].get('comment_index', 0)
                
                if 'ticket_id' in chunk['metadata']:
                    # Ticket chunk
                    ticket_id = chunk['metadata']['ticket_id']
                    doc_id = f"ticket_{ticket_id}_{chunk_type}_{chunk_index}"
                elif 'kb_id' in chunk['metadata']:
                    # KB article chunk
                    kb_id = chunk['metadata']['kb_id']
                    doc_id = f"kb_{kb_id}_{chunk_type}_{i}"
                elif 'ci_id' in chunk['metadata']:
                    # CI item chunk
                    ci_id = chunk['metadata']['ci_id']
                    doc_id = f"ci_{ci_id}_{chunk_type}_{i}"
                else:
                    # Fallback
                    doc_id = f"{collection_name}_{i}_{chunk_type}"
                
                ids.append(doc_id)
                documents.append(chunk['content'])
                metadatas.append(chunk['metadata'])
                embeddings_list.append(embedding.tolist())
            
            # Upsert in batches (ChromaDB has batch size limits)
            batch_size = 100
            upserted_count = 0
            
            with tqdm(total=len(ids), desc=f"Upserting to {collection_name}", unit="doc") as pbar:
                for i in range(0, len(ids), batch_size):
                    batch_ids = ids[i:i + batch_size]
                    batch_docs = documents[i:i + batch_size]
                    batch_meta = metadatas[i:i + batch_size]
                    batch_emb = embeddings_list[i:i + batch_size]
                    
                    try:
                        collection.upsert(
                            ids=batch_ids,
                            documents=batch_docs,
                            metadatas=batch_meta,
                            embeddings=batch_emb
                        )
                        upserted_count += len(batch_ids)
                        pbar.update(len(batch_ids))
                        
                    except Exception as e:
                        logger.error(f"Error upserting batch {i//batch_size}: {e}")
                        pbar.update(len(batch_ids))
                        self.stats['errors'] += 1
            
            logger.info(f"Successfully upserted {upserted_count} documents to '{collection_name}'")
            return upserted_count
            
        except Exception as e:
            logger.error(f"Error upserting to ChromaDB: {e}")
            self.stats['errors'] += 1
            return 0
    
    def populate_knowledge_graph(self, ticket: Dict[str, Any], 
                                entities: Dict[str, List[Dict[str, Any]]]) -> Tuple[int, int]:
        """
        Populate knowledge graph with nodes and edges from ticket.
        
        Args:
            ticket: Ticket dictionary
            entities: Extracted entities
        
        Returns:
            Tuple of (nodes_added, edges_added)
        """
        nodes_added = 0
        edges_added = 0
        
        try:
            # Add ticket node
            ticket_node_id = f"ticket_{ticket['ticket_id']}"
            self.knowledge_graph.add_node(
                ticket_node_id,
                'ticket',
                {
                    'ticket_number': ticket['ticket_number'],
                    'title': ticket['title'],
                    'priority': ticket['priority'],
                    'status': ticket['status'],
                    'created_at': str(ticket['created_at']),
                    'updated_at': str(ticket['updated_at'])
                },
                persist=True
            )
            nodes_added += 1
            
            # Add user node
            if ticket.get('user_id'):
                user_node_id = f"user_{ticket['user_id']}"
                self.knowledge_graph.add_node(
                    user_node_id,
                    'user',
                    {
                        'name': f"{ticket.get('user_first_name', '')} {ticket.get('user_last_name', '')}".strip(),
                        'email': ticket.get('user_email', '')
                    },
                    persist=True
                )
                nodes_added += 1
            
            # Add entity nodes
            for entity_type, entity_list in entities.items():
                for entity in entity_list:
                    entity_node_id = f"{entity_type.rstrip('s')}_{entity['text'].lower().replace(' ', '_')}"
                    self.knowledge_graph.add_node(
                        entity_node_id,
                        entity_type.rstrip('s'),
                        {
                            'name': entity['text'],
                            'confidence': entity['confidence'],
                            'label': entity.get('label', entity_type.upper())
                        },
                        persist=True
                    )
                    nodes_added += 1
            
            # Add CI nodes
            if ticket.get('related_cis'):
                for ci in ticket['related_cis']:
                    ci_node_id = f"ci_{ci['ci_id']}"
                    self.knowledge_graph.add_node(
                        ci_node_id,
                        'ci',
                        {
                            'ci_number': ci['ci_number'],
                            'name': ci['ci_name'],
                            'type': ci['ci_type'],
                            'brand': ci.get('ci_brand', ''),
                            'model': ci.get('ci_model', '')
                        },
                        persist=True
                    )
                    nodes_added += 1
            
            # Extract and add relationships
            edges = self.relationship_extractor.extract_ticket_relationships(ticket, entities)
            
            for edge in edges:
                is_valid, error = self.relationship_extractor.validate_edge(edge)
                if is_valid:
                    self.knowledge_graph.add_edge(
                        edge['source_id'],
                        edge['target_id'],
                        edge['edge_type'],
                        edge['confidence'],
                        edge.get('properties', {}),
                        persist=True
                    )
                    edges_added += 1
                else:
                    logger.warning(f"Invalid edge skipped: {error}")
            
            return nodes_added, edges_added
            
        except Exception as e:
            logger.error(f"Error populating knowledge graph for ticket {ticket['ticket_id']}: {e}")
            self.stats['errors'] += 1
            return nodes_added, edges_added
    
    def sync_tickets(self, since_hours: Optional[int] = 24, 
                    limit: Optional[int] = None) -> None:
        """
        Sync tickets to ChromaDB and knowledge graph.
        
        Args:
            since_hours: Get tickets updated in last N hours (None for all)
            limit: Maximum number of tickets to sync
        """
        logger.info("=" * 60)
        logger.info("Starting ticket sync...")
        logger.info("=" * 60)
        
        try:
            # Fetch tickets with rich data
            tickets = self.get_tickets_with_rich_data(since_hours, limit)
            
            if not tickets:
                logger.info("No tickets to sync")
                return
            
            # Process each ticket
            all_chunks = []
            
            logger.info(f"Processing {len(tickets)} tickets...")
            for ticket in tqdm(tickets, desc="Processing tickets", unit="ticket"):
                try:
                    # Semantic chunking
                    chunks = self.chunk_ticket_semantically(ticket)
                    all_chunks.extend(chunks)
                    
                    # Extract entities
                    ticket_data_for_extraction = {
                        'title': ticket['title'],
                        'description': ticket.get('description', ''),
                        'comments': [c['comment'] for c in ticket.get('comments', [])],
                        'resolution': ticket.get('resolution', ''),
                        'dynamic_fields': {f['field_name']: f['field_value'] for f in ticket.get('dynamic_fields', [])}
                    }
                    
                    entities = self.entity_extractor.extract_ticket_entities(ticket_data_for_extraction)
                    
                    # Populate knowledge graph
                    nodes_added, edges_added = self.populate_knowledge_graph(ticket, entities)
                    self.stats['entities_extracted'] += nodes_added
                    self.stats['relationships_created'] += edges_added
                    
                    self.stats['tickets_synced'] += 1
                    
                except Exception as e:
                    logger.error(f"Error processing ticket {ticket['ticket_id']}: {e}")
                    self.stats['errors'] += 1
            
            # Generate embeddings for all chunks
            if all_chunks:
                embeddings = self.generate_embeddings_batch(all_chunks, batch_size=100)
                
                # Upsert to ChromaDB
                self.upsert_to_chromadb('tickets', all_chunks, embeddings)
            
            logger.info(f"Ticket sync completed: {self.stats['tickets_synced']} tickets synced")
            
        except Exception as e:
            logger.error(f"Error in ticket sync: {e}")
            self.stats['errors'] += 1

    
    def get_kb_articles(self) -> List[Dict[str, Any]]:
        """
        Query published KB articles.
        
        Returns:
            List of KB article dictionaries
        """
        logger.info("Fetching KB articles...")
        
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            query = """
                SELECT 
                    kb_id,
                    title,
                    content,
                    tags,
                    category_id,
                    created_at,
                    updated_at,
                    author_id
                FROM knowledge_base
                WHERE is_published = 1
                ORDER BY updated_at DESC
            """
            
            cursor.execute(query)
            articles = cursor.fetchall()
            
            logger.info(f"Fetched {len(articles)} KB articles")
            return articles
            
        except Exception as e:
            logger.error(f"Error fetching KB articles: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def sync_kb_articles(self) -> None:
        """Sync KB articles to ChromaDB and knowledge graph."""
        logger.info("=" * 60)
        logger.info("Starting KB article sync...")
        logger.info("=" * 60)
        
        try:
            # Fetch KB articles
            articles = self.get_kb_articles()
            
            if not articles:
                logger.info("No KB articles to sync")
                return
            
            # Process each article
            chunks = []
            
            for article in tqdm(articles, desc="Processing KB articles", unit="article"):
                try:
                    # Create document content
                    content = f"KB Article: {article['title']}\n\n"
                    if article.get('tags'):
                        content += f"Tags: {article['tags']}\n\n"
                    content += article['content']
                    
                    # Create chunk
                    chunk = {
                        'type': 'kb_article',
                        'content': content,
                        'metadata': {
                            'kb_id': article['kb_id'],
                            'title': article['title'],
                            'tags': article.get('tags', ''),
                            'category_id': article.get('category_id'),
                            'updated_at': str(article['updated_at']),
                            'chunk_type': 'kb_article'
                        }
                    }
                    chunks.append(chunk)
                    
                    # Add KB node to knowledge graph
                    kb_node_id = f"kb_{article['kb_id']}"
                    self.knowledge_graph.add_node(
                        kb_node_id,
                        'kb_article',
                        {
                            'title': article['title'],
                            'tags': article.get('tags', ''),
                            'created_at': str(article['created_at']),
                            'updated_at': str(article['updated_at'])
                        },
                        persist=True
                    )
                    
                    # Extract entities from KB content
                    entities = self.entity_extractor.extract_entities(article['content'])
                    
                    # Add entity nodes and MENTIONS edges
                    for entity_type, entity_list in entities.items():
                        for entity in entity_list:
                            entity_node_id = f"{entity_type.rstrip('s')}_{entity['text'].lower().replace(' ', '_')}"
                            
                            # Add entity node
                            self.knowledge_graph.add_node(
                                entity_node_id,
                                entity_type.rstrip('s'),
                                {
                                    'name': entity['text'],
                                    'confidence': entity['confidence']
                                },
                                persist=True
                            )
                            
                            # Add MENTIONS edge
                            self.knowledge_graph.add_edge(
                                kb_node_id,
                                entity_node_id,
                                'MENTIONS',
                                entity['confidence'],
                                {'entity_type': entity_type},
                                persist=True
                            )
                    
                    # Add author relationship if available
                    if article.get('author_id'):
                        self.knowledge_graph.add_edge(
                            kb_node_id,
                            f"user_{article['author_id']}",
                            'CREATED_BY',
                            1.0,
                            {'created_at': str(article['created_at'])},
                            persist=True
                        )
                    
                    self.stats['kb_synced'] += 1
                    
                except Exception as e:
                    logger.error(f"Error processing KB article {article['kb_id']}: {e}")
                    self.stats['errors'] += 1
            
            # Generate embeddings and upsert
            if chunks:
                embeddings = self.generate_embeddings_batch(chunks, batch_size=100)
                self.upsert_to_chromadb('knowledge_base', chunks, embeddings)
            
            logger.info(f"KB sync completed: {self.stats['kb_synced']} articles synced")
            
        except Exception as e:
            logger.error(f"Error in KB sync: {e}")
            self.stats['errors'] += 1
    
    def get_ci_items(self) -> List[Dict[str, Any]]:
        """
        Query active CI items.
        
        Returns:
            List of CI item dictionaries
        """
        logger.info("Fetching CI items...")
        
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            query = """
                SELECT 
                    ci_id,
                    ci_number,
                    type,
                    name,
                    brand,
                    model,
                    serial_number,
                    category,
                    status,
                    location,
                    notes,
                    created_at,
                    updated_at
                FROM configuration_items
                WHERE status != 'Afgeschreven'
                ORDER BY updated_at DESC
            """
            
            cursor.execute(query)
            items = cursor.fetchall()
            
            logger.info(f"Fetched {len(items)} CI items")
            return items
            
        except Exception as e:
            logger.error(f"Error fetching CI items: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def sync_ci_items(self) -> None:
        """Sync CI items to ChromaDB and knowledge graph."""
        logger.info("=" * 60)
        logger.info("Starting CI item sync...")
        logger.info("=" * 60)
        
        try:
            # Fetch CI items
            items = self.get_ci_items()
            
            if not items:
                logger.info("No CI items to sync")
                return
            
            # Process each item
            chunks = []
            
            for item in tqdm(items, desc="Processing CI items", unit="item"):
                try:
                    # Create document content
                    content = f"CI: {item['ci_number']}\n"
                    content += f"Type: {item['type']}\n"
                    content += f"Name: {item['name']}\n"
                    if item.get('brand'):
                        content += f"Brand: {item['brand']}\n"
                    if item.get('model'):
                        content += f"Model: {item['model']}\n"
                    if item.get('serial_number'):
                        content += f"Serial Number: {item['serial_number']}\n"
                    if item.get('category'):
                        content += f"Category: {item['category']}\n"
                    if item.get('location'):
                        content += f"Location: {item['location']}\n"
                    if item.get('notes'):
                        content += f"\nNotes: {item['notes']}"
                    
                    # Create chunk
                    chunk = {
                        'type': 'ci_item',
                        'content': content,
                        'metadata': {
                            'ci_id': item['ci_id'],
                            'ci_number': item['ci_number'],
                            'type': item['type'],
                            'name': item['name'],
                            'brand': item.get('brand', ''),
                            'model': item.get('model', ''),
                            'status': item['status'],
                            'updated_at': str(item['updated_at']),
                            'chunk_type': 'ci_item'
                        }
                    }
                    chunks.append(chunk)
                    
                    # Add CI node to knowledge graph
                    ci_node_id = f"ci_{item['ci_id']}"
                    self.knowledge_graph.add_node(
                        ci_node_id,
                        'ci',
                        {
                            'ci_number': item['ci_number'],
                            'name': item['name'],
                            'type': item['type'],
                            'brand': item.get('brand', ''),
                            'model': item.get('model', ''),
                            'status': item['status'],
                            'location': item.get('location', ''),
                            'created_at': str(item['created_at']),
                            'updated_at': str(item['updated_at'])
                        },
                        persist=True
                    )
                    
                    # Extract relationships
                    ci_data = {
                        'ci_id': item['ci_id'],
                        'location': item.get('location'),
                        'department': item.get('category')  # Using category as department
                    }
                    edges = self.relationship_extractor.extract_ci_relationships(ci_data)
                    
                    for edge in edges:
                        is_valid, error = self.relationship_extractor.validate_edge(edge)
                        if is_valid:
                            self.knowledge_graph.add_edge(
                                edge['source_id'],
                                edge['target_id'],
                                edge['edge_type'],
                                edge['confidence'],
                                edge.get('properties', {}),
                                persist=True
                            )
                    
                    self.stats['ci_synced'] += 1
                    
                except Exception as e:
                    logger.error(f"Error processing CI item {item['ci_id']}: {e}")
                    self.stats['errors'] += 1
            
            # Generate embeddings and upsert
            if chunks:
                embeddings = self.generate_embeddings_batch(chunks, batch_size=100)
                self.upsert_to_chromadb('configuration_items', chunks, embeddings)
            
            logger.info(f"CI sync completed: {self.stats['ci_synced']} items synced")
            
        except Exception as e:
            logger.error(f"Error in CI sync: {e}")
            self.stats['errors'] += 1
    
    def run_full_sync(self, since_hours: Optional[int] = 24, 
                     ticket_limit: Optional[int] = None) -> None:
        """
        Run complete sync pipeline.
        
        Args:
            since_hours: Get tickets updated in last N hours (None for all)
            ticket_limit: Maximum number of tickets to sync
        """
        self.stats['start_time'] = datetime.now()
        
        logger.info("=" * 60)
        logger.info("STARTING FULL SYNC PIPELINE")
        logger.info("=" * 60)
        logger.info(f"Start time: {self.stats['start_time']}")
        logger.info(f"Parameters: since_hours={since_hours}, ticket_limit={ticket_limit}")
        
        try:
            # Initialize components
            self.initialize()
            
            # Sync tickets
            self.sync_tickets(since_hours, ticket_limit)
            
            # Sync KB articles
            self.sync_kb_articles()
            
            # Sync CI items
            self.sync_ci_items()
            
            # Final statistics
            self.stats['end_time'] = datetime.now()
            duration = (self.stats['end_time'] - self.stats['start_time']).total_seconds()
            
            logger.info("=" * 60)
            logger.info("SYNC COMPLETED SUCCESSFULLY")
            logger.info("=" * 60)
            logger.info(f"Duration: {duration:.1f} seconds")
            logger.info(f"Tickets synced: {self.stats['tickets_synced']}")
            logger.info(f"KB articles synced: {self.stats['kb_synced']}")
            logger.info(f"CI items synced: {self.stats['ci_synced']}")
            logger.info(f"Entities extracted: {self.stats['entities_extracted']}")
            logger.info(f"Relationships created: {self.stats['relationships_created']}")
            logger.info(f"Errors: {self.stats['errors']}")
            logger.info("=" * 60)
            
        except Exception as e:
            logger.error(f"Fatal error in sync pipeline: {e}")
            self.stats['errors'] += 1
            raise


def main():
    """Main entry point for sync script."""
    parser = argparse.ArgumentParser(description='Sync ticketportaal data to ChromaDB and knowledge graph')
    parser.add_argument('--since-hours', type=int, default=24,
                       help='Sync tickets updated in last N hours (default: 24, use 0 for all)')
    parser.add_argument('--limit', type=int, default=None,
                       help='Maximum number of tickets to sync (default: no limit)')
    parser.add_argument('--incremental', action='store_true',
                       help='Run incremental sync (last 1 hour)')
    
    args = parser.parse_args()
    
    # Adjust parameters for incremental sync
    if args.incremental:
        since_hours = 1
        logger.info("Running incremental sync (last 1 hour)")
    else:
        since_hours = args.since_hours if args.since_hours > 0 else None
    
    # Database configuration
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',  # XAMPP default
        'database': 'ticketportaal'
    }
    
    # ChromaDB path
    chromadb_path = os.path.join(
        os.path.dirname(os.path.dirname(__file__)),
        'chromadb_data'
    )
    
    # Create pipeline and run sync
    try:
        pipeline = DataSyncPipeline(db_config, chromadb_path)
        pipeline.run_full_sync(since_hours, args.limit)
        
        # Exit with success
        sys.exit(0)
        
    except Exception as e:
        logger.error(f"Sync failed: {e}")
        sys.exit(1)


if __name__ == "__main__":
    main()
