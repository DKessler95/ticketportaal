# Entity Extraction Module

## Overview

The Entity Extraction module provides Named Entity Recognition (NER) functionality for the Ticketportaal RAG system. It extracts structured entities from ticket text to populate the knowledge graph and improve search relevance.

## Features

### Extracted Entity Types

1. **Products**: Hardware and software products (e.g., "Dell Latitude 5520", "HP LaserJet 2055")
2. **Errors**: Error codes and messages (e.g., "0x0000007B", "HTTP 500")
3. **Locations**: Physical locations (e.g., "Kantoor Hengelo", "Magazijn")
4. **Persons**: People names (requires spaCy)
5. **Organizations**: Company/department names (requires spaCy)
6. **Technical Terms**: IT terminology (e.g., "BIOS", "VPN", "Windows")
7. **Brands**: Hardware/software brands (e.g., "Dell", "HP", "Microsoft")
8. **Models**: Product model numbers (e.g., "Latitude 5520", "LaserJet 2055")
9. **IP Addresses**: IPv4 addresses (e.g., "192.168.1.100")
10. **Email Addresses**: Email contacts

## Installation

### Step 1: Install spaCy

```bash
# Activate virtual environment
C:\TicketportaalAI\venv\Scripts\activate.bat

# Install spaCy
pip install spacy>=3.7.0
```

### Step 2: Download Dutch NER Model

```bash
# Download large model (recommended, ~560MB)
python -m spacy download nl_core_news_lg

# OR download small model (faster, less accurate, ~40MB)
python -m spacy download nl_core_news_sm
```

### Automated Installation

Use the provided installation scripts:

**Windows Batch:**
```cmd
cd C:\TicketportaalAI\ai_module\scripts
install_spacy_model.bat
```

**PowerShell:**
```powershell
cd C:\TicketportaalAI\ai_module\scripts
.\install_spacy_model.ps1
```

## Usage

### Basic Usage

```python
from entity_extractor import EntityExtractor

# Initialize extractor
extractor = EntityExtractor(use_spacy=True)

# Extract entities from text
text = "Dell Latitude 5520 heeft BIOS error 0x0000007B. Locatie: Kantoor Hengelo"
entities = extractor.extract_entities(text)

# Access extracted entities
print(entities.products)      # ['Dell Latitude 5520']
print(entities.errors)        # ['0x0000007B']
print(entities.locations)     # ['Kantoor Hengelo']
print(entities.brands)        # ['Dell']
print(entities.technical_terms)  # ['bios']
```

### Extract from Complete Ticket

```python
# Ticket data from database
ticket_data = {
    'title': 'Laptop start niet op',
    'description': 'Dell Latitude 5520 geeft geen beeld...',
    'resolution': 'BIOS reset uitgevoerd',
    'comments': [
        {'comment_text': 'Laptop opgehaald van kantoor'},
        {'comment_text': 'BIOS versie geüpdatet'}
    ],
    'dynamic_fields': [
        {'field_name': 'Merk', 'field_value': 'Dell'},
        {'field_name': 'Model', 'field_value': 'Latitude 5520'},
        {'field_name': 'Locatie', 'field_value': 'Kantoor Hengelo'}
    ]
}

# Extract entities from all fields
entities = extractor.extract_from_ticket(ticket_data)

# Convert to dictionary
entity_dict = entities.to_dict()
```

### Without spaCy (Pattern Matching Only)

If spaCy is not available, the extractor falls back to pattern matching:

```python
# Initialize without spaCy
extractor = EntityExtractor(use_spacy=False)

# Still extracts: errors, IPs, emails, brands, models, technical terms
entities = extractor.extract_entities(text)
```

## Testing

Run the test suite to verify functionality:

```bash
# Activate virtual environment
C:\TicketportaalAI\venv\Scripts\activate.bat

# Run tests
cd C:\TicketportaalAI\ai_module\scripts
python test_entity_extraction.py
```

The test suite includes:
- Hardware ticket extraction
- Printer ticket extraction
- Network ticket extraction
- Software ticket extraction
- Complete ticket with dynamic fields
- Pattern matching without spaCy

## Architecture

### Extraction Methods

1. **spaCy NER**: Uses pre-trained Dutch model for persons, organizations, locations
2. **Pattern Matching**: Regex patterns for technical entities (errors, IPs, models)
3. **Known Lists**: Predefined lists of brands and locations
4. **Dynamic Fields**: Extracts structured data from ticket category fields

### Entity Extraction Flow

```
Input Text
    ↓
spaCy NER (if available)
    ↓
Pattern Matching
    ↓
Known Lists Matching
    ↓
Dynamic Fields Extraction
    ↓
Deduplication
    ↓
ExtractedEntities Object
```

## Configuration

### Adding Known Brands

Edit `entity_extractor.py`:

```python
self.known_brands = {
    'dell', 'hp', 'lenovo', 'asus', 'acer',
    # Add your brands here
    'custom_brand_1', 'custom_brand_2'
}
```

### Adding Known Locations

```python
self.known_locations = {
    'hengelo', 'enschede', 'kantoor hengelo',
    # Add your locations here
    'warehouse', 'datacenter'
}
```

### Adding Technical Patterns

```python
self.technical_patterns = [
    r'\b(?:windows|linux|macos)\b',
    # Add your patterns here
    r'\b(?:custom_term_1|custom_term_2)\b'
]
```

## Integration with Knowledge Graph

Extracted entities are used to populate the knowledge graph:

```python
from entity_extractor import EntityExtractor
from knowledge_graph import KnowledgeGraph

# Extract entities
extractor = EntityExtractor()
entities = extractor.extract_from_ticket(ticket_data)

# Add to knowledge graph
graph = KnowledgeGraph()

# Add entity nodes
for product in entities.products:
    graph.add_node(f"product_{product}", node_type='product', properties={'name': product})

for location in entities.locations:
    graph.add_node(f"location_{location}", node_type='location', properties={'name': location})

# Add relationships
ticket_id = f"ticket_{ticket_data['ticket_id']}"
for product in entities.products:
    graph.add_edge(ticket_id, f"product_{product}", edge_type='MENTIONS', confidence=0.9)
```

## Performance

### Extraction Speed

- **With spaCy**: ~100-200 tickets/minute (depends on text length)
- **Without spaCy**: ~500-1000 tickets/minute (pattern matching only)

### Memory Usage

- **spaCy model**: ~500MB RAM (nl_core_news_lg)
- **Extractor instance**: ~10MB RAM

### Optimization Tips

1. **Batch Processing**: Process multiple tickets in batches
2. **Disable spaCy**: Use pattern matching only for faster processing
3. **Cache Results**: Cache extracted entities for frequently accessed tickets
4. **Parallel Processing**: Use multiprocessing for large datasets

## Troubleshooting

### spaCy Model Not Found

**Error**: `OSError: [E050] Can't find model 'nl_core_news_lg'`

**Solution**:
```bash
python -m spacy download nl_core_news_lg
```

### Import Error

**Error**: `ModuleNotFoundError: No module named 'spacy'`

**Solution**:
```bash
pip install spacy>=3.7.0
```

### Low Extraction Quality

**Issue**: Missing entities or incorrect extractions

**Solutions**:
1. Use larger spaCy model (nl_core_news_lg instead of sm)
2. Add domain-specific terms to known lists
3. Improve regex patterns for technical terms
4. Train custom spaCy model on ticket data

### Performance Issues

**Issue**: Slow extraction speed

**Solutions**:
1. Disable spaCy for faster pattern matching
2. Use smaller spaCy model (nl_core_news_sm)
3. Process tickets in batches
4. Cache extraction results

## Requirements

- Python 3.11+
- spaCy 3.7.0+ (optional but recommended)
- Dutch spaCy model: nl_core_news_lg or nl_core_news_sm

## Related Modules

- **knowledge_graph.py**: Uses extracted entities to build graph
- **sync_tickets_to_vector_db.py**: Integrates entity extraction in sync pipeline
- **rag_api.py**: Uses entities for enhanced retrieval

## Future Enhancements

1. **Custom NER Training**: Train spaCy model on ticket-specific data
2. **Multi-language Support**: Add English entity extraction
3. **Image OCR**: Extract entities from ticket screenshots
4. **Confidence Scores**: Add confidence scores to extracted entities
5. **Entity Linking**: Link entities to external knowledge bases
6. **Relationship Extraction**: Extract relationships between entities

## References

- [spaCy Documentation](https://spacy.io/usage)
- [Dutch Models](https://spacy.io/models/nl)
- [Named Entity Recognition](https://en.wikipedia.org/wiki/Named-entity_recognition)
- [Requirements 3.1](../../.kiro/specs/rag-ai-local-implementation/requirements.md)

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review test output: `python test_entity_extraction.py`
3. Check logs in `C:\TicketportaalAI\logs\`
4. Contact: ICT team at Kruit & Kramer
