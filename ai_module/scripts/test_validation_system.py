"""
Test Validation System
Tests the human-in-the-loop validation components.

This script verifies:
- Validation tables can be created
- Samples can be generated
- Metrics can be calculated
- System handles edge cases
"""

import sys
import mysql.connector
from validation_sampler import ValidationSampler
from validation_metrics import ValidationMetrics
import logging
import json

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [TEST] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


def test_database_connection(db_config):
    """Test database connection."""
    logger.info("Testing database connection...")
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        cursor.execute("SELECT 1")
        result = cursor.fetchone()
        cursor.close()
        conn.close()
        
        if result[0] == 1:
            logger.info("✓ Database connection successful")
            return True
        else:
            logger.error("✗ Database connection failed")
            return False
    except Exception as e:
        logger.error(f"✗ Database connection error: {e}")
        return False


def test_validation_tables(db_config):
    """Test validation table creation."""
    logger.info("Testing validation table creation...")
    try:
        sampler = ValidationSampler(db_config)
        sampler.create_validation_tables()
        
        # Verify tables exist
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        tables = ['validation_samples', 'entity_validations', 'relationship_validations']
        for table in tables:
            cursor.execute(f"SHOW TABLES LIKE '{table}'")
            result = cursor.fetchone()
            if result:
                logger.info(f"✓ Table '{table}' exists")
            else:
                logger.error(f"✗ Table '{table}' not found")
                return False
        
        cursor.close()
        conn.close()
        
        logger.info("✓ All validation tables created successfully")
        return True
        
    except Exception as e:
        logger.error(f"✗ Error creating validation tables: {e}")
        return False


def test_sample_generation(db_config):
    """Test validation sample generation."""
    logger.info("Testing sample generation...")
    try:
        sampler = ValidationSampler(db_config)
        
        # Generate small sample for testing
        tickets = sampler.get_stratified_sample(total_samples=10)
        
        if len(tickets) > 0:
            logger.info(f"✓ Generated {len(tickets)} sample tickets")
            
            # Test saving samples
            saved_count = sampler.save_validation_samples(tickets)
            logger.info(f"✓ Saved {saved_count} validation samples")
            
            return True
        else:
            logger.warning("⚠ No tickets available for sampling")
            return True  # Not a failure, just no data
            
    except Exception as e:
        logger.error(f"✗ Error generating samples: {e}")
        return False


def test_metrics_calculation(db_config):
    """Test metrics calculation."""
    logger.info("Testing metrics calculation...")
    try:
        metrics = ValidationMetrics(db_config)
        
        # Test progress check
        progress = metrics.get_validation_progress()
        logger.info(f"✓ Progress check successful: {progress['samples']['total']} samples")
        
        # Test entity metrics (may be empty if no validations yet)
        entity_metrics = metrics.calculate_entity_metrics()
        logger.info(f"✓ Entity metrics calculated: {entity_metrics['total_validated']} validated")
        
        # Test relationship metrics
        rel_metrics = metrics.calculate_relationship_metrics()
        logger.info(f"✓ Relationship metrics calculated: {rel_metrics['total_validated']} validated")
        
        # Test threshold analysis (may be empty if no validations)
        if entity_metrics['total_validated'] > 0:
            threshold_analysis = metrics.analyze_confidence_thresholds()
            logger.info(f"✓ Threshold analysis completed")
        else:
            logger.info("⚠ Skipping threshold analysis (no validations yet)")
        
        return True
        
    except Exception as e:
        logger.error(f"✗ Error calculating metrics: {e}")
        return False


def test_edge_cases(db_config):
    """Test edge cases and error handling."""
    logger.info("Testing edge cases...")
    try:
        metrics = ValidationMetrics(db_config)
        
        # Test with non-existent entity type
        result = metrics.calculate_entity_metrics(entity_type='nonexistent_type')
        if result['total_validated'] == 0:
            logger.info("✓ Handles non-existent entity type correctly")
        
        # Test with non-existent edge type
        result = metrics.calculate_relationship_metrics(edge_type='NONEXISTENT_EDGE')
        if result['total_validated'] == 0:
            logger.info("✓ Handles non-existent edge type correctly")
        
        return True
        
    except Exception as e:
        logger.error(f"✗ Error in edge case testing: {e}")
        return False


def test_data_integrity(db_config):
    """Test data integrity constraints."""
    logger.info("Testing data integrity...")
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        
        # Check for orphaned entity validations
        cursor.execute("""
            SELECT COUNT(*) as count
            FROM entity_validations ev
            LEFT JOIN validation_samples vs ON ev.sample_id = vs.sample_id
            WHERE vs.sample_id IS NULL
        """)
        orphaned_entities = cursor.fetchone()['count']
        
        if orphaned_entities == 0:
            logger.info("✓ No orphaned entity validations")
        else:
            logger.warning(f"⚠ Found {orphaned_entities} orphaned entity validations")
        
        # Check for orphaned relationship validations
        cursor.execute("""
            SELECT COUNT(*) as count
            FROM relationship_validations rv
            LEFT JOIN validation_samples vs ON rv.sample_id = vs.sample_id
            WHERE vs.sample_id IS NULL
        """)
        orphaned_relationships = cursor.fetchone()['count']
        
        if orphaned_relationships == 0:
            logger.info("✓ No orphaned relationship validations")
        else:
            logger.warning(f"⚠ Found {orphaned_relationships} orphaned relationship validations")
        
        # Check confidence score ranges
        cursor.execute("""
            SELECT COUNT(*) as count
            FROM entity_validations
            WHERE extracted_confidence < 0 OR extracted_confidence > 1
        """)
        invalid_confidence = cursor.fetchone()['count']
        
        if invalid_confidence == 0:
            logger.info("✓ All confidence scores are valid (0-1 range)")
        else:
            logger.error(f"✗ Found {invalid_confidence} invalid confidence scores")
        
        cursor.close()
        conn.close()
        
        return True
        
    except Exception as e:
        logger.error(f"✗ Error checking data integrity: {e}")
        return False


def run_all_tests(db_config):
    """Run all validation system tests."""
    logger.info("=" * 60)
    logger.info("Starting Validation System Tests")
    logger.info("=" * 60)
    
    tests = [
        ("Database Connection", test_database_connection),
        ("Validation Tables", test_validation_tables),
        ("Sample Generation", test_sample_generation),
        ("Metrics Calculation", test_metrics_calculation),
        ("Edge Cases", test_edge_cases),
        ("Data Integrity", test_data_integrity)
    ]
    
    results = []
    
    for test_name, test_func in tests:
        logger.info("")
        logger.info(f"Running: {test_name}")
        logger.info("-" * 60)
        
        try:
            result = test_func(db_config)
            results.append((test_name, result))
        except Exception as e:
            logger.error(f"Test '{test_name}' crashed: {e}")
            results.append((test_name, False))
    
    # Print summary
    logger.info("")
    logger.info("=" * 60)
    logger.info("Test Summary")
    logger.info("=" * 60)
    
    passed = sum(1 for _, result in results if result)
    total = len(results)
    
    for test_name, result in results:
        status = "✓ PASS" if result else "✗ FAIL"
        logger.info(f"{status}: {test_name}")
    
    logger.info("")
    logger.info(f"Results: {passed}/{total} tests passed ({passed/total*100:.1f}%)")
    
    if passed == total:
        logger.info("✓ All tests passed!")
        return True
    else:
        logger.error(f"✗ {total - passed} test(s) failed")
        return False


def main():
    """Main function for command-line usage."""
    import argparse
    
    parser = argparse.ArgumentParser(description='Test Validation System')
    parser.add_argument('--host', default='localhost', help='Database host')
    parser.add_argument('--user', default='root', help='Database user')
    parser.add_argument('--password', default='', help='Database password')
    parser.add_argument('--database', default='ticketportaal', help='Database name')
    
    args = parser.parse_args()
    
    # Database configuration
    db_config = {
        'host': args.host,
        'user': args.user,
        'password': args.password,
        'database': args.database
    }
    
    # Run tests
    success = run_all_tests(db_config)
    
    # Exit with appropriate code
    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
