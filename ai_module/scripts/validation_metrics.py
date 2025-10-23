"""
Validation Metrics Calculator
Calculates precision, recall, and F1 scores for entity and relationship extraction.

This module handles:
- Calculating precision/recall for entity extraction
- Calculating precision/recall for relationship extraction
- Generating confusion matrices
- Recommending confidence threshold adjustments
"""

import mysql.connector
from typing import Dict, List, Any, Optional, Tuple
import logging
from datetime import datetime
import json
from collections import defaultdict
import numpy as np

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [METRICS] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


class ValidationMetrics:
    """
    Calculates quality metrics for extraction validation.
    
    Computes precision, recall, F1, and provides threshold recommendations.
    """
    
    def __init__(self, db_config: Dict[str, str]):
        """
        Initialize metrics calculator.
        
        Args:
            db_config: MySQL connection configuration
        """
        self.db_config = db_config
    
    def connect_db(self) -> mysql.connector.MySQLConnection:
        """Create database connection."""
        return mysql.connector.connect(**self.db_config)
    
    def calculate_entity_metrics(self, entity_type: Optional[str] = None) -> Dict[str, Any]:
        """
        Calculate precision and recall for entity extraction.
        
        Args:
            entity_type: Optional filter for specific entity type
        
        Returns:
            Dictionary with precision, recall, F1, and confusion matrix
        """
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Build query with optional type filter
            query = """
                SELECT 
                    entity_type,
                    extracted_confidence,
                    is_correct,
                    should_be_type
                FROM entity_validations
                WHERE is_correct IS NOT NULL
            """
            params = []
            
            if entity_type:
                query += " AND entity_type = %s"
                params.append(entity_type)
            
            cursor.execute(query, params)
            validations = cursor.fetchall()
            
            if not validations:
                logger.warning("No validated entities found")
                return {
                    'total_validated': 0,
                    'precision': 0.0,
                    'recall': 0.0,
                    'f1_score': 0.0
                }
            
            # Calculate metrics
            true_positives = sum(1 for v in validations if v['is_correct'] == 1)
            false_positives = sum(1 for v in validations if v['is_correct'] == 0)
            
            # For recall, we need to know false negatives (entities that should have been extracted but weren't)
            # This is harder to measure automatically, so we approximate based on validation feedback
            # Assume false negatives are entities marked with should_be_type but not extracted
            false_negatives = sum(1 for v in validations if v['is_correct'] == 0 and v['should_be_type'])
            
            total_validated = len(validations)
            
            # Calculate precision: TP / (TP + FP)
            precision = true_positives / (true_positives + false_positives) if (true_positives + false_positives) > 0 else 0.0
            
            # Calculate recall: TP / (TP + FN)
            recall = true_positives / (true_positives + false_negatives) if (true_positives + false_negatives) > 0 else 0.0
            
            # Calculate F1 score: 2 * (precision * recall) / (precision + recall)
            f1_score = 2 * (precision * recall) / (precision + recall) if (precision + recall) > 0 else 0.0
            
            # Calculate accuracy
            accuracy = true_positives / total_validated if total_validated > 0 else 0.0
            
            # Build confusion matrix by entity type
            confusion_matrix = defaultdict(lambda: {'correct': 0, 'incorrect': 0, 'total': 0})
            
            for v in validations:
                et = v['entity_type']
                confusion_matrix[et]['total'] += 1
                if v['is_correct'] == 1:
                    confusion_matrix[et]['correct'] += 1
                else:
                    confusion_matrix[et]['incorrect'] += 1
            
            # Calculate per-type accuracy
            for et in confusion_matrix:
                total = confusion_matrix[et]['total']
                correct = confusion_matrix[et]['correct']
                confusion_matrix[et]['accuracy'] = correct / total if total > 0 else 0.0
            
            metrics = {
                'total_validated': total_validated,
                'true_positives': true_positives,
                'false_positives': false_positives,
                'false_negatives': false_negatives,
                'precision': round(precision, 4),
                'recall': round(recall, 4),
                'f1_score': round(f1_score, 4),
                'accuracy': round(accuracy, 4),
                'confusion_matrix': dict(confusion_matrix),
                'entity_type_filter': entity_type
            }
            
            logger.info(f"Entity metrics calculated: Precision={precision:.2%}, Recall={recall:.2%}, F1={f1_score:.2%}")
            
            return metrics
            
        except Exception as e:
            logger.error(f"Error calculating entity metrics: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def calculate_relationship_metrics(self, edge_type: Optional[str] = None) -> Dict[str, Any]:
        """
        Calculate precision and recall for relationship extraction.
        
        Args:
            edge_type: Optional filter for specific edge type
        
        Returns:
            Dictionary with precision, recall, F1, and confusion matrix
        """
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Build query with optional type filter
            query = """
                SELECT 
                    edge_type,
                    extracted_confidence,
                    is_correct,
                    should_be_type
                FROM relationship_validations
                WHERE is_correct IS NOT NULL
            """
            params = []
            
            if edge_type:
                query += " AND edge_type = %s"
                params.append(edge_type)
            
            cursor.execute(query, params)
            validations = cursor.fetchall()
            
            if not validations:
                logger.warning("No validated relationships found")
                return {
                    'total_validated': 0,
                    'precision': 0.0,
                    'recall': 0.0,
                    'f1_score': 0.0
                }
            
            # Calculate metrics
            true_positives = sum(1 for v in validations if v['is_correct'] == 1)
            false_positives = sum(1 for v in validations if v['is_correct'] == 0)
            false_negatives = sum(1 for v in validations if v['is_correct'] == 0 and v['should_be_type'])
            
            total_validated = len(validations)
            
            # Calculate precision, recall, F1
            precision = true_positives / (true_positives + false_positives) if (true_positives + false_positives) > 0 else 0.0
            recall = true_positives / (true_positives + false_negatives) if (true_positives + false_negatives) > 0 else 0.0
            f1_score = 2 * (precision * recall) / (precision + recall) if (precision + recall) > 0 else 0.0
            accuracy = true_positives / total_validated if total_validated > 0 else 0.0
            
            # Build confusion matrix by edge type
            confusion_matrix = defaultdict(lambda: {'correct': 0, 'incorrect': 0, 'total': 0})
            
            for v in validations:
                et = v['edge_type']
                confusion_matrix[et]['total'] += 1
                if v['is_correct'] == 1:
                    confusion_matrix[et]['correct'] += 1
                else:
                    confusion_matrix[et]['incorrect'] += 1
            
            # Calculate per-type accuracy
            for et in confusion_matrix:
                total = confusion_matrix[et]['total']
                correct = confusion_matrix[et]['correct']
                confusion_matrix[et]['accuracy'] = correct / total if total > 0 else 0.0
            
            metrics = {
                'total_validated': total_validated,
                'true_positives': true_positives,
                'false_positives': false_positives,
                'false_negatives': false_negatives,
                'precision': round(precision, 4),
                'recall': round(recall, 4),
                'f1_score': round(f1_score, 4),
                'accuracy': round(accuracy, 4),
                'confusion_matrix': dict(confusion_matrix),
                'edge_type_filter': edge_type
            }
            
            logger.info(f"Relationship metrics calculated: Precision={precision:.2%}, Recall={recall:.2%}, F1={f1_score:.2%}")
            
            return metrics
            
        except Exception as e:
            logger.error(f"Error calculating relationship metrics: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def analyze_confidence_thresholds(self, entity_type: Optional[str] = None) -> Dict[str, Any]:
        """
        Analyze extraction quality at different confidence thresholds.
        
        Args:
            entity_type: Optional filter for specific entity type
        
        Returns:
            Threshold analysis with recommendations
        """
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Get entity validations with confidence scores
            query = """
                SELECT 
                    entity_type,
                    extracted_confidence,
                    is_correct
                FROM entity_validations
                WHERE is_correct IS NOT NULL
            """
            params = []
            
            if entity_type:
                query += " AND entity_type = %s"
                params.append(entity_type)
            
            cursor.execute(query, params)
            validations = cursor.fetchall()
            
            if not validations:
                return {'error': 'No validated entities found'}
            
            # Test different thresholds
            thresholds = [0.5, 0.6, 0.7, 0.75, 0.8, 0.85, 0.9, 0.95]
            threshold_analysis = []
            
            for threshold in thresholds:
                # Filter by threshold
                filtered = [v for v in validations if float(v['extracted_confidence']) >= threshold]
                
                if not filtered:
                    continue
                
                # Calculate metrics at this threshold
                tp = sum(1 for v in filtered if v['is_correct'] == 1)
                fp = sum(1 for v in filtered if v['is_correct'] == 0)
                
                precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
                coverage = len(filtered) / len(validations)
                
                threshold_analysis.append({
                    'threshold': threshold,
                    'precision': round(precision, 4),
                    'coverage': round(coverage, 4),
                    'entities_kept': len(filtered),
                    'entities_filtered': len(validations) - len(filtered)
                })
            
            # Find optimal threshold (best F1 between precision and coverage)
            best_threshold = None
            best_f1 = 0.0
            
            for analysis in threshold_analysis:
                f1 = 2 * (analysis['precision'] * analysis['coverage']) / (analysis['precision'] + analysis['coverage']) if (analysis['precision'] + analysis['coverage']) > 0 else 0.0
                analysis['f1'] = round(f1, 4)
                
                if f1 > best_f1:
                    best_f1 = f1
                    best_threshold = analysis['threshold']
            
            # Generate recommendation
            current_avg_confidence = sum(float(v['extracted_confidence']) for v in validations) / len(validations)
            
            recommendation = {
                'current_average_confidence': round(current_avg_confidence, 4),
                'recommended_threshold': best_threshold,
                'expected_precision': next((a['precision'] for a in threshold_analysis if a['threshold'] == best_threshold), 0.0),
                'expected_coverage': next((a['coverage'] for a in threshold_analysis if a['threshold'] == best_threshold), 0.0),
                'reasoning': f"Threshold {best_threshold} provides best balance between precision and coverage (F1={best_f1:.2%})"
            }
            
            return {
                'threshold_analysis': threshold_analysis,
                'recommendation': recommendation,
                'total_validations': len(validations),
                'entity_type_filter': entity_type
            }
            
        except Exception as e:
            logger.error(f"Error analyzing confidence thresholds: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def get_validation_progress(self) -> Dict[str, Any]:
        """
        Get current validation progress statistics.
        
        Returns:
            Progress summary
        """
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        try:
            # Sample progress
            cursor.execute("""
                SELECT 
                    COUNT(*) as total_samples,
                    SUM(CASE WHEN validated = TRUE THEN 1 ELSE 0 END) as validated_samples,
                    COUNT(DISTINCT category) as categories
                FROM validation_samples
            """)
            sample_stats = cursor.fetchone()
            
            # Entity validation progress
            cursor.execute("""
                SELECT 
                    COUNT(*) as total_entities,
                    SUM(CASE WHEN is_correct IS NOT NULL THEN 1 ELSE 0 END) as validated_entities,
                    entity_type,
                    COUNT(*) as count
                FROM entity_validations
                GROUP BY entity_type
            """)
            entity_stats = cursor.fetchall()
            
            # Relationship validation progress
            cursor.execute("""
                SELECT 
                    COUNT(*) as total_relationships,
                    SUM(CASE WHEN is_correct IS NOT NULL THEN 1 ELSE 0 END) as validated_relationships,
                    edge_type,
                    COUNT(*) as count
                FROM relationship_validations
                GROUP BY edge_type
            """)
            relationship_stats = cursor.fetchall()
            
            # Calculate completion percentage
            total_samples = sample_stats['total_samples'] or 0
            validated_samples = sample_stats['validated_samples'] or 0
            completion_pct = (validated_samples / total_samples * 100) if total_samples > 0 else 0.0
            
            return {
                'samples': {
                    'total': total_samples,
                    'validated': validated_samples,
                    'pending': total_samples - validated_samples,
                    'completion_percentage': round(completion_pct, 2),
                    'categories_covered': sample_stats['categories']
                },
                'entities': {
                    'by_type': [
                        {
                            'type': e['entity_type'],
                            'count': e['count']
                        } for e in entity_stats
                    ]
                },
                'relationships': {
                    'by_type': [
                        {
                            'type': r['edge_type'],
                            'count': r['count']
                        } for r in relationship_stats
                    ]
                }
            }
            
        except Exception as e:
            logger.error(f"Error getting validation progress: {e}")
            raise
        finally:
            cursor.close()
            conn.close()
    
    def generate_full_report(self) -> Dict[str, Any]:
        """
        Generate comprehensive validation report.
        
        Returns:
            Complete validation report with all metrics
        """
        logger.info("Generating full validation report")
        
        try:
            report = {
                'generated_at': datetime.now().isoformat(),
                'progress': self.get_validation_progress(),
                'entity_metrics': self.calculate_entity_metrics(),
                'relationship_metrics': self.calculate_relationship_metrics(),
                'threshold_analysis': self.analyze_confidence_thresholds()
            }
            
            # Add summary
            entity_metrics = report['entity_metrics']
            rel_metrics = report['relationship_metrics']
            
            report['summary'] = {
                'overall_precision': round((entity_metrics['precision'] + rel_metrics['precision']) / 2, 4),
                'overall_recall': round((entity_metrics['recall'] + rel_metrics['recall']) / 2, 4),
                'overall_f1': round((entity_metrics['f1_score'] + rel_metrics['f1_score']) / 2, 4),
                'entity_accuracy': entity_metrics['accuracy'],
                'relationship_accuracy': rel_metrics['accuracy'],
                'recommended_threshold': report['threshold_analysis']['recommendation']['recommended_threshold']
            }
            
            logger.info("Full validation report generated successfully")
            
            return report
            
        except Exception as e:
            logger.error(f"Error generating full report: {e}")
            raise


def main():
    """Main function for command-line usage."""
    import argparse
    
    parser = argparse.ArgumentParser(description='Validation Metrics Calculator')
    parser.add_argument('--host', default='localhost', help='Database host')
    parser.add_argument('--user', default='root', help='Database user')
    parser.add_argument('--password', default='', help='Database password')
    parser.add_argument('--database', default='ticketportaal', help='Database name')
    parser.add_argument('--report', action='store_true', help='Generate full report')
    parser.add_argument('--entity-metrics', action='store_true', help='Show entity metrics only')
    parser.add_argument('--relationship-metrics', action='store_true', help='Show relationship metrics only')
    parser.add_argument('--threshold-analysis', action='store_true', help='Show threshold analysis')
    parser.add_argument('--progress', action='store_true', help='Show validation progress')
    parser.add_argument('--entity-type', help='Filter by entity type')
    parser.add_argument('--edge-type', help='Filter by edge type')
    
    args = parser.parse_args()
    
    # Database configuration
    db_config = {
        'host': args.host,
        'user': args.user,
        'password': args.password,
        'database': args.database
    }
    
    # Initialize metrics calculator
    metrics = ValidationMetrics(db_config)
    
    if args.report:
        print("\n=== Full Validation Report ===\n")
        report = metrics.generate_full_report()
        print(json.dumps(report, indent=2))
    
    elif args.entity_metrics:
        print("\n=== Entity Extraction Metrics ===\n")
        result = metrics.calculate_entity_metrics(args.entity_type)
        print(json.dumps(result, indent=2))
    
    elif args.relationship_metrics:
        print("\n=== Relationship Extraction Metrics ===\n")
        result = metrics.calculate_relationship_metrics(args.edge_type)
        print(json.dumps(result, indent=2))
    
    elif args.threshold_analysis:
        print("\n=== Confidence Threshold Analysis ===\n")
        result = metrics.analyze_confidence_thresholds(args.entity_type)
        print(json.dumps(result, indent=2))
    
    elif args.progress:
        print("\n=== Validation Progress ===\n")
        result = metrics.get_validation_progress()
        print(json.dumps(result, indent=2))
    
    else:
        print("Please specify an action: --report, --entity-metrics, --relationship-metrics, --threshold-analysis, or --progress")


if __name__ == "__main__":
    main()
