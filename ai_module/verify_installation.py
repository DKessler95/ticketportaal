"""
Installation Verification Script
Verifies that all components of the AI system are properly installed and configured
"""

import sys
import os
import subprocess
import requests
import json
from datetime import datetime

# Colors for terminal output
class Colors:
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    BLUE = '\033[94m'
    END = '\033[0m'
    BOLD = '\033[1m'

def print_header(text):
    print(f"\n{Colors.BOLD}{Colors.BLUE}{'='*60}{Colors.END}")
    print(f"{Colors.BOLD}{Colors.BLUE}{text}{Colors.END}")
    print(f"{Colors.BOLD}{Colors.BLUE}{'='*60}{Colors.END}\n")

def print_success(text):
    print(f"{Colors.GREEN}✓ {text}{Colors.END}")

def print_error(text):
    print(f"{Colors.RED}✗ {text}{Colors.END}")

def print_warning(text):
    print(f"{Colors.YELLOW}⚠ {text}{Colors.END}")

def print_info(text):
    print(f"  {text}")

# Test results
results = {
    'passed': 0,
    'failed': 0,
    'warnings': 0
}

print_header("K&K Ticketportaal AI - Installatie Verificatie")
print(f"Datum: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

# Test 1: Python Dependencies
print(f"{Colors.BOLD}[Test 1] Python Dependencies{Colors.END}")
dependencies = [
    'mysql.connector',
    'chromadb',
    'sentence_transformers',
    'fastapi',
    'uvicorn',
    'spacy',
    'rank_bm25',
    'networkx',
    'numpy',
    'tqdm',
    'psutil',
    'requests'
]

for dep in dependencies:
    try:
        __import__(dep.replace('-', '_'))
        print_success(f"{dep} geïnstalleerd")
        results['passed'] += 1
    except ImportError:
        print_error(f"{dep} NIET geïnstalleerd")
        results['failed'] += 1

# Test 2: spaCy Model
print(f"\n{Colors.BOLD}[Test 2] spaCy Nederlands Model{Colors.END}")
try:
    import spacy
    nlp = spacy.load("nl_core_news_lg")
    print_success("nl_core_news_lg model geladen")
    results['passed'] += 1
except OSError:
    print_error("nl_core_news_lg model NIET gevonden")
    print_info("Installeer met: python -m spacy download nl_core_news_lg")
    results['failed'] += 1

# Test 3: Ollama Service
print(f"\n{Colors.BOLD}[Test 3] Ollama Service{Colors.END}")
try:
    response = requests.get("http://localhost:11434/api/tags", timeout=5)
    if response.status_code == 200:
        models = response.json().get('models', [])
        print_success(f"Ollama draait ({len(models)} modellen)")
        
        # Check for Llama model
        llama_found = any('llama3.1' in m.get('name', '') for m in models)
        if llama_found:
            print_success("Llama 3.1 model gevonden")
            results['passed'] += 1
        else:
            print_warning("Llama 3.1 model niet gevonden")
            print_info("Download met: ollama pull llama3.1:8b")
            results['warnings'] += 1
        results['passed'] += 1
    else:
        print_error(f"Ollama geeft foutcode: {response.status_code}")
        results['failed'] += 1
except requests.exceptions.RequestException as e:
    print_error("Ollama is NIET bereikbaar")
    print_info("Start Ollama service of installeer van https://ollama.com")
    results['failed'] += 1

# Test 4: RAG API Service
print(f"\n{Colors.BOLD}[Test 4] RAG API Service{Colors.END}")
try:
    response = requests.get("http://localhost:5005/health", timeout=5)
    if response.status_code == 200:
        health = response.json()
        print_success(f"RAG API draait (status: {health.get('status', 'unknown')})")
        
        # Check components
        if health.get('ollama_available'):
            print_success("  Ollama beschikbaar")
        else:
            print_warning("  Ollama niet beschikbaar")
            results['warnings'] += 1
            
        if health.get('chromadb_available'):
            print_success("  ChromaDB beschikbaar")
        else:
            print_warning("  ChromaDB niet beschikbaar")
            results['warnings'] += 1
            
        if health.get('graph_available'):
            print_success("  Knowledge Graph beschikbaar")
        else:
            print_warning("  Knowledge Graph niet beschikbaar")
            results['warnings'] += 1
            
        results['passed'] += 1
    else:
        print_error(f"RAG API geeft foutcode: {response.status_code}")
        results['failed'] += 1
except requests.exceptions.RequestException:
    print_error("RAG API is NIET bereikbaar")
    print_info("Start service met: Start-Service TicketportaalRAG")
    results['failed'] += 1

# Test 5: ChromaDB Data
print(f"\n{Colors.BOLD}[Test 5] ChromaDB Data{Colors.END}")
try:
    import chromadb
    from chromadb.config import Settings
    
    chromadb_path = os.path.join(os.path.dirname(__file__), 'chromadb_data')
    if os.path.exists(chromadb_path):
        client = chromadb.Client(Settings(
            persist_directory=chromadb_path,
            anonymized_telemetry=False
        ))
        
        collections = client.list_collections()
        print_success(f"ChromaDB directory gevonden ({len(collections)} collecties)")
        
        for collection in collections:
            count = collection.count()
            print_info(f"  {collection.name}: {count} documenten")
            
        if len(collections) > 0:
            results['passed'] += 1
        else:
            print_warning("Geen collecties gevonden - voer sync uit")
            results['warnings'] += 1
    else:
        print_warning("ChromaDB directory niet gevonden")
        print_info(f"Verwacht op: {chromadb_path}")
        results['warnings'] += 1
except Exception as e:
    print_error(f"Fout bij checken ChromaDB: {e}")
    results['failed'] += 1

# Test 6: Database Connection
print(f"\n{Colors.BOLD}[Test 6] MySQL Database{Colors.END}")
try:
    import mysql.connector
    
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='ticketportaal'
    )
    
    cursor = conn.cursor()
    
    # Check tickets table
    cursor.execute("SELECT COUNT(*) FROM tickets")
    ticket_count = cursor.fetchone()[0]
    print_success(f"Database verbinding OK ({ticket_count} tickets)")
    
    # Check graph tables
    cursor.execute("SELECT COUNT(*) FROM graph_nodes")
    node_count = cursor.fetchone()[0]
    print_info(f"  Knowledge graph: {node_count} nodes")
    
    cursor.execute("SELECT COUNT(*) FROM graph_edges")
    edge_count = cursor.fetchone()[0]
    print_info(f"  Knowledge graph: {edge_count} edges")
    
    cursor.close()
    conn.close()
    
    results['passed'] += 1
except mysql.connector.Error as e:
    print_error(f"Database fout: {e}")
    print_info("Check MySQL service en credentials")
    results['failed'] += 1

# Test 7: Scripts
print(f"\n{Colors.BOLD}[Test 7] Scripts{Colors.END}")
script_dir = os.path.join(os.path.dirname(__file__), 'scripts')
required_scripts = [
    'sync_tickets_to_vector_db.py',
    'rag_api.py',
    'hybrid_retrieval.py',
    'entity_extractor.py',
    'relationship_extractor.py',
    'knowledge_graph.py'
]

for script in required_scripts:
    script_path = os.path.join(script_dir, script)
    if os.path.exists(script_path):
        print_success(f"{script} gevonden")
        results['passed'] += 1
    else:
        print_error(f"{script} NIET gevonden")
        results['failed'] += 1

# Test 8: Configuration
print(f"\n{Colors.BOLD}[Test 8] Configuratie{Colors.END}")
config_files = [
    '../config/ai_config.php',
    '../config/database.php'
]

for config in config_files:
    config_path = os.path.join(os.path.dirname(__file__), config)
    if os.path.exists(config_path):
        print_success(f"{os.path.basename(config)} gevonden")
        results['passed'] += 1
    else:
        print_error(f"{os.path.basename(config)} NIET gevonden")
        results['failed'] += 1

# Test 9: Logs Directory
print(f"\n{Colors.BOLD}[Test 9] Logs Directory{Colors.END}")
log_dir = os.path.join(os.path.dirname(__file__), 'logs')
if os.path.exists(log_dir):
    log_files = os.listdir(log_dir)
    print_success(f"Logs directory gevonden ({len(log_files)} bestanden)")
    results['passed'] += 1
else:
    print_warning("Logs directory niet gevonden - wordt aangemaakt bij eerste gebruik")
    results['warnings'] += 1

# Summary
print_header("Verificatie Resultaten")

total_tests = results['passed'] + results['failed'] + results['warnings']
print(f"Totaal tests: {total_tests}")
print(f"{Colors.GREEN}Geslaagd: {results['passed']}{Colors.END}")
print(f"{Colors.RED}Gefaald: {results['failed']}{Colors.END}")
print(f"{Colors.YELLOW}Waarschuwingen: {results['warnings']}{Colors.END}")

print("\n" + "="*60)

if results['failed'] == 0 and results['warnings'] == 0:
    print(f"{Colors.GREEN}{Colors.BOLD}✓ INSTALLATIE VOLLEDIG EN CORRECT!{Colors.END}")
    print("\nHet systeem is klaar voor gebruik.")
    exit_code = 0
elif results['failed'] == 0:
    print(f"{Colors.YELLOW}{Colors.BOLD}⚠ INSTALLATIE VOLTOOID MET WAARSCHUWINGEN{Colors.END}")
    print("\nHet systeem werkt, maar sommige componenten zijn niet optimaal.")
    print("Los de waarschuwingen op voor beste performance.")
    exit_code = 0
else:
    print(f"{Colors.RED}{Colors.BOLD}✗ INSTALLATIE INCOMPLEET{Colors.END}")
    print("\nLos de fouten op voordat je het systeem gebruikt.")
    print("Zie INSTALLATION_GUIDE_NL.md voor hulp.")
    exit_code = 1

print("\nVolgende stappen:")
if results['failed'] > 0:
    print("1. Los de bovenstaande fouten op")
    print("2. Run dit script opnieuw")
else:
    print("1. Voer eerste sync uit: python scripts/sync_tickets_to_vector_db.py --limit 10")
    print("2. Bekijk AI Dashboard: http://localhost/admin/ai_dashboard.php")
    print("3. Test AI suggesties op een ticket")

print("\n" + "="*60 + "\n")

sys.exit(exit_code)
