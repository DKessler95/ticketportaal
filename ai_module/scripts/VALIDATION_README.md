# Human-in-the-Loop Validation System

## Overview

The Human-in-the-Loop (HITL) validation system allows manual review of entity and relationship extraction quality. This is critical for:
- Measuring extraction precision and recall
- Identifying systematic errors in NER and relationship extraction
- Adjusting confidence thresholds based on real performance
- Continuous improvement of the knowledge graph

## Components

### 1. Validation Sampler (`validation_sampler.py`)
Samples 100 representative tickets for manual review using stratified sampling.

**Features:**
- Stratified sampling across categories and priorities
- Ensures representative coverage of ticket types
- Creates validation tables in database
- Tracks validation progress

**Usage:**
```bash
# Create validation tables
python validation_sampler.py --create-tables

# Generate 100 validation samples
python validation_sampler.py --samples 100

# Custom database connection
python validation_sampler.py --host localhost --user root --password yourpass --database ticketportaal --samples 100
```

### 2. Validation UI (`admin/ai_validation.php`)
Web-based interface for reviewing and validating extractions.

**Features:**
- Visual display of ticket content
- Entity validation with confidence scores
- Relationship validation with graph visualization
- Progress tracking
- Sample navigation
- Notes and corrections

**Access:**
1. Log in as admin
2. Navigate to: `http://your-server/admin/ai_validation.php`
3. Review each entity and relationship
4. Mark as correct/incorrect
5. Add notes for corrections
6. Complete sample when done

### 3. Metrics Calculator (`validation_metrics.py`)
Calculates precision, recall, F1 scores and recommends threshold adjustments.

**Features:**
- Entity extraction metrics (precision, recall, F1)
- Relationship extraction metrics
- Confusion matrices by type
- Confidence threshold analysis
- Threshold recommendations

**Usage:**
```bash
# Generate full validation report
python validation_metrics.py --report

# Show entity metrics only
python validation_metrics.py --entity-metrics

# Show relationship metrics only
python validation_metrics.py --relationship-metrics

# Analyze confidence thresholds
python validation_metrics.py --threshold-analysis

# Show validation progress
python validation_metrics.py --progress

# Filter by entity type
python validation_metrics.py --entity-metrics --entity-type product

# Filter by edge type
python validation_metrics.py --relationship-metrics --edge-type SIMILAR_TO
```

## Workflow

### Step 1: Generate Validation Samples
```bash
cd C:\TicketportaalAI\scripts
python validation_sampler.py --samples 100
```

This will:
- Sample 100 tickets using stratified sampling
- Extract entities and relationships for each ticket
- Store them in validation tables
- Display summary statistics

### Step 2: Manual Validation
1. Open web browser
2. Navigate to `http://your-server/admin/ai_validation.php`
3. For each sample:
   - Read the ticket content
   - Review extracted entities
   - Mark each entity as correct/incorrect
   - Review extracted relationships
   - Mark each relationship as correct/incorrect
   - Add notes for any corrections
   - Click "Mark Sample as Complete"
4. Continue until all 100 samples are validated

### Step 3: Calculate Metrics
```bash
# Generate full report
python validation_metrics.py --report > validation_report.json

# View entity metrics
python validation_metrics.py --entity-metrics

# View relationship metrics
python validation_metrics.py --relationship-metrics
```

### Step 4: Analyze Thresholds
```bash
# Analyze confidence thresholds
python validation_metrics.py --threshold-analysis
```

This will show:
- Precision at different confidence thresholds
- Coverage (% of entities kept) at each threshold
- Recommended optimal threshold
- Expected precision and coverage at recommended threshold

### Step 5: Adjust Confidence Thresholds

Based on the threshold analysis, update confidence thresholds in your extraction code:

**In `entity_extractor.py`:**
```python
# Adjust base confidence for spaCy entities
entity_data = {
    'text': ent.text,
    'label': ent.label_,
    'confidence': 0.85,  # Adjust based on validation results
    ...
}
```

**In `relationship_extractor.py`:**
```python
# Adjust confidence thresholds
self.DIRECT_RELATION_CONFIDENCE = 1.0
self.EXTRACTED_RELATION_CONFIDENCE = 0.90  # Adjust based on validation
self.INFERRED_RELATION_CONFIDENCE = 0.75   # Adjust based on validation
self.SIMILARITY_THRESHOLD = 0.80           # Adjust based on validation
```

### Step 6: Re-run Extraction
After adjusting thresholds, re-run entity and relationship extraction on your dataset:

```bash
# Re-extract with new thresholds
python knowledge_extraction_pipeline.py --all
```

### Step 7: Validate Again (Optional)
Generate a new validation batch to verify improvements:

```bash
# Generate new samples
python validation_sampler.py --samples 50

# Validate in UI
# Calculate new metrics
python validation_metrics.py --report
```

## Metrics Interpretation

### Precision
**Definition:** Of all entities/relationships extracted, what percentage are correct?

**Formula:** `Precision = True Positives / (True Positives + False Positives)`

**Interpretation:**
- High precision (>90%): Few false positives, extractions are reliable
- Medium precision (70-90%): Some false positives, needs improvement
- Low precision (<70%): Many false positives, threshold too low

### Recall
**Definition:** Of all entities/relationships that should be extracted, what percentage did we find?

**Formula:** `Recall = True Positives / (True Positives + False Negatives)`

**Interpretation:**
- High recall (>90%): Finding most entities, good coverage
- Medium recall (70-90%): Missing some entities, could improve
- Low recall (<70%): Missing many entities, threshold too high

### F1 Score
**Definition:** Harmonic mean of precision and recall

**Formula:** `F1 = 2 * (Precision * Recall) / (Precision + Recall)`

**Interpretation:**
- High F1 (>85%): Good balance between precision and recall
- Medium F1 (70-85%): Acceptable but could improve
- Low F1 (<70%): Poor extraction quality, needs work

### Confidence Threshold Analysis

The threshold analysis shows precision and coverage at different confidence levels:

**Example Output:**
```json
{
  "threshold_analysis": [
    {
      "threshold": 0.7,
      "precision": 0.82,
      "coverage": 0.95,
      "f1": 0.88
    },
    {
      "threshold": 0.8,
      "precision": 0.91,
      "coverage": 0.78,
      "f1": 0.84
    },
    {
      "threshold": 0.9,
      "precision": 0.97,
      "coverage": 0.52,
      "f1": 0.68
    }
  ],
  "recommendation": {
    "recommended_threshold": 0.8,
    "expected_precision": 0.91,
    "expected_coverage": 0.78,
    "reasoning": "Threshold 0.8 provides best balance between precision and coverage (F1=84%)"
  }
}
```

**Interpretation:**
- Threshold 0.7: High coverage but lower precision (more false positives)
- Threshold 0.8: **Recommended** - Best F1 score, good balance
- Threshold 0.9: Very high precision but low coverage (missing many entities)

## Database Schema

### validation_samples
Stores sampled tickets for validation.

```sql
CREATE TABLE validation_samples (
    sample_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    ticket_number VARCHAR(50),
    category VARCHAR(100),
    priority VARCHAR(50),
    sampled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validated BOOLEAN DEFAULT FALSE,
    validated_at TIMESTAMP NULL,
    validated_by INT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id)
);
```

### entity_validations
Stores entity extractions for validation.

```sql
CREATE TABLE entity_validations (
    validation_id INT AUTO_INCREMENT PRIMARY KEY,
    sample_id INT NOT NULL,
    entity_text VARCHAR(500),
    entity_type VARCHAR(50),
    extracted_confidence DECIMAL(3,2),
    is_correct BOOLEAN NULL,  -- NULL=not reviewed, TRUE=correct, FALSE=incorrect
    should_be_type VARCHAR(50) NULL,
    notes TEXT NULL,
    validated_at TIMESTAMP NULL,
    FOREIGN KEY (sample_id) REFERENCES validation_samples(sample_id)
);
```

### relationship_validations
Stores relationship extractions for validation.

```sql
CREATE TABLE relationship_validations (
    validation_id INT AUTO_INCREMENT PRIMARY KEY,
    sample_id INT NOT NULL,
    source_entity VARCHAR(500),
    target_entity VARCHAR(500),
    edge_type VARCHAR(50),
    extracted_confidence DECIMAL(3,2),
    is_correct BOOLEAN NULL,
    should_be_type VARCHAR(50) NULL,
    notes TEXT NULL,
    validated_at TIMESTAMP NULL,
    FOREIGN KEY (sample_id) REFERENCES validation_samples(sample_id)
);
```

## Best Practices

### Validation Guidelines

1. **Be Consistent:** Use the same criteria for all validations
2. **Read Full Context:** Review entire ticket before validating
3. **Check Confidence:** Pay attention to confidence scores
4. **Add Notes:** Document why something is incorrect
5. **Take Breaks:** Validate in batches to maintain focus

### Entity Validation Criteria

**Correct Entity:**
- Text is accurately extracted
- Type is correctly identified
- Entity is relevant to the ticket

**Incorrect Entity:**
- Text is wrong or incomplete
- Type is misclassified
- Entity is not relevant

### Relationship Validation Criteria

**Correct Relationship:**
- Source and target are correct
- Relationship type is accurate
- Relationship makes logical sense

**Incorrect Relationship:**
- Source or target is wrong
- Relationship type is misclassified
- Relationship doesn't exist

### Threshold Adjustment Guidelines

**If Precision is Low (<80%):**
- Increase confidence thresholds
- Filter out low-confidence extractions
- Improve extraction patterns

**If Recall is Low (<80%):**
- Decrease confidence thresholds
- Add more extraction patterns
- Improve NER model training

**If Both are Low:**
- Review extraction logic
- Consider retraining NER model
- Add domain-specific patterns

## Continuous Improvement

### Monthly Validation Cycle

1. **Week 1:** Generate new validation batch (50 samples)
2. **Week 2:** Complete manual validation
3. **Week 3:** Calculate metrics and adjust thresholds
4. **Week 4:** Re-run extraction and monitor improvements

### Tracking Improvements

Keep a log of validation results over time:

```
Date       | Precision | Recall | F1    | Threshold | Notes
-----------|-----------|--------|-------|-----------|------------------
2024-10-22 | 0.82      | 0.78   | 0.80  | 0.75      | Initial baseline
2024-11-22 | 0.89      | 0.81   | 0.85  | 0.80      | Adjusted threshold
2024-12-22 | 0.92      | 0.85   | 0.88  | 0.80      | Added patterns
```

## Troubleshooting

### No Samples Generated
**Problem:** `validation_sampler.py` returns 0 samples

**Solutions:**
- Check if knowledge graph has data: `SELECT COUNT(*) FROM graph_nodes`
- Verify tickets have entities: Run entity extraction first
- Check database connection settings

### Validation UI Not Loading
**Problem:** `ai_validation.php` shows blank page

**Solutions:**
- Check PHP error logs
- Verify database tables exist
- Ensure user is logged in as admin
- Check file permissions

### Metrics Show 0%
**Problem:** All metrics are 0.00

**Solutions:**
- Ensure validations are completed (is_correct is not NULL)
- Check at least 10 samples are validated
- Verify database queries are returning data

### Threshold Analysis Empty
**Problem:** No threshold recommendations

**Solutions:**
- Complete at least 20 entity validations
- Ensure confidence scores are set
- Check validation data has variety of confidence levels

## Support

For issues or questions:
1. Check logs in `C:\TicketportaalAI\logs\`
2. Review database tables for data
3. Consult main documentation
4. Contact system administrator

## Next Steps

After completing validation:
1. Review metrics in AI Dashboard
2. Adjust extraction parameters
3. Re-run knowledge extraction
4. Monitor production performance
5. Schedule next validation cycle
