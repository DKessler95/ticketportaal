"""
Knowledge Extraction Pipeline
Integrated pipeline for entity and relationship extraction.

This module handles:
- Coordinated entity and relationship extraction
- Batch processing of tickets
- Progress tracking and statistics
- Error handling and recovery
"""

import sys
import os
from typing import Dict, List, Any, Optional
import mysql.connector
from knowledge_graph import KnowledgeGraph
from entity_extractor import EntityExtractor
from relationship_extractor import RelationshipExtractor
import logging
from datetime import datetime
import json
import time

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [PIPELINE] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


class KnowledgeExtractionPipeline:
    """
    Integrated pipeline for knowledge extraction from tickets.
    
    Coordinates entity extraction and relationship extraction
    to build a comprehensive knowledge graph.
    """
    
    def __init__(self, db_config: Dict[str, str], model_name: str = "nl_core_news_sm"):
        """
        Initialize extraction pipeline.
        
        Args:
            db_config: MySQL database configuration
            model_name: spaCy model name
        """
        self.db_config = db_config
        self.kg = KnowledgeGraph(db_config)
        self.entity_extractor = EntityExtractor(db_config, model_name)
        self.relationship_extractor = RelationshipExtractor(db_config, model_name)
        
        # Processing statistics
        self.stats = {
            'tickets_processed': 0,
            'entities_extracted': 0,
            'relationships_extracted': 0,
            'errors': 0,
            'start_time': None,
            'end_time': None
        }
    
    def process_single_ticket(self, ticket_id: int, ticket_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Process a single ticket for complete knowledge extraction.
        
        Args:
            ticket_id: Ticket ID
            ticket_data: Ticket data
        
        Returns:
            Processing results
        """
        logger.info(f"Processing ticket {ticket_id} for knowledge extraction")
        
        results = {
            'ticket_id': ticket_id,
            'entities': [],
            'relationships': [],
            'success': False,
            'error': None
        }
        
        try:
            # Step 1: Extract entities
            logger.debug(f"Extracting entities from ticket {ticket_id}")
            entities = self.entity_extractor.process_ticket(ticket_id, ticket_data)
            results['entities'] = entities
            
            # Step 2: Save entities to knowledge graph
            if entities:
                self.entity_extractor.save_entities_to_graph(entities)
                logger.debug(f"Saved {len(entities)} entities for ticket {ticket_id}")
            
            # Step 3: Extract relationships (only if we have enough entities)
            if len(entities) >= 2:
                logger.debug(f"Extracting relationships from ticket {ticket_id}")
                relationships = self.relationship_extractor.process_ticket_relationships(ticket_id, ticket_data)
                results['relationships'] = relationships
                
                # Step 4: Save relationships to knowledge graph
                if relationships:
                    self.relationship_extractor.save_relationships_to_graph(relationships)
                    logger.debug(f"Saved {len(relationships)} relationships for ticket {ticket_id}")
            else:
                logger.debug(f"Not enough entities ({len(entities)}) for relationship extraction in ticket {ticket_id}")
            
            results['success'] = True
            
            # Update statistics
            self.stats['tickets_processed'] += 1
            self.stats['entities_extracted'] += len(entities)
            self.stats['relationships_extracted'] += len(results['relationships'])
            
            logger.info(f"Successfully processed ticket {ticket_id}: {len(entities)} entities, {len(results['relationships'])} relationships")
            
        except Exception as e:
            logger.error(f"Error processing ticket {ticket_id}: {e}")
            results['error'] = str(e)
            self.stats['errors'] += 1
        
        return results
    
    def process_tickets_batch(self, limit: int = 100, offset: int = 0, 
                             filter_conditions: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """
        Process a batch of tickets for knowledge extraction.
        
        Args:
            limit: Number of tickets to process
            offset: Starting offset
            filter_conditions: Optional filters for ticket selection
        
        Returns:
            Batch processing results
        """
        logger.info(f"Starting batch processing: {limit} tickets (offset: {offset})")
        
        self.stats['start_time'] = datetime.now()
        
        conn = mysql.connector.connect(**self.db_config)
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Build query with optional filters
            query = """
                SELECT ticket_id, ticket_number, title, description, resolution, status
                FROM tickets
                WHERE 1=1
            """
            params = []
            
            if filter_conditions:
                if 'status' in filter_conditions:
                    query += " AND status = %s"
                    params.append(filter_conditions['status'])
                
                if 'category_id' in filter_conditions:
                    query += " AND category_id = %s"
                    params.append(filter_conditions['category_id'])
                
                if 'updated_after' in filter_conditions:
                    query += " AND updated_at >= %s"
                    params.append(filter_conditions['updated_after'])
            
            query += " ORDER BY ticket_id LIMIT %s OFFSET %s"
            params.extend([limit, offset])
            
            cursor.execute(query, params)
            tickets = cursor.fetchall()
            
            logger.info(f"Retrieved {len(tickets)} tickets for processing")
            
            # Process each ticket
            batch_results = []
            for ticket in tickets:
                result = self.process_single_ticket(ticket['ticket_id'], ticket)
                batch_results.append(result)
                
                # Log progress every 10 tickets
                if self.stats['tickets_processed'] % 10 == 0:
                    logger.info(f"Progress: {self.stats['tickets_processed']}/{len(tickets)} tickets processed")
            
            self.stats['end_time'] = datetime.now()
            
            # Calculate duration
            duration = (self.stats['end_time'] - self.stats['start_time']).total_seconds()
            
            summary = {
                'batch_size': len(tickets),
                'tickets_processed': self.stats['tickets_processed'],
                'entities_extracted': self.stats['entities_extracted'],
                'relationships_extracted': self.stats['relationships_extracted'],
                'errors': self.stats['errors'],
                'duration_seconds': duration,
                'tickets_per_second': self.stats['tickets_processed'] / duration if duration > 0 else 0,
                'success_rate': (self.stats['tickets_processed'] - self.stats['errors']) / self.stats['tickets_processed'] if self.stats['tickets_processed'] > 0 else 0
            }
            
            logger.info(f"Batch processing complete: {summary}")
            
            return summary
            
        except Exception as e:
            logger.error(f"Error in batch processing: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def process_all_tickets(self, batch_size: int = 100) -> Dict[str, Any]:
        """
        Process all tickets in the database.
        
        Args:
            batch_size: Number of tickets per batch
        
        Returns:
            Overall processing statistics
        """
        logger.info("Starting full ticket processing")
        
        conn = mysql.connector.connect(**self.db_config)
        cursor = conn.cursor()
        
        try:
            # Get total ticket count
            cursor.execute("SELECT COUNT(*) FROM tickets")
            total_tickets = cursor.fetchone()[0]
            
            logger.info(f"Total tickets to process: {total_tickets}")
            
            # Reset statistics
            self.stats = {
                'tickets_processed': 0,
                'entities_extracted': 0,
                'relationships_extracted': 0,
                'errors': 0,
                'start_time': datetime.now(),
                'end_time': None
            }
            
            # Process in batches
            offset = 0
            batch_count = 0
            
            while offset < total_tickets:
                batch_count += 1
                logger.info(f"Processing batch {batch_count} (offset: {offset})")
                
                batch_result = self.process_tickets_batch(limit=batch_size, offset=offset)
                
                offset += batch_size
                
                # Small delay between batches to avoid overload
                time.sleep(1)
            
            self.stats['end_time'] = datetime.now()
            
            # Calculate final statistics
            duration = (self.stats['end_time'] - self.stats['start_time']).total_seconds()
            
            final_stats = {
                'total_tickets': total_tickets,
                'tickets_processed': self.stats['tickets_processed'],
                'entities_extracted': self.stats['entities_extracted'],
                'relationships_extracted': self.stats['relationships_extracted'],
                'errors': self.stats['errors'],
                'duration_seconds': duration,
                'duration_minutes': duration / 60,
                'tickets_per_second': self.stats['tickets_processed'] / duration if duration > 0 else 0,
                'success_rate': (self.stats['tickets_processed'] - self.stats['errors']) / self.stats['tickets_processed'] if self.stats['tickets_processed'] > 0 else 0
            }
            
            logger.info(f"Full processing complete: {final_stats}")
            
            return final_stats
            
        except Exception as e:
            logger.error(f"Error in full processing: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def get_pipeline_stats(self) -> Dict[str, Any]:
        """
        Get comprehensive pipeline statistics.
        
        Returns:
            Statistics dictionary
        """
        # Get entity extraction stats
        entity_stats = self.entity_extractor.get_extraction_stats()
        
        # Get relationship extraction stats
        relationship_stats = self.relationship_extractor.get_relationship_stats()
        
        # Get knowledge graph stats
        conn = mysql.connector.connect(**self.db_config)
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Total nodes and edges
            cursor.execute("SELECT COUNT(*) as count FROM graph_nodes")
            total_nodes = cursor.fetchone()['count']
            
            cursor.execute("SELECT COUNT(*) as count FROM graph_edges")
            total_edges = cursor.fetchone()['count']
            
            # Tickets with entities
            cursor.execute("""
                SELECT COUNT(DISTINCT JSON_EXTRACT(properties, '$.source_ticket')) as count
                FROM graph_nodes
                WHERE JSON_EXTRACT(properties, '$.source_ticket') IS NOT NULL
            """)
            tickets_with_entities = cursor.fetchone()['count']
            
            # Average entities per ticket
            cursor.execute("""
                SELECT AVG(entity_count) as avg_entities
                FROM (
                    SELECT JSON_EXTRACT(properties, '$.source_ticket') as ticket_id, COUNT(*) as entity_count
                    FROM graph_nodes
                    WHERE JSON_EXTRACT(properties, '$.source_ticket') IS NOT NULL
                    GROUP BY JSON_EXTRACT(properties, '$.source_ticket')
                ) as ticket_entities
            """)
            avg_entities_result = cursor.fetchone()
            avg_entities = float(avg_entities_result['avg_entities']) if avg_entities_result['avg_entities'] else 0
            
            # Average relationships per ticket
            cursor.execute("""
                SELECT AVG(rel_count) as avg_relationships
                FROM (
                    SELECT JSON_EXTRACT(properties, '$.source_ticket') as ticket_id, COUNT(*) as rel_count
                    FROM graph_edges
                    WHERE JSON_EXTRACT(properties, '$.source_ticket') IS NOT NULL
                    GROUP BY JSON_EXTRACT(properties, '$.source_ticket')
                ) as ticket_relationships
            """)
            avg_relationships_result = cursor.fetchone()
            avg_relationships = float(avg_relationships_result['avg_relationships']) if avg_relationships_result['avg_relationships'] else 0
            
            return {
                'knowledge_graph': {
                    'total_nodes': total_nodes,
                    'total_edges': total_edges,
                    'tickets_with_entities': tickets_with_entities,
                    'avg_entities_per_ticket': round(avg_entities, 2),
                    'avg_relationships_per_ticket': round(avg_relationships, 2)
                },
                'entity_extraction': entity_stats,
                'relationship_extraction': relationship_stats,
                'processing_stats': self.stats
            }
            
        except Exception as e:
            logger.error(f"Error getting pipeline stats: {e}")
            return {}
        finally:
            cursor.close()
            conn.close()


def main():
    """
    Main function for command-line usage.
    """
    import argparse
    
    parser = argparse.ArgumentParser(description='Knowledge Extraction Pipeline')
    parser.add_argument('--host', default='localhost', help='Database host')
    parser.add_argument('--user', default='root', help='Database user')
    parser.add_argument('--password', default='', help='Database password')
    parser.add_argument('--database', default='ticketportaal', help='Database name')
    parser.add_argument('--model', default='nl_core_news_sm', help='spaCy model name')
    parser.add_argument('--batch-size', type=int, default=100, help='Batch size for processing')
    parser.add_argument('--limit', type=int, help='Limit number of tickets to process')
    parser.add_argument('--offset', type=int, default=0, help='Starting offset')
    parser.add_argument('--all', action='store_true', help='Process all tickets')
    parser.add_argument('--stats', action='store_true', help='Show pipeline statistics')
    
    args = parser.parse_args()
    
    # Database configuration
    db_config = {
        'host': args.host,
        'user': args.user,
        'password': args.password,
        'database': args.database
    }
    
    # Initialize pipeline
    pipeline = KnowledgeExtractionPipeline(db_config, args.model)
    
    if args.stats:
        # Show statistics
        print("\n=== Knowledge Extraction Pipeline Statistics ===\n")
        stats = pipeline.get_pipeline_stats()
        print(json.dumps(stats, indent=2))
    
    elif args.all:
        # Process all tickets
        print(f"\n=== Processing All Tickets (batch size: {args.batch_size}) ===\n")
        results = pipeline.process_all_tickets(batch_size=args.batch_size)
        print("\n=== Processing Complete ===\n")
        print(json.dumps(results, indent=2))
    
    else:
        # Process batch
        limit = args.limit if args.limit else args.batch_size
        print(f"\n=== Processing Batch (limit: {limit}, offset: {args.offset}) ===\n")
        results = pipeline.process_tickets_batch(limit=limit, offset=args.offset)
        print("\n=== Batch Complete ===\n")
        print(json.dumps(results, indent=2))


if __name__ == "__main__":
    main()
