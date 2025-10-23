"""
Test Ollama Integration
Tests the Ollama API integration for RAG queries.
"""

import requests
import json
import time

# Configuration
OLLAMA_URL = 'http://localhost:11434'
OLLAMA_MODEL = 'llama3.1:8b'

def test_ollama_health():
    """Test if Ollama is running and accessible."""
    print("Testing Ollama health...")
    try:
        response = requests.get(f"{OLLAMA_URL}/api/tags", timeout=5)
        if response.status_code == 200:
            print("✓ Ollama is running")
            models = response.json().get('models', [])
            print(f"  Available models: {len(models)}")
            for model in models:
                print(f"    - {model.get('name', 'unknown')}")
            return True
        else:
            print(f"✗ Ollama returned status {response.status_code}")
            return False
    except Exception as e:
        print(f"✗ Ollama health check failed: {e}")
        return False


def test_simple_query():
    """Test a simple query to Ollama."""
    print("\nTesting simple query...")
    
    prompt = "Wat is de hoofdstad van Nederland?"
    
    try:
        url = f"{OLLAMA_URL}/api/generate"
        payload = {
            'model': OLLAMA_MODEL,
            'prompt': prompt,
            'stream': False,
            'options': {
                'temperature': 0.7,
                'top_p': 0.9,
                'top_k': 40
            }
        }
        
        print(f"  Sending query: '{prompt}'")
        start_time = time.time()
        
        response = requests.post(url, json=payload, timeout=30)
        
        elapsed = time.time() - start_time
        
        if response.status_code == 200:
            result = response.json()
            answer = result.get('response', '')
            print(f"✓ Query successful ({elapsed:.2f}s)")
            print(f"  Answer: {answer[:200]}...")
            return True
        else:
            print(f"✗ Query failed with status {response.status_code}")
            return False
            
    except requests.Timeout:
        print("✗ Query timed out")
        return False
    except Exception as e:
        print(f"✗ Query failed: {e}")
        return False


def test_rag_prompt():
    """Test a RAG-style prompt with context."""
    print("\nTesting RAG prompt...")
    
    # Simulate a RAG prompt
    context = """
[Bron 1] Ticket T-2024-001: Printer werkt niet
De printer in kantoor Hengelo geeft een foutmelding "Paper Jam". 
Oplossing: Papier verwijderd uit lade 2, printer herstart.

[Bron 2] Ticket T-2024-015: Printer offline
HP LaserJet Pro in Enschede is offline. 
Oplossing: Netwerkkabel was los, opnieuw aangesloten.
"""
    
    query = "Hoe los ik een printer probleem op?"
    
    prompt = f"""Je bent een AI-assistent voor het K&K Ticketportaal. Je helpt medewerkers met het oplossen van IT-problemen.

VRAAG VAN GEBRUIKER:
{query}

RELEVANTE INFORMATIE UIT TICKETPORTAAL:
{context}

INSTRUCTIES:
1. Beantwoord de vraag op basis van de gegeven informatie
2. Verwijs naar specifieke bronnen (bijv. "Volgens Ticket T-2024-001...")
3. Geef praktische, uitvoerbare adviezen
4. Gebruik Nederlandse taal
5. Wees beknopt maar compleet

ANTWOORD:
"""
    
    try:
        url = f"{OLLAMA_URL}/api/generate"
        payload = {
            'model': OLLAMA_MODEL,
            'prompt': prompt,
            'stream': False,
            'options': {
                'temperature': 0.7,
                'top_p': 0.9,
                'top_k': 40
            }
        }
        
        print(f"  Sending RAG query: '{query}'")
        start_time = time.time()
        
        response = requests.post(url, json=payload, timeout=30)
        
        elapsed = time.time() - start_time
        
        if response.status_code == 200:
            result = response.json()
            answer = result.get('response', '')
            print(f"✓ RAG query successful ({elapsed:.2f}s)")
            print(f"\n  AI Answer:")
            print(f"  {answer}")
            return True
        else:
            print(f"✗ RAG query failed with status {response.status_code}")
            return False
            
    except requests.Timeout:
        print("✗ RAG query timed out")
        return False
    except Exception as e:
        print(f"✗ RAG query failed: {e}")
        return False


def main():
    """Run all tests."""
    print("="*60)
    print("Ollama Integration Tests")
    print("="*60)
    
    results = []
    
    # Test 1: Health check
    results.append(("Health Check", test_ollama_health()))
    
    # Test 2: Simple query
    results.append(("Simple Query", test_simple_query()))
    
    # Test 3: RAG prompt
    results.append(("RAG Prompt", test_rag_prompt()))
    
    # Summary
    print("\n" + "="*60)
    print("Test Summary")
    print("="*60)
    
    for test_name, passed in results:
        status = "✓ PASSED" if passed else "✗ FAILED"
        print(f"{test_name}: {status}")
    
    total = len(results)
    passed = sum(1 for _, p in results if p)
    print(f"\nTotal: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n✓ All tests passed! Ollama integration is working correctly.")
    else:
        print(f"\n✗ {total - passed} test(s) failed. Please check the errors above.")


if __name__ == "__main__":
    main()
