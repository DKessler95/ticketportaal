"""
Entity Extraction Module
Extracts named entities from ticket text using spaCy NER.

This module handles:
- Loading Dutch NER model (nl_core_news_lg)
- Extracting entities: products, errors, locations, persons, organizations
- Extracting domain-specific entities from dynamic fields
- Confidence scoring for extracted entities
"""

import spacy
from typing import Dict, List, Tuple, Optional, Any
import re
import logging
from datetime import datetime

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [NER] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


class EntityExtractor:
    """
    Named Entity Recognition for ticket text.
    
    Extracts entities from ticket descriptions, comments, and dynamic fields
    to populate the knowledge graph.
    """
    
    def __init__(self, model_name: str = "nl_core_news_lg"):
        """
        Initialize entity extractor with spaCy model.
        
        Args:
            model_name: spaCy model to use (default: nl_core_news_lg for Dutch)
        """
        self.model_name = model_name
        self.nlp = None
        self._load_model()
        
        # Domain-specific patterns for IT entities
        self.error_patterns = [
            r'error\s*[:\s]*\d+',
            r'0x[0-9A-Fa-f]+',
            r'blue\s*screen',
            r'BSOD',
            r'fatal\s*error',
            r'exception',
            r'crash',
        ]
        
        self.product_brands = [
            'Dell', 'HP', 'Lenovo', 'Microsoft', 'Windows', 'Office',
            'Adobe', 'Chrome', 'Firefox', 'Outlook', 'Teams', 'Zoom',
            'Canon', 'Epson', 'Brother', 'Samsung', 'Apple', 'Cisco'
        ]
        
        self.location_keywords = [
            'kantoor', 'locatie', 'afdeling', 'verdieping', 'kamer',
            'Hengelo', 'Enschede', 'warehouse', 'magazijn'
        ]
    
    def _load_model(self) -> None:
        """Load spaCy NER model."""
        try:
            logger.info(f"Loading spaCy model: {self.model_name}")
            self.nlp = spacy.load(self.model_name)
            logger.info(f"Model loaded successfully")
        except OSError:
            logger.error(f"Model {self.model_name} not found. Install with: python -m spacy download {self.model_name}")
            raise
    
    def extract_entities(self, text: str, dynamic_fields: Optional[Dict[str, Any]] = None) -> Dict[str, List[Dict[str, Any]]]:
        """
        Extract all entities from text and dynamic fields.
        
        Args:
            text: Ticket description, comment, or resolution text
            dynamic_fields: Optional dictionary of dynamic field values
        
        Returns:
            Dictionary with entity types as keys and lists of entities as values
            {
                'products': [{'text': 'Dell Latitude', 'confidence': 0.9}],
                'errors': [{'text': 'Error 0x0000007B', 'confidence': 1.0}],
                'persons': [{'text': 'Jan Jansen', 'confidence': 0.95}],
                'organizations': [{'text': 'Microsoft', 'confidence': 0.85}],
                'locations': [{'text': 'Kantoor Hengelo', 'confidence': 0.9}]
            }
        """
        entities = {
            'products': [],
            'errors': [],
            'persons': [],
            'organizations': [],
            'locations': [],
            'misc': []
        }
        
        if not text:
            return entities
        
        # Extract entities using spaCy NER
        doc = self.nlp(text)
        
        for ent in doc.ents:
            entity_data = {
                'text': ent.text,
                'label': ent.label_,
                'confidence': 0.8,  # Base confidence for spaCy entities
                'start': ent.start_char,
                'end': ent.end_char
            }
            
            # Map spaCy labels to our entity types
            if ent.label_ == 'PER' or ent.label_ == 'PERSON':
                entities['persons'].append(entity_data)
            elif ent.label_ == 'ORG':
                # Check if it's a known product brand
                if any(brand.lower() in ent.text.lower() for brand in self.product_brands):
                    entity_data['confidence'] = 0.9
                    entities['products'].append(entity_data)
                else:
                    entities['organizations'].append(entity_data)
            elif ent.label_ == 'LOC' or ent.label_ == 'GPE':
                entities['locations'].append(entity_data)
            elif ent.label_ == 'PRODUCT':
                entity_data['confidence'] = 0.85
                entities['products'].append(entity_data)
            else:
                entities['misc'].append(entity_data)
        
        # Extract error codes and messages using regex
        error_entities = self._extract_errors(text)
        entities['errors'].extend(error_entities)
        
        # Extract product mentions using brand patterns
        product_entities = self._extract_products(text)
        entities['products'].extend(product_entities)
        
        # Extract location mentions
        location_entities = self._extract_locations(text)
        entities['locations'].extend(location_entities)
        
        # Extract entities from dynamic fields
        if dynamic_fields:
            field_entities = self._extract_from_dynamic_fields(dynamic_fields)
            for entity_type, entity_list in field_entities.items():
                entities[entity_type].extend(entity_list)
        
        # Remove duplicates and sort by confidence
        for entity_type in entities:
            entities[entity_type] = self._deduplicate_entities(entities[entity_type])
        
        return entities
    
    def _extract_errors(self, text: str) -> List[Dict[str, Any]]:
        """Extract error codes and error messages."""
        errors = []
        
        for pattern in self.error_patterns:
            matches = re.finditer(pattern, text, re.IGNORECASE)
            for match in matches:
                errors.append({
                    'text': match.group(0),
                    'label': 'ERROR',
                    'confidence': 1.0,  # High confidence for pattern matches
                    'start': match.start(),
                    'end': match.end()
                })
        
        return errors
    
    def _extract_products(self, text: str) -> List[Dict[str, Any]]:
        """Extract product names and brands."""
        products = []
        
        for brand in self.product_brands:
            # Look for brand mentions with potential model numbers
            pattern = rf'\b{brand}\b[\s\w\-]*'
            matches = re.finditer(pattern, text, re.IGNORECASE)
            
            for match in matches:
                product_text = match.group(0).strip()
                # Only include if it's more than just the brand name
                if len(product_text) > len(brand) + 2:
                    products.append({
                        'text': product_text,
                        'label': 'PRODUCT',
                        'confidence': 0.9,
                        'start': match.start(),
                        'end': match.end()
                    })
        
        return products
    
    def _extract_locations(self, text: str) -> List[Dict[str, Any]]:
        """Extract location mentions."""
        locations = []
        
        for keyword in self.location_keywords:
            # Look for location keywords with context
            pattern = rf'\b{keyword}\b[\s\w\-]*'
            matches = re.finditer(pattern, text, re.IGNORECASE)
            
            for match in matches:
                location_text = match.group(0).strip()
                locations.append({
                    'text': location_text,
                    'label': 'LOCATION',
                    'confidence': 0.75,
                    'start': match.start(),
                    'end': match.end()
                })
        
        return locations
    
    def _extract_from_dynamic_fields(self, dynamic_fields: Dict[str, Any]) -> Dict[str, List[Dict[str, Any]]]:
        """Extract entities from structured dynamic fields."""
        entities = {
            'products': [],
            'errors': [],
            'persons': [],
            'organizations': [],
            'locations': [],
            'misc': []
        }
        
        # Map field names to entity types
        field_mapping = {
            'merk': 'products',
            'model': 'products',
            'brand': 'products',
            'applicatie_naam': 'products',
            'locatie': 'locations',
            'afdeling': 'organizations',
            'department': 'organizations',
            'naam': 'persons',
            'name': 'persons',
        }
        
        for field_name, field_value in dynamic_fields.items():
            if not field_value or not isinstance(field_value, str):
                continue
            
            # Normalize field name
            field_name_lower = field_name.lower().replace(' ', '_')
            
            # Find matching entity type
            entity_type = None
            for key, value in field_mapping.items():
                if key in field_name_lower:
                    entity_type = value
                    break
            
            if entity_type:
                entities[entity_type].append({
                    'text': field_value,
                    'label': entity_type.upper(),
                    'confidence': 1.0,  # High confidence for structured data
                    'source': 'dynamic_field',
                    'field_name': field_name
                })
        
        return entities
    
    def _deduplicate_entities(self, entities: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Remove duplicate entities and keep highest confidence."""
        if not entities:
            return []
        
        # Group by normalized text
        entity_map = {}
        for entity in entities:
            key = entity['text'].lower().strip()
            if key not in entity_map or entity['confidence'] > entity_map[key]['confidence']:
                entity_map[key] = entity
        
        # Sort by confidence (descending)
        result = list(entity_map.values())
        result.sort(key=lambda x: x['confidence'], reverse=True)
        
        return result
    
    def extract_ticket_entities(self, ticket_data: Dict[str, Any]) -> Dict[str, List[Dict[str, Any]]]:
        """
        Extract entities from complete ticket data.
        
        Args:
            ticket_data: Dictionary containing ticket fields
                {
                    'title': str,
                    'description': str,
                    'comments': List[str],
                    'resolution': str,
                    'dynamic_fields': Dict[str, Any]
                }
        
        Returns:
            Combined entities from all ticket text fields
        """
        all_entities = {
            'products': [],
            'errors': [],
            'persons': [],
            'organizations': [],
            'locations': [],
            'misc': []
        }
        
        # Extract from title
        if ticket_data.get('title'):
            title_entities = self.extract_entities(ticket_data['title'])
            for entity_type, entity_list in title_entities.items():
                all_entities[entity_type].extend(entity_list)
        
        # Extract from description
        if ticket_data.get('description'):
            desc_entities = self.extract_entities(ticket_data['description'])
            for entity_type, entity_list in desc_entities.items():
                all_entities[entity_type].extend(entity_list)
        
        # Extract from comments
        if ticket_data.get('comments'):
            for comment in ticket_data['comments']:
                if isinstance(comment, str):
                    comment_text = comment
                elif isinstance(comment, dict):
                    comment_text = comment.get('text', '')
                else:
                    continue
                
                comment_entities = self.extract_entities(comment_text)
                for entity_type, entity_list in comment_entities.items():
                    all_entities[entity_type].extend(entity_list)
        
        # Extract from resolution
        if ticket_data.get('resolution'):
            resolution_entities = self.extract_entities(ticket_data['resolution'])
            for entity_type, entity_list in resolution_entities.items():
                all_entities[entity_type].extend(entity_list)
        
        # Extract from dynamic fields
        if ticket_data.get('dynamic_fields'):
            field_entities = self._extract_from_dynamic_fields(ticket_data['dynamic_fields'])
            for entity_type, entity_list in field_entities.items():
                all_entities[entity_type].extend(entity_list)
        
        # Deduplicate all entities
        for entity_type in all_entities:
            all_entities[entity_type] = self._deduplicate_entities(all_entities[entity_type])
        
        return all_entities
    
    def entities_to_graph_nodes(self, entities: Dict[str, List[Dict[str, Any]]], 
                                ticket_id: int) -> List[Dict[str, Any]]:
        """
        Convert extracted entities to graph node format.
        
        Args:
            entities: Dictionary of extracted entities
            ticket_id: ID of the ticket these entities came from
        
        Returns:
            List of node dictionaries ready for graph insertion
        """
        nodes = []
        
        entity_type_mapping = {
            'products': 'product',
            'errors': 'error',
            'persons': 'person',
            'organizations': 'organization',
            'locations': 'location',
            'misc': 'entity'
        }
        
        for entity_type, entity_list in entities.items():
            node_type = entity_type_mapping.get(entity_type, 'entity')
            
            for entity in entity_list:
                # Create unique node ID
                node_id = f"{node_type}_{entity['text'].lower().replace(' ', '_')}"
                
                nodes.append({
                    'node_id': node_id,
                    'node_type': node_type,
                    'properties': {
                        'name': entity['text'],
                        'label': entity.get('label', entity_type.upper()),
                        'confidence': entity['confidence'],
                        'source_ticket_id': ticket_id,
                        'extracted_at': datetime.now().isoformat()
                    }
                })
        
        return nodes


# Example usage
if __name__ == "__main__":
    # Initialize extractor
    extractor = EntityExtractor()
    
    # Example ticket data
    ticket_data = {
        'title': 'Dell Latitude laptop start niet op',
        'description': 'Mijn Dell Latitude 5520 geeft een blue screen error 0x0000007B bij opstarten. Het probleem is begonnen na de laatste Windows update.',
        'comments': [
            'Ik heb geprobeerd in safe mode op te starten maar dat werkt ook niet.',
            'De laptop staat op kantoor Hengelo, verdieping 2.'
        ],
        'resolution': 'BIOS update uitgevoerd en boot order aangepast. Probleem opgelost.',
        'dynamic_fields': {
            'Merk': 'Dell',
            'Model': 'Latitude 5520',
            'Serienummer': 'ABC123XYZ',
            'Locatie': 'Kantoor Hengelo',
            'Afdeling': 'Sales'
        }
    }
    
    # Extract entities
    print("Extracting entities from ticket...")
    entities = extractor.extract_ticket_entities(ticket_data)
    
    print("\nExtracted Entities:")
    for entity_type, entity_list in entities.items():
        if entity_list:
            print(f"\n{entity_type.upper()}:")
            for entity in entity_list:
                print(f"  - {entity['text']} (confidence: {entity['confidence']:.2f})")
    
    # Convert to graph nodes
    print("\nConverting to graph nodes...")
    nodes = extractor.entities_to_graph_nodes(entities, ticket_id=123)
    
    print(f"\nGenerated {len(nodes)} graph nodes:")
    for node in nodes[:5]:  # Show first 5
        print(f"  - {node['node_id']}: {node['properties']['name']}")
