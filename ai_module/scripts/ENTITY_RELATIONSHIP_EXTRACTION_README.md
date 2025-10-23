# Entity and Relationship Extraction

Deze module implementeert Named Entity Recognition (NER) en Relationship Extraction voor het Ticketportaal systeem. Het extraheert entiteiten en relaties uit ticket teksten en bouwt een knowledge graph.

## Overzicht

De knowledge extraction pipeline bestaat uit drie hoofdcomponenten:

1. **Entity Extractor** - Extraheert entiteiten uit ticket tekst
2. **Relationship Extractor** - Detecteert relaties tussen entiteiten
3. **Knowledge Extraction Pipeline** - Coördineert het volledige proces

## Componenten

### 1. Entity Extractor (`entity_extractor.py`)

Extraheert entiteiten zoals:
- **Hardware**: laptops, printers, servers, monitors
- **Software**: applicaties, operating systems
- **Locaties**: kantoren, gebouwen, kamers
- **Personen**: gebruikers, technici
- **Technische termen**: IP adressen, error codes, email adressen

#### Extractie Methoden

1. **spaCy NER**: Gebruikt het Nederlandse spaCy model voor named entity recognition
2. **Custom Patterns**: Entity ruler met IT-specifieke patronen
3. **Regex Patterns**: Voor gestructureerde data (IP adressen, emails, telefoons)

#### Gebruik

```python
from entity_extractor import EntityExtractor

# Database configuratie
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ticketportaal'
}

# Initialiseer extractor
extractor = EntityExtractor(db_config, model_name="nl_core_news_sm")

# Extract entities uit tekst
text = "Jan Jansen heeft problemen met zijn Dell Latitude laptop in kantoor Hengelo."
entities = extractor.extract_entities(text)

# Process een ticket
ticket_data = {
    'title': 'Laptop probleem',
    'description': 'Dell laptop start niet op',
    'resolution': 'BIOS update uitgevoerd'
}
entities = extractor.process_ticket(ticket_id=123, ticket_data=ticket_data)

# Save naar knowledge graph
extractor.save_entities_to_graph(entities)

# Statistieken
stats = extractor.get_extraction_stats()
print(stats)
```

#### Entity Types

| Entity Label | Knowledge Graph Type | Beschrijving |
|-------------|---------------------|--------------|
| PERSON | user | Personen (gebruikers, technici) |
| ORG | department | Organisaties en afdelingen |
| GPE | location | Geografische locaties |
| PRODUCT | ci | Producten en hardware |
| HARDWARE | ci | Hardware items |
| SOFTWARE | ci | Software applicaties |
| IP_ADDRESS | ci | IP adressen |
| ERROR_CODE | solution | Error codes |
| BUILDING | location | Gebouwen en kantoren |
| ROOM | location | Kamers en ruimtes |
| EMAIL | user | Email adressen |
| PHONE | user | Telefoonnummers |
| TICKET_REF | ticket | Ticket referenties |

### 2. Relationship Extractor (`relationship_extractor.py`)

Extraheert relaties tussen entiteiten zoals:
- **USES**: Gebruiker gebruikt hardware/software
- **LOCATED_AT**: Hardware bevindt zich op locatie
- **WORKS_IN**: Gebruiker werkt in afdeling
- **AFFECTS**: Ticket beïnvloedt hardware
- **CONNECTED_TO**: Hardware verbonden met netwerk
- **SIMILAR_TO**: Entiteiten komen samen voor

#### Extractie Methoden

1. **Pattern-based**: Regex patronen voor specifieke relaties
2. **Dependency-based**: spaCy dependency parsing
3. **Co-occurrence**: Entiteiten die dicht bij elkaar voorkomen

#### Gebruik

```python
from relationship_extractor import RelationshipExtractor

# Initialiseer extractor
extractor = RelationshipExtractor(db_config, model_name="nl_core_news_sm")

# Extract relationships uit tekst en entities
text = "Jan Jansen gebruikt een Dell laptop in kantoor Hengelo."
entities = [...]  # Van entity extractor

relationships = extractor.extract_relationships(text, entities)

# Process een ticket
ticket_data = {...}
relationships = extractor.process_ticket_relationships(ticket_id=123, ticket_data=ticket_data)

# Save naar knowledge graph
extractor.save_relationships_to_graph(relationships)

# Statistieken
stats = extractor.get_relationship_stats()
print(stats)
```

#### Relationship Types

| Relationship Type | Beschrijving | Voorbeeld |
|------------------|--------------|-----------|
| USES | Gebruiker gebruikt item | Jan USES Dell Laptop |
| LOCATED_AT | Item op locatie | Laptop LOCATED_AT Kantoor Hengelo |
| WORKS_IN | Gebruiker werkt in afdeling | Jan WORKS_IN Sales |
| AFFECTS | Ticket beïnvloedt item | Ticket AFFECTS Laptop |
| CONNECTED_TO | Item verbonden met netwerk | Laptop CONNECTED_TO Network |
| SIMILAR_TO | Items komen samen voor | Laptop SIMILAR_TO Printer |
| OWNS | Gebruiker bezit item | Jan OWNS Laptop |
| RELATED_TO | Algemene relatie | Item RELATED_TO Item |
| MENTIONS_USER | Ticket vermeldt gebruiker | Ticket MENTIONS_USER Jan |
| MENTIONS_CI | Ticket vermeldt CI | Ticket MENTIONS_CI Laptop |
| MENTIONS_LOCATION | Ticket vermeldt locatie | Ticket MENTIONS_LOCATION Hengelo |

### 3. Knowledge Extraction Pipeline (`knowledge_extraction_pipeline.py`)

Geïntegreerde pipeline die entity en relationship extraction coördineert.

#### Gebruik

```python
from knowledge_extraction_pipeline import KnowledgeExtractionPipeline

# Initialiseer pipeline
pipeline = KnowledgeExtractionPipeline(db_config, model_name="nl_core_news_sm")

# Process één ticket
ticket_data = {...}
result = pipeline.process_single_ticket(ticket_id=123, ticket_data=ticket_data)

# Process een batch tickets
results = pipeline.process_tickets_batch(limit=100, offset=0)

# Process alle tickets
results = pipeline.process_all_tickets(batch_size=100)

# Statistieken
stats = pipeline.get_pipeline_stats()
print(stats)
```

#### Command-line Gebruik

```bash
# Process een batch van 50 tickets
python knowledge_extraction_pipeline.py --limit 50

# Process alle tickets
python knowledge_extraction_pipeline.py --all --batch-size 100

# Toon statistieken
python knowledge_extraction_pipeline.py --stats

# Met custom database configuratie
python knowledge_extraction_pipeline.py --host localhost --user root --password mypass --database ticketportaal --limit 100
```

## Installatie

### 1. Installeer spaCy

```bash
pip install spacy
```

### 2. Download Nederlands Model

```bash
# Klein model (sneller, minder accuraat)
python -m spacy download nl_core_news_sm

# Groot model (langzamer, meer accuraat)
python -m spacy download nl_core_news_lg
```

### 3. Installeer Dependencies

```bash
pip install mysql-connector-python networkx
```

## Testing

Run het test script:

```bash
python test_entity_relationship_extraction.py
```

Dit test:
- Entity extraction uit sample ticket
- Relationship extraction
- Graph node en edge generatie
- Data validatie

## Performance

### Entity Extraction

- **Snelheid**: ~0.5-1 seconde per ticket (afhankelijk van lengte)
- **Throughput**: ~60-120 tickets per minuut
- **Geheugen**: ~200-500 MB (afhankelijk van model)

### Relationship Extraction

- **Snelheid**: ~0.3-0.7 seconde per ticket
- **Throughput**: ~85-200 tickets per minuut
- **Geheugen**: ~150-300 MB

### Pipeline

- **Totale snelheid**: ~1-2 seconden per ticket
- **Throughput**: ~30-60 tickets per minuut
- **Geheugen**: ~400-800 MB

## Confidence Scores

Alle geëxtraheerde entiteiten en relaties hebben een confidence score (0.0-1.0):

- **0.9-1.0**: Zeer hoog vertrouwen (regex matches, exacte patronen)
- **0.7-0.9**: Hoog vertrouwen (spaCy NER, custom patterns)
- **0.5-0.7**: Medium vertrouwen (dependency parsing)
- **0.3-0.5**: Laag vertrouwen (co-occurrence)

Gebruik deze scores voor filtering en ranking in de RAG pipeline.

## Customization

### Nieuwe Entity Types Toevoegen

Bewerk `entity_extractor.py`:

```python
# Voeg toe aan _add_custom_patterns()
new_patterns = [
    {"label": "MY_ENTITY", "pattern": [{"LOWER": "my"}, {"LOWER": "pattern"}]},
]
ruler.add_patterns(new_patterns)

# Voeg toe aan entity_type_mapping
self.entity_type_mapping['MY_ENTITY'] = 'my_kg_type'
```

### Nieuwe Relationship Types Toevoegen

Bewerk `relationship_extractor.py`:

```python
# Voeg toe aan _define_relationship_patterns()
'MY_RELATIONSHIP': [
    {'pattern': r'pattern_here', 'confidence': 0.8},
]
```

## Troubleshooting

### spaCy Model Niet Gevonden

```
OSError: [E050] Can't find model 'nl_core_news_sm'
```

**Oplossing**: Download het model:
```bash
python -m spacy download nl_core_news_sm
```

### Database Connection Error

```
mysql.connector.errors.DatabaseError: 2003 (HY000): Can't connect to MySQL server
```

**Oplossing**: Controleer database configuratie en of MySQL draait.

### Lage Extraction Quality

**Mogelijke oorzaken**:
- Ticket tekst is te kort of bevat weinig informatie
- Verkeerde taal (model is voor Nederlands)
- Ontbrekende custom patterns voor domein-specifieke termen

**Oplossingen**:
- Voeg meer custom patterns toe
- Gebruik groter spaCy model (nl_core_news_lg)
- Train custom NER model op ticket data

## Integratie met RAG Pipeline

De geëxtraheerde entiteiten en relaties worden gebruikt in de RAG pipeline:

1. **Entity Linking**: Link query entities naar knowledge graph
2. **Graph Traversal**: Vind gerelateerde tickets via graph
3. **Context Enrichment**: Voeg entity informatie toe aan context
4. **Relationship Chains**: Toon hoe tickets gerelateerd zijn

Zie `sync_tickets_to_vector_db.py` voor integratie details.

## Monitoring

### Extraction Statistics

```python
# Entity extraction stats
entity_stats = extractor.get_extraction_stats()
print(f"Total entities: {sum(entity_stats['entity_counts'].values())}")
print(f"By type: {entity_stats['entity_counts']}")
print(f"Avg confidence: {entity_stats['confidence_stats']}")

# Relationship extraction stats
rel_stats = extractor.get_relationship_stats()
print(f"Total relationships: {sum(rel_stats['relationship_counts'].values())}")
print(f"By type: {rel_stats['relationship_counts']}")

# Pipeline stats
pipeline_stats = pipeline.get_pipeline_stats()
print(f"Tickets processed: {pipeline_stats['knowledge_graph']['tickets_with_entities']}")
print(f"Avg entities per ticket: {pipeline_stats['knowledge_graph']['avg_entities_per_ticket']}")
print(f"Avg relationships per ticket: {pipeline_stats['knowledge_graph']['avg_relationships_per_ticket']}")
```

### Logging

Alle modules loggen naar console met timestamps:

```
[2024-10-22 14:30:15] [INFO] [NER] Processing ticket 123
[2024-10-22 14:30:15] [INFO] [NER] Extracted 8 entities from ticket 123
[2024-10-22 14:30:16] [INFO] [REL] Extracted 5 relationships from ticket 123
[2024-10-22 14:30:16] [INFO] [PIPELINE] Successfully processed ticket 123: 8 entities, 5 relationships
```

## Best Practices

1. **Batch Processing**: Process tickets in batches van 50-100 voor optimale performance
2. **Error Handling**: Pipeline blijft doorgaan bij fouten in individuele tickets
3. **Incremental Updates**: Process alleen nieuwe/gewijzigde tickets
4. **Confidence Filtering**: Filter low-confidence entities/relationships (< 0.5)
5. **Regular Retraining**: Update custom patterns gebaseerd op nieuwe data
6. **Monitoring**: Track extraction quality metrics over tijd

## Volgende Stappen

Na implementatie van Task 8 en 9:

1. **Task 10**: Integreer met sync pipeline voor automatische extraction
2. **Task 11-15**: Gebruik knowledge graph voor hybrid retrieval
3. **Task 33**: Implementeer quality metrics voor extraction
4. **Task 53-55**: Setup continuous improvement proces

## Referenties

- [spaCy Documentation](https://spacy.io/usage)
- [spaCy Dutch Models](https://spacy.io/models/nl)
- [NetworkX Documentation](https://networkx.org/documentation/stable/)
- Knowledge Graph Schema: `database/migrations/007_create_knowledge_graph_schema.sql`
