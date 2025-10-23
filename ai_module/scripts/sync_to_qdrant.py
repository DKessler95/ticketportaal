"""
Sync Ticketportaal Data to Qdrant Vector Database
Replaces ChromaDB with Qdrant for production-ready vector search
"""

import os
import sys
import argparse
from datetime import datetime
from typing import List, Dict, Any
import logging
from tqdm import tqdm

# Add parent directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from qdrant_client import QdrantClient
from qdrant_client.models import Distance, VectorParams, PointStruct
from sentence_transformers import SentenceTransformer
import pymysql

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [QDRANT] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)

# Configuration
QDRANT_HOST = "localhost"
QDRANT_PORT = 6333
EMBEDDING_MODEL = "sentence-transformers/all-mpnet-base-v2"
VECTOR_SIZE = 768
BATCH_SIZE = 100

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ticketportaal',
    'charset': 'utf8mb4'
}


class QdrantSync:
    """Sync data from MySQL to Qdrant vector database."""
    
    def __init__(self):
        """Initialize Qdrant sync."""
        logger.info("Initializing Qdrant sync...")
        
        # Initialize Qdrant client
        self.client = QdrantClient(host=QDRANT_HOST, port=QDRANT_PORT)
        logger.info(f"Connected to Qdrant at {QDRANT_HOST}:{QDRANT_PORT}")
        
        # Initialize embedding model
        logger.info(f"Loading embedding model: {EMBEDDING_MODEL}")
        self.embedding_model = SentenceTransformer(EMBEDDING_MODEL)
        logger.info("Embedding model loaded")
        
        # Initialize collections
        self._init_collections()
    
    def _init_collections(self):
        """Initialize Qdrant collections."""
        collections = ['tickets', 'knowledge_base', 'configuration_items']
        
        for collection_name in collections:
            try:
                # Check if collection exists
                self.client.get_collection(collection_name)
                logger.info(f"Collection '{collection_name}' already exists")
            except Exception:
                # Create collection
                logger.info(f"Creating collection '{collection_name}'...")
                self.client.create_collection(
                    collection_name=collection_name,
                    vectors_config=VectorParams(
                        size=VECTOR_SIZE,
                        distance=Distance.COSINE
                    )
                )
                logger.info(f"Collection '{collection_name}' created")
    
    def _get_db_connection(self):
        """Get MySQL database connection."""
        return pymysql.connect(**DB_CONFIG)
    
    def sync_tickets(self, since_hours: int = None, limit: int = None):
        """Sync tickets to Qdrant."""
        logger.info("=" * 60)
        logger.info("Starting ticket sync...")
        logger.info("=" * 60)
        
        conn = self._get_db_connection()
        cursor = conn.cursor(pymysql.cursors.DictCursor)
        
        # Build query
        query = """
            SELECT 
                t.ticket_id, t.ticket_number, t.title, t.description,
                t.status, t.priority,
                t.created_at, t.updated_at,
                CONCAT(u.first_name, ' ', u.last_name) as user_name
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.user_id
            WHERE 1=1
        """
        
        if since_hours:
            query += f" AND t.updated_at >= DATE_SUB(NOW(), INTERVAL {since_hours} HOUR)"
        
        query += " ORDER BY t.ticket_id"
        
        if limit:
            query += f" LIMIT {limit}"
        
        cursor.execute(query)
        tickets = cursor.fetchall()
        
        logger.info(f"Fetched {len(tickets)} tickets")
        
        if not tickets:
            logger.info("No tickets to sync")
            return 0
        
        # Process tickets in chunks
        points = []
        for ticket in tqdm(tickets, desc="Processing tickets"):
            # Create text for embedding
            text = f"""
            Ticket: {ticket['ticket_number']}
            Title: {ticket['title']}
            Description: {ticket['description'] or ''}
            Status: {ticket['status']}
            Priority: {ticket['priority']}
            User: {ticket['user_name'] or 'Unknown'}
            """.strip()
            
            # Generate embedding
            embedding = self.embedding_model.encode(text).tolist()
            
            # Create point
            point = PointStruct(
                id=ticket['ticket_id'],
                vector=embedding,
                payload={
                    'ticket_id': ticket['ticket_id'],
                    'ticket_number': ticket['ticket_number'],
                    'title': ticket['title'],
                    'description': ticket['description'],
                    'status': ticket['status'],
                    'priority': ticket['priority'],
                    'user_name': ticket['user_name'],
                    'created_at': ticket['created_at'].isoformat() if ticket['created_at'] else None,
                    'updated_at': ticket['updated_at'].isoformat() if ticket['updated_at'] else None,
                    'type': 'ticket'
                }
            )
            points.append(point)
        
        # Upload to Qdrant
        logger.info(f"Uploading {len(points)} tickets to Qdrant...")
        self.client.upsert(
            collection_name='tickets',
            points=points
        )
        
        logger.info(f"Ticket sync completed: {len(points)} tickets synced")
        
        cursor.close()
        conn.close()
        
        return len(points)
    
    def sync_kb_articles(self):
        """Sync KB articles to Qdrant."""
        logger.info("=" * 60)
        logger.info("Starting KB article sync...")
        logger.info("=" * 60)
        
        conn = self._get_db_connection()
        cursor = conn.cursor(pymysql.cursors.DictCursor)
        
        query = """
            SELECT 
                kb_id as id, title, content, category_id as category, tags,
                views, created_at, updated_at
            FROM knowledge_base
            WHERE is_published = 1
            ORDER BY kb_id
        """
        
        cursor.execute(query)
        articles = cursor.fetchall()
        
        logger.info(f"Fetched {len(articles)} KB articles")
        
        if not articles:
            logger.info("No KB articles to sync")
            return 0
        
        # Process articles
        points = []
        for article in tqdm(articles, desc="Processing KB articles"):
            # Create text for embedding
            text = f"""
            Title: {article['title']}
            Content: {article['content']}
            Category: {article['category']}
            Tags: {article['tags'] or ''}
            """.strip()
            
            # Generate embedding
            embedding = self.embedding_model.encode(text).tolist()
            
            # Create point
            point = PointStruct(
                id=article['id'],
                vector=embedding,
                payload={
                    'title': article['title'],
                    'content': article['content'],
                    'category': article['category'],
                    'tags': article['tags'],
                    'views': article['views'],
                    'created_at': article['created_at'].isoformat() if article['created_at'] else None,
                    'updated_at': article['updated_at'].isoformat() if article['updated_at'] else None,
                    'type': 'kb_article'
                }
            )
            points.append(point)
        
        # Upload to Qdrant
        logger.info(f"Uploading {len(points)} KB articles to Qdrant...")
        self.client.upsert(
            collection_name='knowledge_base',
            points=points
        )
        
        logger.info(f"KB sync completed: {len(points)} articles synced")
        
        cursor.close()
        conn.close()
        
        return len(points)
    
    def sync_ci_items(self):
        """Sync CI items to Qdrant."""
        logger.info("=" * 60)
        logger.info("Starting CI item sync...")
        logger.info("=" * 60)
        
        conn = self._get_db_connection()
        cursor = conn.cursor(pymysql.cursors.DictCursor)
        
        query = """
            SELECT 
                ci_id as id, name, type, brand as manufacturer, model,
                serial_number, status, location,
                notes, created_at, updated_at
            FROM configuration_items
            WHERE status != 'Afgeschreven'
            ORDER BY ci_id
        """
        
        cursor.execute(query)
        ci_items = cursor.fetchall()
        
        logger.info(f"Fetched {len(ci_items)} CI items")
        
        if not ci_items:
            logger.info("No CI items to sync")
            return 0
        
        # Process CI items
        points = []
        for ci in tqdm(ci_items, desc="Processing CI items"):
            # Create text for embedding
            text = f"""
            Name: {ci['name']}
            Type: {ci['type']}
            Manufacturer: {ci['manufacturer'] or ''}
            Model: {ci['model'] or ''}
            Serial: {ci['serial_number'] or ''}
            Status: {ci['status']}
            Location: {ci['location'] or ''}
            Notes: {ci['notes'] or ''}
            """.strip()
            
            # Generate embedding
            embedding = self.embedding_model.encode(text).tolist()
            
            # Create point
            point = PointStruct(
                id=ci['id'],
                vector=embedding,
                payload={
                    'name': ci['name'],
                    'type': ci['type'],
                    'manufacturer': ci['manufacturer'],
                    'model': ci['model'],
                    'serial_number': ci['serial_number'],
                    'status': ci['status'],
                    'location': ci['location'],
                    'notes': ci['notes'],
                    'created_at': ci['created_at'].isoformat() if ci['created_at'] else None,
                    'updated_at': ci['updated_at'].isoformat() if ci['updated_at'] else None,
                    'type_field': 'ci_item'
                }
            )
            points.append(point)
        
        # Upload to Qdrant
        logger.info(f"Uploading {len(points)} CI items to Qdrant...")
        self.client.upsert(
            collection_name='configuration_items',
            points=points
        )
        
        logger.info(f"CI sync completed: {len(points)} items synced")
        
        cursor.close()
        conn.close()
        
        return len(points)


def main():
    """Main sync function."""
    parser = argparse.ArgumentParser(description='Sync ticketportaal data to Qdrant')
    parser.add_argument('--since-hours', type=int, default=0,
                       help='Sync tickets updated in last N hours (default: 0 = all)')
    parser.add_argument('--limit', type=int, default=None,
                       help='Maximum number of tickets to sync')
    parser.add_argument('--incremental', action='store_true',
                       help='Run incremental sync (last 1 hour)')
    
    args = parser.parse_args()
    
    # Handle incremental flag
    if args.incremental:
        args.since_hours = 1
    
    logger.info("=" * 60)
    logger.info("QDRANT SYNC PIPELINE")
    logger.info("=" * 60)
    logger.info(f"Start time: {datetime.now()}")
    logger.info(f"Parameters: since_hours={args.since_hours}, limit={args.limit}")
    logger.info("")
    
    start_time = datetime.now()
    
    try:
        # Initialize sync
        sync = QdrantSync()
        
        # Sync data
        tickets_synced = sync.sync_tickets(args.since_hours, args.limit)
        kb_synced = sync.sync_kb_articles()
        ci_synced = sync.sync_ci_items()
        
        # Summary
        duration = (datetime.now() - start_time).total_seconds()
        
        logger.info("=" * 60)
        logger.info("SYNC COMPLETED SUCCESSFULLY")
        logger.info("=" * 60)
        logger.info(f"Duration: {duration:.1f} seconds")
        logger.info(f"Tickets synced: {tickets_synced}")
        logger.info(f"KB articles synced: {kb_synced}")
        logger.info(f"CI items synced: {ci_synced}")
        logger.info(f"Total: {tickets_synced + kb_synced + ci_synced}")
        logger.info("=" * 60)
        
    except Exception as e:
        logger.error(f"Sync failed: {e}", exc_info=True)
        sys.exit(1)


if __name__ == '__main__':
    main()
