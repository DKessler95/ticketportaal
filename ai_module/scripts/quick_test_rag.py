"""
Quick RAG API Test
Simple test to verify RAG API is working.
"""

import requests
import time

API_URL = "http://localhost:5005"

def test_health():
    """Test health endpoint."""
    print("\n" + "="*60)
    print("Testing /health endpoint...")
    print("="*60)
    
    try:
        response = requests.get(f"{API_URL}/health", timeout=5)
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print(f"✓ Health check passed")
            print(f"  Status: {data.get('status')}")
            print(f"  Ollama: {data.get('ollama_available')}")
            print(f"  ChromaDB: {data.get('chromadb_available')}")
            print(f"  Graph: {data.get('graph_available')}")
            return True
        else:
            print(f"✗ Health check failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"✗ Error: {e}")
        return False

def test_simple_query():
    """Test simple RAG query."""
    print("\n" + "="*60)
    print("Testing /rag_query endpoint...")
    print("="*60)
    
    try:
        query = "Hoe los ik een printer probleem op?"
        print(f"Query: {query}")
        
        payload = {
            "query": query,
            "top_k": 5,
            "include_tickets": True,
            "include_kb": True,
            "include_ci": False
        }
        
        print("Sending request...")
        start = time.time()
        response = requests.post(
            f"{API_URL}/rag_query",
            json=payload,
            timeout=60
        )
        duration = time.time() - start
        
        print(f"Status Code: {response.status_code}")
        print(f"Duration: {duration:.2f}s")
        
        if response.status_code == 200:
            data = response.json()
            print(f"\n✓ Query successful")
            print(f"  Success: {data.get('success')}")
            print(f"  Confidence: {data.get('confidence_score', 0):.2f}")
            print(f"  Sources: {len(data.get('sources', []))}")
            print(f"  Relationships: {len(data.get('relationships', []))}")
            
            if data.get('ai_answer'):
                print(f"\n  AI Answer:")
                answer = data['ai_answer']
                # Print first 300 chars
                print(f"  {answer[:300]}...")
            
            return True
        else:
            print(f"✗ Query failed: {response.status_code}")
            print(f"  Response: {response.text[:200]}")
            return False
            
    except Exception as e:
        print(f"✗ Error: {e}")
        return False

def main():
    print("\n" + "="*60)
    print("Quick RAG API Test")
    print("="*60)
    print(f"API URL: {API_URL}")
    
    # Test health
    health_ok = test_health()
    
    if not health_ok:
        print("\n✗ Health check failed. Make sure RAG API is running.")
        print("  Start it with: python rag_api.py")
        return
    
    # Test query
    query_ok = test_simple_query()
    
    # Summary
    print("\n" + "="*60)
    print("Test Summary")
    print("="*60)
    if health_ok and query_ok:
        print("✓ All tests passed!")
    else:
        print("✗ Some tests failed")
    print("="*60)

if __name__ == "__main__":
    main()
