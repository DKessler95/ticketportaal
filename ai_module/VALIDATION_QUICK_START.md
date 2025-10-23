# Human-in-the-Loop Validation - Quick Start Guide

## What is This?

The validation system lets you manually review 100 tickets to check if the AI correctly extracted entities (products, errors, people, locations) and relationships (who created what, what affects what, etc.). This helps measure and improve extraction quality.

## Quick Start (5 Steps)

### Step 1: Generate Validation Samples (5 minutes)

Open Command Prompt and run:

```cmd
cd C:\TicketportaalAI\scripts
run_validation_workflow.bat
```

This will:
- Create validation database tables
- Sample 100 representative tickets
- Open your browser to the validation UI

### Step 2: Validate Samples (2-3 hours)

In the browser at `http://localhost/admin/ai_validation.php`:

1. **Read the ticket** - Review title, description, and resolution
2. **Check entities** - Are products, errors, people, locations correct?
   - Click ✓ Correct if right
   - Click ✗ Incorrect if wrong
   - Add notes if needed
3. **Check relationships** - Are connections between entities correct?
   - Example: `ticket_123 --CREATED_BY--> user_45`
   - Click ✓ Correct or ✗ Incorrect
4. **Complete sample** - Click "Mark Sample as Complete"
5. **Repeat** for all 100 samples

**Tips:**
- Take breaks every 20 samples
- Be consistent in your judgments
- Add notes for unclear cases
- You can come back later - progress is saved

### Step 3: Calculate Metrics (1 minute)

After completing validations, run:

```cmd
cd C:\TicketportaalAI\scripts
run_validation_metrics.bat
```

This will show:
- **Precision**: % of extractions that are correct (target: >85%)
- **Recall**: % of actual entities found (target: >80%)
- **F1 Score**: Overall quality (target: >85%)
- **Recommended Threshold**: Optimal confidence level

### Step 4: Adjust Thresholds (5 minutes)

Based on the metrics, update confidence thresholds:

**Edit `ai_module/scripts/entity_extractor.py`:**

Find this section and adjust the confidence value:
```python
entity_data = {
    'text': ent.text,
    'label': ent.label_,
    'confidence': 0.85,  # Change this based on recommendations
    ...
}
```

**Edit `ai_module/scripts/relationship_extractor.py`:**

Find this section and adjust thresholds:
```python
self.EXTRACTED_RELATION_CONFIDENCE = 0.90  # Adjust based on validation
self.INFERRED_RELATION_CONFIDENCE = 0.75   # Adjust based on validation
self.SIMILARITY_THRESHOLD = 0.80           # Adjust based on validation
```

### Step 5: Re-run Extraction (10-30 minutes)

Apply the new thresholds:

```cmd
cd C:\TicketportaalAI\scripts
python knowledge_extraction_pipeline.py --all
```

This will re-extract entities and relationships with the improved thresholds.

## Understanding the Metrics

### Precision
**What it means:** Of all the entities we extracted, how many are actually correct?

**Example:** 
- Extracted 100 entities
- 85 are correct
- Precision = 85%

**What to do:**
- **High (>90%)**: Great! Extractions are reliable
- **Medium (70-90%)**: Some false positives, increase threshold
- **Low (<70%)**: Too many errors, significantly increase threshold

### Recall
**What it means:** Of all the entities that exist, how many did we find?

**Example:**
- 100 entities should be extracted
- We found 80 of them
- Recall = 80%

**What to do:**
- **High (>90%)**: Great! Finding most entities
- **Medium (70-90%)**: Missing some, slightly decrease threshold
- **Low (<70%)**: Missing too many, decrease threshold

### F1 Score
**What it means:** Balance between precision and recall

**Formula:** `2 * (Precision * Recall) / (Precision + Recall)`

**What to do:**
- **High (>85%)**: System is working well
- **Medium (70-85%)**: Needs improvement
- **Low (<70%)**: Significant issues, review extraction logic

### Confidence Threshold
**What it means:** Minimum confidence score to keep an extraction

**Example:**
- Threshold 0.7: Keep entities with 70%+ confidence
- Threshold 0.9: Keep only entities with 90%+ confidence

**Trade-off:**
- **Higher threshold**: Better precision, lower recall (fewer false positives, but miss some entities)
- **Lower threshold**: Better recall, lower precision (find more entities, but more false positives)

## Example Validation Session

### Sample Ticket
```
Title: Dell laptop start niet op
Description: Mijn Dell Latitude 5520 geeft een blue screen error 0x0000007B
Resolution: BIOS update uitgevoerd
```

### Extracted Entities
1. **Product: "Dell Latitude 5520"** - Confidence: 90%
   - ✓ Correct (it's mentioned in the description)

2. **Error: "blue screen error 0x0000007B"** - Confidence: 100%
   - ✓ Correct (exact error code)

3. **Person: "Mijn"** - Confidence: 75%
   - ✗ Incorrect (this is not a person, it's Dutch for "my")
   - Note: "False positive - Dutch possessive pronoun"

### Extracted Relationships
1. **ticket_123 --CREATED_BY--> user_45** - Confidence: 100%
   - ✓ Correct (from database)

2. **ticket_123 --MENTIONS--> product_dell_latitude_5520** - Confidence: 90%
   - ✓ Correct (ticket mentions this product)

3. **ticket_123 --SIMILAR_TO--> ticket_89** - Confidence: 82%
   - ✓ Correct (both are about Dell laptop boot issues)

## Troubleshooting

### "No samples available"
**Solution:** Run `run_validation_workflow.bat` first to generate samples

### "Database connection error"
**Solution:** Check that MySQL is running and credentials are correct in scripts

### "No entities extracted"
**Solution:** Run entity extraction first: `python knowledge_extraction_pipeline.py --all`

### "Validation UI not loading"
**Solution:** 
1. Check you're logged in as admin
2. Verify PHP files are in correct location
3. Check PHP error logs

### "Metrics show 0%"
**Solution:** Complete at least 10 validations before calculating metrics

## Monthly Validation Cycle

For continuous improvement, repeat this process monthly:

**Week 1:** Generate 50 new validation samples
**Week 2:** Complete validations
**Week 3:** Calculate metrics and adjust thresholds
**Week 4:** Re-run extraction and monitor improvements

Track your progress:
```
Month      | Precision | Recall | F1    | Threshold
-----------|-----------|--------|-------|----------
Oct 2024   | 82%       | 78%    | 80%   | 0.75
Nov 2024   | 89%       | 81%    | 85%   | 0.80
Dec 2024   | 92%       | 85%    | 88%   | 0.80
```

## Need Help?

1. Read full documentation: `ai_module/scripts/VALIDATION_README.md`
2. Check logs: `C:\TicketportaalAI\logs\`
3. Run tests: `python test_validation_system.py`
4. Contact system administrator

## Summary

The validation system helps you:
- ✅ Measure extraction quality objectively
- ✅ Identify systematic errors
- ✅ Optimize confidence thresholds
- ✅ Continuously improve the AI
- ✅ Build trust in the system

**Time Investment:**
- Initial setup: 5 minutes
- Validation: 2-3 hours (one-time)
- Metrics & adjustment: 10 minutes
- Monthly maintenance: 1 hour

**Expected Results:**
- Precision: 85-95%
- Recall: 80-90%
- F1 Score: 85-92%
- Improved knowledge graph quality
- Better AI suggestions for agents
