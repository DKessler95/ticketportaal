"""
Validation Sampler
Samples tickets for human-in-the-loop validation of entity and relationship extraction.

This module handles:
- Sampling 100 representative tickets for manual review
- Stratified sampling across categories and priorities
- Storing validation samples in database
- Tracking validation progress
"""

import mysql.connector
from typing import Dict, List, Any, Optional
import logging
from datetime import datetime
import json
import random

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [SAMPLER] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


class ValidationSampler:
    """
    Samples tickets for validation of extraction quality.
    
    Uses stratified sampling to ensure representative coverage
    across categories, priorities, and ticket characteristics.
    """
    
    def __init__(self, db_config: Dict[str, str]):
        """
        Initialize validation sampler.
        
        Args:
            db_config: MySQL connection configuration
        """
        self.db_config = db_config
    
    def connect_db(self) -> mysql.connector.MySQLConnection:
        """Create database connection."""
        return mysql.connector.connect(**self.db_config)
    
    def create_validation_tables(self) -> None:
        """Create tables for storing validation data."""
        conn = self.connect_db()
        cursor = conn.cursor()
        
        try:
            # Table for validation samples
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS validation_samples (
                    sample_id INT AUTO_INCREMENT PRIMARY KEY,
                    ticket_id INT NOT NULL,
                    ticket_number VARCHAR(50),
                    category VARCHAR(100),
                    priority VARCHAR(50),
                    sampled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    validated BOOLEAN DEFAULT FALSE,
                    validated_at TIMESTAMP NULL,
                    validated_by INT NULL,
                    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
                    UNIQUE KEY unique_ticket_sample (ticket_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            """)
            
            # Table for entity validations
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS entity_validations (
                    validation_id INT AUTO_INCREMENT PRIMARY KEY,
                    sample_id INT NOT NULL,
                    entity_text VARCHAR(500),
                    entity_type VARCHAR(50),
                    extracted_confidence DECIMAL(3,2),
                    is_correct BOOLEAN NULL COMMENT 'NULL=not reviewed, TRUE=correct, FALSE=incorrect',
                    should_be_type VARCHAR(50) NULL COMMENT 'Correct type if is_correct=FALSE',
                    notes TEXT NULL,
                    validated_at TIMESTAMP NULL,
                    FOREIGN KEY (sample_id) REFERENCES validation_samples(sample_id) ON DELETE CASCADE,
                    INDEX idx_sample (sample_id),
                    INDEX idx_entity_type (entity_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            """)
            
            # Table for relationship validations
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS relationship_validations (
                    validation_id INT AUTO_INCREMENT PRIMARY KEY,
                    sample_id INT NOT NULL,
                    source_entity VARCHAR(500),
                    target_entity VARCHAR(500),
                    edge_type VARCHAR(50),
                    extracted_confidence DECIMAL(3,2),
                    is_correct BOOLEAN NULL COMMENT 'NULL=not reviewed, TRUE=correct, FALSE=incorrect',
                    should_be_type VARCHAR(50) NULL COMMENT 'Correct type if is_correct=FALSE',
                    notes TEXT NULL,
                    validated_at TIMESTAMP NULL,
                    FOREIGN KEY (sample_id) REFERENCES validation_samples(sample_id) ON DELETE CASCADE,
                    INDEX idx_sample (sample_id),
                    INDEX idx_edge_type (edge_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            """)
            
            conn.commit()
            logger.info("Validation tables created successfully")
            
        except Exception as e:
            logger.error(f"Error creating validation tables: {e}")
            conn.rollback()
            raise
        finally:
            cursor.close()
            conn.close()
    
    def get_stratified_sample(self, total_samples: int = 100) -> List[Dict[str, Any]]:
        """
        Get stratified sample of tickets for validation.
        
        Args:
            total_samples: Total number of tickets to sample
        
        Returns:
            List of sampled ticket data
        """
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Get category distribution
            cursor.execute("""
                SELECT category, COUNT(*) as count
                FROM tickets
                WHERE status IN ('Open', 'In Progress', 'Closed')
                GROUP BY category
            """)
            category_counts = cursor.fetchall()
            
            # Calculate samples per category (proportional)
            total_tickets = sum(c['count'] for c in category_counts)
            samples_per_category = {}
            
            for cat in category_counts:
                proportion = cat['count'] / total_tickets
                samples = max(1, int(proportion * total_samples))  # At least 1 per category
                samples_per_category[cat['category']] = samples
            
            logger.info(f"Sampling strategy: {samples_per_category}")
            
            # Sample tickets from each category
            sampled_tickets = []
            
            for category, sample_count in samples_per_category.items():
                # Get tickets with knowledge graph data
                cursor.execute("""
                    SELECT DISTINCT
                        t.ticket_id,
                        t.ticket_number,
                        t.title,
                        t.description,
                        t.category,
                        t.priority,
                        t.status,
                        COUNT(DISTINCT gn.node_id) as entity_count,
                        COUNT(DISTINCT ge.edge_id) as relationship_count
                    FROM tickets t
                    LEFT JOIN graph_nodes gn ON gn.node_id = CONCAT('ticket_', t.ticket_id)
                        OR JSON_EXTRACT(gn.properties, '$.source_ticket_id') = t.ticket_id
                    LEFT JOIN graph_edges ge ON ge.source_id = CONCAT('ticket_', t.ticket_id)
                        OR ge.target_id = CONCAT('ticket_', t.ticket_id)
                    WHERE t.category = %s
                    AND t.status IN ('Open', 'In Progress', 'Closed')
                    AND t.description IS NOT NULL
                    AND LENGTH(t.description) > 50
                    GROUP BY t.ticket_id
                    HAVING entity_count > 0 OR relationship_count > 0
                    ORDER BY RAND()
                    LIMIT %s
                """, (category, sample_count))
                
                category_tickets = cursor.fetchall()
                sampled_tickets.extend(category_tickets)
                
                logger.info(f"Sampled {len(category_tickets)} tickets from category '{category}'")
            
            # If we don't have enough, sample more randomly
            if len(sampled_tickets) < total_samples:
                remaining = total_samples - len(sampled_tickets)
                sampled_ids = [t['ticket_id'] for t in sampled_tickets]
                
                placeholders = ','.join(['%s'] * len(sampled_ids)) if sampled_ids else '0'
                
                cursor.execute(f"""
                    SELECT DISTINCT
                        t.ticket_id,
                        t.ticket_number,
                        t.title,
                        t.description,
                        t.category,
                        t.priority,
                        t.status,
                        COUNT(DISTINCT gn.node_id) as entity_count,
                        COUNT(DISTINCT ge.edge_id) as relationship_count
                    FROM tickets t
                    LEFT JOIN graph_nodes gn ON gn.node_id = CONCAT('ticket_', t.ticket_id)
                        OR JSON_EXTRACT(gn.properties, '$.source_ticket_id') = t.ticket_id
                    LEFT JOIN graph_edges ge ON ge.source_id = CONCAT('ticket_', t.ticket_id)
                        OR ge.target_id = CONCAT('ticket_', t.ticket_id)
                    WHERE t.ticket_id NOT IN ({placeholders})
                    AND t.status IN ('Open', 'In Progress', 'Closed')
                    AND t.description IS NOT NULL
                    AND LENGTH(t.description) > 50
                    GROUP BY t.ticket_id
                    HAVING entity_count > 0 OR relationship_count > 0
                    ORDER BY RAND()
                    LIMIT %s
                """, sampled_ids + [remaining])
                
                additional_tickets = cursor.fetchall()
                sampled_tickets.extend(additional_tickets)
                
                logger.info(f"Added {len(additional_tickets)} additional random tickets")
            
            logger.info(f"Total sampled tickets: {len(sampled_tickets)}")
            
            return sampled_tickets[:total_samples]  # Ensure we don't exceed limit
            
        except Exception as e:
            logger.error(f"Error getting stratified sample: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def save_validation_samples(self, tickets: List[Dict[str, Any]]) -> int:
        """
        Save sampled tickets to validation_samples table.
        
        Args:
            tickets: List of sampled ticket data
        
        Returns:
            Number of samples saved
        """
        conn = self.connect_db()
        cursor = conn.cursor()
        
        try:
            saved_count = 0
            
            for ticket in tickets:
                # Insert sample
                cursor.execute("""
                    INSERT INTO validation_samples (ticket_id, ticket_number, category, priority)
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        ticket_number = VALUES(ticket_number),
                        category = VALUES(category),
                        priority = VALUES(priority)
                """, (
                    ticket['ticket_id'],
                    ticket['ticket_number'],
                    ticket['category'],
                    ticket['priority']
                ))
                
                sample_id = cursor.lastrowid
                
                # Get extracted entities for this ticket
                cursor.execute("""
                    SELECT node_id, node_type, properties
                    FROM graph_nodes
                    WHERE JSON_EXTRACT(properties, '$.source_ticket_id') = %s
                """, (ticket['ticket_id'],))
                
                entities = cursor.fetchall()
                
                # Save entities for validation
                for entity in entities:
                    properties = json.loads(entity[2]) if entity[2] else {}
                    
                    cursor.execute("""
                        INSERT INTO entity_validations 
                        (sample_id, entity_text, entity_type, extracted_confidence)
                        VALUES (%s, %s, %s, %s)
                    """, (
                        sample_id,
                        properties.get('name', entity[0]),
                        entity[1],
                        properties.get('confidence', 0.8)
                    ))
                
                # Get extracted relationships for this ticket
                cursor.execute("""
                    SELECT source_id, target_id, edge_type, confidence, properties
                    FROM graph_edges
                    WHERE source_id = %s OR target_id = %s
                """, (f"ticket_{ticket['ticket_id']}", f"ticket_{ticket['ticket_id']}"))
                
                relationships = cursor.fetchall()
                
                # Save relationships for validation
                for rel in relationships:
                    cursor.execute("""
                        INSERT INTO relationship_validations
                        (sample_id, source_entity, target_entity, edge_type, extracted_confidence)
                        VALUES (%s, %s, %s, %s, %s)
                    """, (
                        sample_id,
                        rel[0],
                        rel[1],
                        rel[2],
                        rel[3]
                    ))
                
                saved_count += 1
            
            conn.commit()
            logger.info(f"Saved {saved_count} validation samples")
            
            return saved_count
            
        except Exception as e:
            logger.error(f"Error saving validation samples: {e}")
            conn.rollback()
            raise
        finally:
            cursor.close()
            conn.close()
    
    def generate_validation_batch(self, total_samples: int = 100) -> Dict[str, Any]:
        """
        Generate a complete validation batch.
        
        Args:
            total_samples: Number of tickets to sample
        
        Returns:
            Summary of generated batch
        """
        logger.info(f"Generating validation batch of {total_samples} tickets")
        
        # Create tables if they don't exist
        self.create_validation_tables()
        
        # Get stratified sample
        tickets = self.get_stratified_sample(total_samples)
        
        # Save samples
        saved_count = self.save_validation_samples(tickets)
        
        # Get summary statistics
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            cursor.execute("""
                SELECT 
                    COUNT(*) as total_samples,
                    SUM(CASE WHEN validated = TRUE THEN 1 ELSE 0 END) as validated_count,
                    COUNT(DISTINCT category) as categories_covered
                FROM validation_samples
            """)
            sample_stats = cursor.fetchone()
            
            cursor.execute("""
                SELECT COUNT(*) as total_entities
                FROM entity_validations
            """)
            entity_stats = cursor.fetchone()
            
            cursor.execute("""
                SELECT COUNT(*) as total_relationships
                FROM relationship_validations
            """)
            relationship_stats = cursor.fetchone()
            
            summary = {
                'total_samples': sample_stats['total_samples'],
                'validated_count': sample_stats['validated_count'],
                'pending_count': sample_stats['total_samples'] - sample_stats['validated_count'],
                'categories_covered': sample_stats['categories_covered'],
                'total_entities': entity_stats['total_entities'],
                'total_relationships': relationship_stats['total_relationships'],
                'generated_at': datetime.now().isoformat()
            }
            
            logger.info(f"Validation batch generated: {summary}")
            
            return summary
            
        except Exception as e:
            logger.error(f"Error getting batch summary: {e}")
            raise
        finally:
            cursor.close()
            conn.close()


def main():
    """Main function for command-line usage."""
    import argparse
    
    parser = argparse.ArgumentParser(description='Validation Sampler')
    parser.add_argument('--host', default='localhost', help='Database host')
    parser.add_argument('--user', default='root', help='Database user')
    parser.add_argument('--password', default='', help='Database password')
    parser.add_argument('--database', default='ticketportaal', help='Database name')
    parser.add_argument('--samples', type=int, default=100, help='Number of samples to generate')
    parser.add_argument('--create-tables', action='store_true', help='Create validation tables only')
    
    args = parser.parse_args()
    
    # Database configuration
    db_config = {
        'host': args.host,
        'user': args.user,
        'password': args.password,
        'database': args.database
    }
    
    # Initialize sampler
    sampler = ValidationSampler(db_config)
    
    if args.create_tables:
        print("Creating validation tables...")
        sampler.create_validation_tables()
        print("Tables created successfully")
    else:
        print(f"\n=== Generating Validation Batch ({args.samples} samples) ===\n")
        summary = sampler.generate_validation_batch(args.samples)
        print("\n=== Batch Generated ===\n")
        print(json.dumps(summary, indent=2))


if __name__ == "__main__":
    main()
