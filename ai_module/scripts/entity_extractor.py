"""
Entity Extraction Module for Ticketportaal RAG System

This module provides Named Entity Recognition (NER) functionality to extract
structured entities from ticket text, including products, errors, locations,
persons, organizations, and technical terms.

Requirements: 3.1
"""

import re
import logging
from typing import Dict, List, Set, Optional
from dataclasses import dataclass, field

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [%(name)s] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


@dataclass
class ExtractedEntities:
    """Container for extracted entities from ticket text"""
    products: List[str] = field(default_factory=list)
    errors: List[str] = field(default_factory=list)
    locations: List[str] = field(default_factory=list)
    persons: List[str] = field(default_factory=list)
    organizations: List[str] = field(default_factory=list)
    technical_terms: List[str] = field(default_factory=list)
    brands: List[str] = field(default_factory=list)
    models: List[str] = field(default_factory=list)
    ip_addresses: List[str] = field(default_factory=list)
    email_addresses: List[str] = field(default_factory=list)
    
    def to_dict(self) -> Dict[str, List[str]]:
        """Convert to dictionary format"""
        return {
            'products': self.products,
            'errors': self.errors,
            'locations': self.locations,
            'persons': self.persons,
            'organizations': self.organizations,
            'technical_terms': self.technical_terms,
            'brands': self.brands,
            'models': self.models,
            'ip_addresses': self.ip_addresses,
            'email_addresses': self.email_addresses
        }


class EntityExtractor:
    """
    Entity extraction using spaCy NER and pattern matching
    
    This class provides methods to extract various entity types from ticket text,
    combining spaCy's NER capabilities with custom pattern matching for
    technical terms, error codes, and domain-specific entities.
    """
    
    def __init__(self, use_spacy: bool = True):
        """
        Initialize the entity extractor
        
        Args:
            use_spacy: Whether to use spaCy NER (requires model download)
        """
        self.use_spacy = use_spacy
        self.nlp = None
        
        if use_spacy:
            try:
                import spacy
                # Try to load Dutch model
                try:
                    self.nlp = spacy.load("nl_core_news_lg")
                    logger.info("Loaded spaCy Dutch model: nl_core_news_lg")
                except OSError:
                    logger.warning("Dutch model not found, trying smaller model")
                    try:
                        self.nlp = spacy.load("nl_core_news_sm")
                        logger.info("Loaded spaCy Dutch model: nl_core_news_sm")
                    except OSError:
                        logger.warning("No Dutch spaCy model found. Run: python -m spacy download nl_core_news_lg")
                        self.use_spacy = False
            except ImportError:
                logger.warning("spaCy not installed. Install with: pip install spacy")
                self.use_spacy = False
        
        # Known brands (hardware/software)
        self.known_brands = {
            'dell', 'hp', 'lenovo', 'asus', 'acer', 'microsoft', 'apple',
            'cisco', 'netgear', 'tp-link', 'canon', 'epson', 'brother',
            'samsung', 'lg', 'intel', 'amd', 'nvidia', 'adobe', 'oracle'
        }
        
        # Known locations (can be extended from database)
        self.known_locations = {
            'hengelo', 'enschede', 'kantoor hengelo', 'kantoor enschede',
            'warehouse', 'magazijn', 'serverruimte', 'server room'
        }
        
        # Technical terms patterns
        self.technical_patterns = [
            r'\b(?:windows|linux|macos|ubuntu|debian)\b',
            r'\b(?:office|excel|word|outlook|powerpoint)\b',
            r'\b(?:chrome|firefox|edge|safari)\b',
            r'\b(?:vpn|wifi|lan|wan|dhcp|dns|tcp|ip|http|https|ftp|smtp|pop3|imap)\b',
            r'\b(?:bios|uefi|boot|startup|shutdown|reboot)\b',
            r'\b(?:driver|firmware|software|hardware|update|patch)\b',
            r'\b(?:printer|scanner|monitor|keyboard|mouse|laptop|desktop|server)\b',
            r'\b(?:ram|cpu|gpu|ssd|hdd|disk|memory)\b',
        ]
        
        logger.info(f"EntityExtractor initialized (spaCy: {self.use_spacy})")
    
    def extract_entities(self, text: str) -> ExtractedEntities:
        """
        Extract all entity types from text
        
        Args:
            text: Input text (ticket description, title, comments, etc.)
            
        Returns:
            ExtractedEntities object containing all extracted entities
        """
        if not text or not text.strip():
            return ExtractedEntities()
        
        entities = ExtractedEntities()
        text_lower = text.lower()
        
        # Extract using spaCy NER if available
        if self.use_spacy and self.nlp:
            entities = self._extract_with_spacy(text, entities)
        
        # Extract using pattern matching (always run)
        entities.errors = self._extract_errors(text)
        entities.ip_addresses = self._extract_ip_addresses(text)
        entities.email_addresses = self._extract_email_addresses(text)
        entities.brands = self._extract_brands(text_lower)
        entities.models = self._extract_models(text)
        entities.technical_terms = self._extract_technical_terms(text_lower)
        entities.products = self._extract_products(text)
        
        # Extract locations (combine spaCy + known locations)
        pattern_locations = self._extract_locations(text_lower)
        entities.locations = list(set(entities.locations + pattern_locations))
        
        # Deduplicate all lists
        entities = self._deduplicate_entities(entities)
        
        logger.debug(f"Extracted entities: {len(entities.products)} products, "
                    f"{len(entities.errors)} errors, {len(entities.locations)} locations")
        
        return entities
    
    def _extract_with_spacy(self, text: str, entities: ExtractedEntities) -> ExtractedEntities:
        """Extract entities using spaCy NER"""
        try:
            doc = self.nlp(text)
            
            for ent in doc.ents:
                entity_text = ent.text.strip()
                
                if ent.label_ == 'PER' or ent.label_ == 'PERSON':
                    # Person names
                    entities.persons.append(entity_text)
                
                elif ent.label_ == 'ORG':
                    # Organizations
                    entities.organizations.append(entity_text)
                
                elif ent.label_ == 'LOC' or ent.label_ == 'GPE':
                    # Locations
                    entities.locations.append(entity_text)
                
                elif ent.label_ == 'PRODUCT':
                    # Products
                    entities.products.append(entity_text)
        
        except Exception as e:
            logger.error(f"spaCy extraction error: {e}")
        
        return entities
    
    def _extract_errors(self, text: str) -> List[str]:
        """Extract error codes and error messages"""
        errors = []
        
        # Windows error codes (0x...)
        hex_errors = re.findall(r'\b0x[0-9A-Fa-f]{8}\b', text)
        errors.extend(hex_errors)
        
        # HTTP status codes
        http_errors = re.findall(r'\b(?:error|status)\s*:?\s*([45]\d{2})\b', text, re.IGNORECASE)
        errors.extend([f"HTTP {code}" for code in http_errors])
        
        # Generic error patterns
        error_patterns = [
            r'error\s*:?\s*([A-Z0-9_-]+)',
            r'exception\s*:?\s*([A-Za-z]+Exception)',
            r'failed\s+with\s+code\s+(\d+)',
            r'error\s+code\s*:?\s*([A-Z0-9-]+)',
        ]
        
        for pattern in error_patterns:
            matches = re.findall(pattern, text, re.IGNORECASE)
            errors.extend(matches)
        
        return errors
    
    def _extract_ip_addresses(self, text: str) -> List[str]:
        """Extract IP addresses (IPv4)"""
        ip_pattern = r'\b(?:\d{1,3}\.){3}\d{1,3}\b'
        ips = re.findall(ip_pattern, text)
        
        # Validate IP addresses
        valid_ips = []
        for ip in ips:
            parts = ip.split('.')
            if all(0 <= int(part) <= 255 for part in parts):
                valid_ips.append(ip)
        
        return valid_ips
    
    def _extract_email_addresses(self, text: str) -> List[str]:
        """Extract email addresses"""
        email_pattern = r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b'
        emails = re.findall(email_pattern, text)
        return emails
    
    def _extract_brands(self, text_lower: str) -> List[str]:
        """Extract known hardware/software brands"""
        brands = []
        
        for brand in self.known_brands:
            if brand in text_lower:
                brands.append(brand.capitalize())
        
        return brands
    
    def _extract_models(self, text: str) -> List[str]:
        """Extract product model numbers"""
        models = []
        
        # Common model patterns
        model_patterns = [
            r'\b[A-Z]{2,}\s*-?\s*\d{3,5}[A-Z]?\b',  # HP-2055, DELL-5520
            r'\b(?:Latitude|Optiplex|ThinkPad|Pavilion|ProBook)\s+\d{4}\b',  # Latitude 5520
            r'\b[A-Z]\d{3,4}[A-Z]?\b',  # E5570, T480
        ]
        
        for pattern in model_patterns:
            matches = re.findall(pattern, text)
            models.extend(matches)
        
        return models
    
    def _extract_technical_terms(self, text_lower: str) -> List[str]:
        """Extract technical terms and keywords"""
        terms = []
        
        for pattern in self.technical_patterns:
            matches = re.findall(pattern, text_lower, re.IGNORECASE)
            terms.extend(matches)
        
        return terms
    
    def _extract_products(self, text: str) -> List[str]:
        """Extract product names (combination of brand + model)"""
        products = []
        
        # Look for brand + model combinations
        for brand in self.known_brands:
            # Pattern: Brand Model
            pattern = rf'\b{brand}\s+[A-Za-z0-9-]+\b'
            matches = re.findall(pattern, text, re.IGNORECASE)
            products.extend(matches)
        
        return products
    
    def _extract_locations(self, text_lower: str) -> List[str]:
        """Extract known locations"""
        locations = []
        
        for location in self.known_locations:
            if location in text_lower:
                locations.append(location.title())
        
        return locations
    
    def _deduplicate_entities(self, entities: ExtractedEntities) -> ExtractedEntities:
        """Remove duplicates from all entity lists"""
        entities.products = list(set(entities.products))
        entities.errors = list(set(entities.errors))
        entities.locations = list(set(entities.locations))
        entities.persons = list(set(entities.persons))
        entities.organizations = list(set(entities.organizations))
        entities.technical_terms = list(set(entities.technical_terms))
        entities.brands = list(set(entities.brands))
        entities.models = list(set(entities.models))
        entities.ip_addresses = list(set(entities.ip_addresses))
        entities.email_addresses = list(set(entities.email_addresses))
        
        return entities
    
    def extract_from_ticket(self, ticket_data: Dict) -> ExtractedEntities:
        """
        Extract entities from complete ticket data
        
        Args:
            ticket_data: Dictionary containing ticket fields (title, description, comments, etc.)
            
        Returns:
            ExtractedEntities object with all extracted entities
        """
        # Combine all text fields
        text_parts = []
        
        if 'title' in ticket_data and ticket_data['title']:
            text_parts.append(ticket_data['title'])
        
        if 'description' in ticket_data and ticket_data['description']:
            text_parts.append(ticket_data['description'])
        
        if 'resolution' in ticket_data and ticket_data['resolution']:
            text_parts.append(ticket_data['resolution'])
        
        # Add comments
        if 'comments' in ticket_data and ticket_data['comments']:
            for comment in ticket_data['comments']:
                if isinstance(comment, dict) and 'comment_text' in comment:
                    text_parts.append(comment['comment_text'])
                elif isinstance(comment, str):
                    text_parts.append(comment)
        
        # Combine all text
        combined_text = ' '.join(text_parts)
        
        # Extract entities
        entities = self.extract_entities(combined_text)
        
        # Add dynamic field values as additional context
        if 'dynamic_fields' in ticket_data and ticket_data['dynamic_fields']:
            for field in ticket_data['dynamic_fields']:
                if isinstance(field, dict):
                    field_name = field.get('field_name', '').lower()
                    field_value = field.get('field_value', '')
                    
                    if field_value:
                        # Categorize based on field name
                        if 'merk' in field_name or 'brand' in field_name:
                            entities.brands.append(field_value)
                        elif 'model' in field_name:
                            entities.models.append(field_value)
                        elif 'locatie' in field_name or 'location' in field_name:
                            entities.locations.append(field_value)
        
        # Deduplicate again after adding dynamic fields
        entities = self._deduplicate_entities(entities)
        
        return entities


def main():
    """Test the entity extractor"""
    # Test text
    test_text = """
    Laptop Dell Latitude 5520 start niet op na Windows update.
    Error code: 0x0000007B
    Locatie: Kantoor Hengelo
    IP adres: 192.168.1.100
    Contact: jan.jansen@kruit-en-kramer.nl
    
    Printer HP LaserJet 2055 geeft paper jam error.
    Status: HTTP 500 error bij printen.
    """
    
    print("Testing Entity Extractor...")
    print("=" * 60)
    
    extractor = EntityExtractor(use_spacy=True)
    entities = extractor.extract_entities(test_text)
    
    print("\nExtracted Entities:")
    print("-" * 60)
    for entity_type, values in entities.to_dict().items():
        if values:
            print(f"{entity_type.upper()}: {values}")
    
    print("\n" + "=" * 60)
    print("Test completed!")


if __name__ == "__main__":
    main()
